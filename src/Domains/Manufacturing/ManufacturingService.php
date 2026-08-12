<?php

declare(strict_types=1);

namespace Fmos\Domains\Manufacturing;

use Fmos\Core\Audit;
use Fmos\Core\Auth;
use Fmos\Core\Database;
use Fmos\Domains\Catalog\CatalogService;
use Fmos\Domains\Catalog\MaterialService;
use Fmos\Domains\Furniture\FurnitureEngine;
use Fmos\Domains\Furniture\FurnitureExpo;
use Fmos\Domains\Furniture\PanelFinishResolver;

final class ManufacturingService
{
    public function validateFurniture(int $tenantId, int $furnitureId): array
    {
        $engine = new FurnitureEngine();
        $furniture = $engine->refreshComponents($tenantId, $furnitureId);
        $sheet = $this->defaultSheet($tenantId);
        $sheetL = (float) $sheet['length_mm'];
        $sheetW = (float) $sheet['width_mm'];
        $issues = [];

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT parameters_json, code FROM furniture_templates WHERE id = ?');
        $stmt->execute([(int) $furniture['template_id']]);
        $template = $stmt->fetch() ?: [];
        $defs = json_decode((string) ($template['parameters_json'] ?? '{}'), true) ?: [];
        $params = is_array($furniture['parameters'] ?? null) ? $furniture['parameters'] : [];

        foreach ($params as $k => $v) {
            $def = $defs[$k] ?? null;
            $type = is_array($def) ? ($def['type'] ?? 'number') : null;

            // Structured / non-numeric params are valid by design.
            if ($type === 'layout' || $type === 'object' || $type === 'catalog_board' || is_array($v)) {
                if ($k === 'layout' && (!is_array($v) || !isset($v['bays']) || !is_array($v['bays']))) {
                    $issues[] = ['severity' => 'ERROR', 'code' => 'PARAM', 'message' => 'layout must include bays'];
                }
                continue;
            }
            if ($type === 'enum') {
                $opts = $def['options'] ?? [];
                if ($opts !== [] && !in_array($v, $opts, true)) {
                    $issues[] = [
                        'severity' => 'ERROR',
                        'code' => 'PARAM',
                        'message' => "{$k} must be one of " . implode(', ', $opts),
                    ];
                }
                continue;
            }
            // door_type and similar strings without schema type
            if ($def === null && !is_numeric($v) && is_string($v)) {
                continue;
            }
            if (!is_numeric($v)) {
                $issues[] = ['severity' => 'ERROR', 'code' => 'PARAM', 'message' => "{$k} must be numeric"];
                continue;
            }
            $num = (float) $v;
            if (is_array($def) && isset($def['min']) && $num < (float) $def['min']) {
                $issues[] = ['severity' => 'ERROR', 'code' => 'PARAM', 'message' => "{$k} below minimum {$def['min']}"];
            }
            if (is_array($def) && isset($def['max']) && $num > (float) $def['max']) {
                $issues[] = ['severity' => 'ERROR', 'code' => 'PARAM', 'message' => "{$k} exceeds maximum {$def['max']}"];
            }
        }

        foreach (['width', 'height', 'depth'] as $dim) {
            if (!isset($params[$dim]) || !is_numeric($params[$dim]) || (float) $params[$dim] <= 0) {
                $issues[] = ['severity' => 'ERROR', 'code' => 'PARAM', 'message' => "{$dim} is required"];
            }
        }

        foreach ($furniture['component_rows'] as $c) {
            if (($c['component_type'] ?? '') === 'HARDWARE') {
                continue;
            }
            $l = (float) $c['length_mm'];
            $w = (float) $c['width_mm'];
            $fits = ($l <= $sheetL && $w <= $sheetW) || ($w <= $sheetL && $l <= $sheetW);
            if (!$fits) {
                $issues[] = ['severity' => 'BLOCKER', 'code' => 'PANEL_SIZE', 'message' => $c['name'] . ' exceeds sheet even with rotation'];
            }
            if (!empty($c['manufacturing_data']['note'])) {
                $issues[] = ['severity' => 'INFO', 'code' => 'PANEL_SPLIT', 'message' => $c['name'] . ': ' . $c['manufacturing_data']['note']];
            }
        }

        $hasBlocker = (bool) array_filter($issues, static fn ($i) => ($i['severity'] ?? '') === 'BLOCKER');
        $hasError = (bool) array_filter($issues, static fn ($i) => ($i['severity'] ?? '') === 'ERROR');
        return [
            'furniture_id' => $furnitureId,
            'ok' => !$hasBlocker && !$hasError,
            'issues' => $issues,
            'sheet' => $sheet,
            'summary' => [
                'code' => $furniture['code'] ?? null,
                'name' => $furniture['name'] ?? null,
                'parts' => count($furniture['component_rows'] ?? []),
                'errors' => count(array_filter($issues, static fn ($i) => in_array($i['severity'] ?? '', ['ERROR', 'BLOCKER'], true))),
                'infos' => count(array_filter($issues, static fn ($i) => ($i['severity'] ?? '') === 'INFO')),
            ],
            'furniture' => $furniture,
        ];
    }

    public function createProjectManufacturing(int $tenantId, int $projectId, array $furnitureIds): array
    {
        if ($furnitureIds === []) {
            throw new \RuntimeException('furniture_ids required');
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO manufacturing_jobs (tenant_id, project_id, status, validation_json, snapshot_json, created_by, created_at, updated_at) VALUES (?, ?, ?, NULL, NULL, ?, NOW(), NOW())');
            $stmt->execute([$tenantId, $projectId, 'OPEN', Auth::id()]);
            $jobId = (int) $pdo->lastInsertId();

            $packages = [];
            $allIssues = [];
            foreach ($furnitureIds as $fid) {
                $fid = (int) $fid;
                $pkg = $this->validateAndGenerate($tenantId, $projectId, $fid, $jobId);
                $pdo->prepare('INSERT INTO manufacturing_job_furniture (manufacturing_job_id, furniture_id, manufacturing_package_id, validation_json, status) VALUES (?, ?, ?, ?, ?)')
                    ->execute([
                        $jobId,
                        $fid,
                        (int) $pkg['id'],
                        json_encode($pkg['validation'] ?? []),
                        $pkg['status'] === 'BLOCKED' ? 'BLOCKED' : 'READY',
                    ]);
                $packages[] = $pkg;
                foreach ($pkg['validation']['issues'] ?? [] as $issue) {
                    $allIssues[] = $issue + ['furniture_id' => $fid];
                }
            }
            $blocked = (bool) array_filter($packages, static fn ($p) => ($p['status'] ?? '') === 'BLOCKED');
            $pdo->prepare('UPDATE manufacturing_jobs SET status=?, validation_json=?, snapshot_json=?, updated_at=NOW() WHERE id=?')
                ->execute([
                    $blocked ? 'BLOCKED' : 'READY',
                    json_encode(['issues' => $allIssues]),
                    json_encode(['package_ids' => array_map(static fn ($p) => $p['id'], $packages)]),
                    $jobId,
                ]);
            $pdo->commit();
            Audit::record('CREATE', 'manufacturing_job', $jobId, null, ['furniture_ids' => $furnitureIds]);
            return $this->getJob($tenantId, $jobId);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function validateAndGenerate(int $tenantId, int $projectId, int $furnitureId, ?int $jobId = null): array
    {
        $validation = $this->validateFurniture($tenantId, $furnitureId);
        $furniture = $validation['furniture'];
        $issues = $validation['issues'];
        $hasBlocker = !$validation['ok'];
        $sheet = $validation['sheet'];
        $edges = $this->defaultEdges($tenantId);
        $cuttingRule = $this->cuttingRule($tenantId);

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(revision_number),0)+1 FROM manufacturing_packages WHERE tenant_id=? AND project_id=? AND furniture_id=?');
        $stmt->execute([$tenantId, $projectId, $furnitureId]);
        $rev = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare('INSERT INTO manufacturing_packages (tenant_id, project_id, furniture_id, manufacturing_job_id, sheet_definition_id, revision_number, status, validation_json, snapshot_json, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $snapshot = ['furniture' => $furniture, 'generated_at' => date('c'), 'sheet' => $sheet];
        $stmt->execute([
            $tenantId,
            $projectId,
            $furnitureId,
            $jobId,
            (int) $sheet['id'],
            $rev,
            $hasBlocker ? 'BLOCKED' : 'READY',
            json_encode(['issues' => $issues]),
            json_encode($snapshot),
        ]);
        $pkgId = (int) $pdo->lastInsertId();

        if (!$hasBlocker) {
            $this->generatePanelsAndCutlist($tenantId, $projectId, $pkgId, $furnitureId, $furniture, $rev, $edges, $cuttingRule);
            $bomRevId = $this->generateUnifiedBom($tenantId, $projectId, $furnitureId, $pkgId, $furniture);
            $pdo->prepare('UPDATE manufacturing_packages SET bom_revision_id=? WHERE id=?')->execute([$bomRevId, $pkgId]);
        }

        Audit::record('GENERATE', 'manufacturing_package', $pkgId, null, ['issues' => $issues]);
        return $this->getPackage($tenantId, $pkgId);
    }

    private function generatePanelsAndCutlist(
        int $tenantId,
        int $projectId,
        int $pkgId,
        int $furnitureId,
        array $furniture,
        int $rev,
        array $edges,
        array $cuttingRule
    ): void {
        $pdo = Database::connection();
        $matSvc = new MaterialService();
        $finishName = null;
        $finishId = $furniture['exterior_finish_id'] ?? null;
        if ($finishId) {
            try {
                $finishName = $matSvc->get($tenantId, (int) $finishId)['sku'];
            } catch (\Throwable) {
                $finishName = null;
            }
        }

        $panelSeq = 0;
        foreach ($furniture['component_rows'] as $c) {
            if (($c['component_type'] ?? '') === 'HARDWARE') {
                continue;
            }
            $panelSeq++;
            $finL = (float) $c['length_mm'];
            $finW = (float) $c['width_mm'];
            $applyEdges = ((float) $c['thickness_mm']) >= (float) ($edges['apply_to_thickness_gte_mm'] ?? 12);
            $e1 = $applyEdges ? (float) ($edges['edge_1'] ?? 0) : 0.0;
            $e2 = $applyEdges ? (float) ($edges['edge_2'] ?? 0) : 0.0;
            $e3 = $applyEdges ? (float) ($edges['edge_3'] ?? 0) : 0.0;
            $e4 = $applyEdges ? (float) ($edges['edge_4'] ?? 0) : 0.0;
            [$cutL, $cutW] = $this->computeCutting($finL, $finW, $e1, $e2, $e3, $e4, $cuttingRule);

            $publicId = sprintf('P-%d-%d-R%d-%d', $projectId, $furnitureId, $rev, $panelSeq);
            $note = $c['manufacturing_data']['note'] ?? null;
            $role = strtoupper((string) ($c['manufacturing_data']['role'] ?? $c['geometry']['role'] ?? ''));
            $expoMap = FurnitureExpo::fromParameters(is_array($furniture['parameters'] ?? null) ? $furniture['parameters'] : []);
            $faces = PanelFinishResolver::resolve(
                $role !== '' ? $role : 'PANEL',
                $expoMap,
                $furniture['exterior_finish_id'] !== null ? (int) $furniture['exterior_finish_id'] : null,
                $furniture['interior_finish_id'] !== null ? (int) $furniture['interior_finish_id'] : null
            );
            $isExpo = !empty($faces['expo']) || !empty($c['manufacturing_data']['expo']) || !empty($c['geometry']['expo']);
            if ($role !== '' && !$isExpo) {
                $isExpo = FurnitureExpo::isExpo($role, $expoMap);
            }            if ($isExpo) {
                $note = $note ? (rtrim((string) $note) . ' | EXPO') : 'EXPO';
            }
            $primaryFinishId = PanelFinishResolver::primaryFinishId($faces);
            if (!empty($c['finish_id'])) {
                $primaryFinishId = (int) $c['finish_id'];
            }
            $compFinishId = $primaryFinishId ?: ($furniture['exterior_finish_id'] ?? null);
            $compFinishName = $finishName;
            if ($compFinishId) {
                try {
                    $compFinishName = $matSvc->get($tenantId, (int) $compFinishId)['sku'];
                } catch (\Throwable) {
                }
            }
            $faceExtName = null;
            $faceIntName = null;
            if (!empty($faces['face_exterior']['finish_id'])) {
                try {
                    $faceExtName = $matSvc->get($tenantId, (int) $faces['face_exterior']['finish_id'])['sku'];
                } catch (\Throwable) {
                }
            }
            if (!empty($faces['face_interior']['finish_id'])) {
                try {
                    $faceIntName = $matSvc->get($tenantId, (int) $faces['face_interior']['finish_id'])['sku'];
                } catch (\Throwable) {
                }
            }
            $boardName = 'Board';
            $boardCatalogId = null;
            if ($role === 'BACK_PANEL' && !empty($furniture['parameters']['back_material_id'])) {
                $boardCatalogId = (int) $furniture['parameters']['back_material_id'];
            } elseif (!empty($furniture['material_id'])) {
                $boardCatalogId = (int) $furniture['material_id'];
            }
            if ($boardCatalogId) {
                try {
                    $board = (new CatalogService())->get($tenantId, $boardCatalogId);
                    if (strtoupper((string) ($board['category'] ?? '')) === 'BOARD') {
                        $boardName = $board['name'] ?? $board['sku'] ?? 'Board';
                    }
                } catch (\Throwable) {
                }
            }
            $desc = $c['name'];
            $edgeMeta = ['1' => $e1, '2' => $e2, '3' => $e3, '4' => $e4, 'expo' => $isExpo, 'faces' => $faces];
            if ($faceExtName) {
                $edgeMeta['face_exterior_finish'] = $faceExtName;
            }
            if ($faceIntName) {
                $edgeMeta['face_interior_finish'] = $faceIntName;
            }
            $stmt = $pdo->prepare('INSERT INTO panels (
                tenant_id, project_id, manufacturing_package_id, furniture_id, component_id, public_id, name, material_name, material_id, finish_id, finish_name,
                thickness_mm, length_mm, width_mm, finishing_length_mm, finishing_width_mm, cutting_length_mm, cutting_width_mm,
                quantity, grain_direction, edge_json, edge_1, edge_2, edge_3, edge_4, note, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            $stmt->execute([
                $tenantId, $projectId, $pkgId, $furnitureId, (int) $c['id'], $publicId, $desc, $boardName,
                $compFinishId, $compFinishName,
                $c['thickness_mm'], $cutL, $cutW, $finL, $finW, $cutL, $cutW,
                $c['quantity'], 'LENGTH',
                json_encode($edgeMeta),
                $e1, $e2, $e3, $e4, $note, 'CREATED',
            ]);
            $panelId = (int) $pdo->lastInsertId();
            $stmt = $pdo->prepare('INSERT INTO cutlist_items (
                tenant_id, manufacturing_package_id, panel_id, description, length_mm, width_mm, thickness_mm, quantity, material_name,
                finishing_length_mm, finishing_width_mm, cutting_length_mm, cutting_width_mm, colour, edgeband_color,
                edge_1, edge_2, edge_3, edge_4, note, rotate_flag
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)');
            $stmt->execute([
                $tenantId, $pkgId, $panelId, $desc, $cutL, $cutW, $c['thickness_mm'], $c['quantity'], $boardName,
                $finL, $finW, $cutL, $cutW, $compFinishName, $compFinishName,
                $e1, $e2, $e3, $e4, $note,
            ]);
        }
    }

    /** @return array{0:float,1:float} */
    public function computeCutting(float $finL, float $finW, float $e1, float $e2, float $e3, float $e4, array $rule): array
    {
        $decimals = (int) ($rule['rounding_decimals'] ?? 1);
        $mode = $rule['mode'] ?? 'sum_opposite_edges';
        if ($mode === 'sum_opposite_edges') {
            // edges 1/2 on length axis, 3/4 on width axis (shop convention A until owner RQ-002)
            $cutL = $finL - ($e1 + $e2);
            $cutW = $finW - ($e3 + $e4);
        } else {
            $fixed = (float) ($rule['fallback_fixed_mm_per_edged_side'] ?? 0);
            $cutL = $finL - (($e1 > 0 ? $fixed : 0) + ($e2 > 0 ? $fixed : 0));
            $cutW = $finW - (($e3 > 0 ? $fixed : 0) + ($e4 > 0 ? $fixed : 0));
        }
        return [round(max(0, $cutL), $decimals), round(max(0, $cutW), $decimals)];
    }

    private function generateUnifiedBom(int $tenantId, int $projectId, int $furnitureId, int $pkgId, array $furniture): int
    {
        $pdo = Database::connection();
        $board = $pdo->prepare("SELECT * FROM catalog_products WHERE tenant_id=? AND category='BOARD' AND publish_status='PUBLISHED' ORDER BY id LIMIT 1");
        $board->execute([$tenantId]);
        $boardProduct = $board->fetch() ?: ['id' => null, 'name' => 'Board', 'cost' => 45];

        $bomNumber = 'BOM-' . $projectId . '-' . $furnitureId . '-' . time() . '-' . bin2hex(random_bytes(3));
        $stmt = $pdo->prepare('INSERT INTO bom_headers (tenant_id, project_id, furniture_id, bom_number, current_revision, status, created_at, updated_at) VALUES (?, ?, ?, ?, 1, ?, NOW(), NOW())');
        $stmt->execute([$tenantId, $projectId, $furnitureId, $bomNumber, 'GENERATED']);
        $bomId = (int) $pdo->lastInsertId();

        $hash = hash('sha256', json_encode(['pkg' => $pkgId, 'components' => $furniture['component_rows']]));
        $stmt = $pdo->prepare('INSERT INTO bom_revisions (bom_id, revision_number, source_hash, status, created_at) VALUES (?, 1, ?, ?, NOW())');
        $stmt->execute([$bomId, $hash, 'LOCKED']);
        $bomRevId = (int) $pdo->lastInsertId();

        $panels = $pdo->prepare('SELECT * FROM panels WHERE manufacturing_package_id=?');
        $panels->execute([$pkgId]);
        foreach ($panels->fetchAll() as $p) {
            $areaMm2 = ((float) $p['finishing_length_mm'] * (float) $p['finishing_width_mm'] * (float) $p['quantity']);
            $areaSqFt = $areaMm2 / 92903.04;
            $unit = (float) ($boardProduct['cost'] ?? 45);
            $total = $areaSqFt * $unit;
            $stmt = $pdo->prepare('INSERT INTO bom_items (bom_revision_id, item_type, catalog_product_id, description, quantity, uom, unit_cost, total_cost, source_ref) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$bomRevId, 'BOARD', $boardProduct['id'], $p['name'], round($areaSqFt, 4), 'SQ_FT', $unit, round($total, 4), $p['public_id']]);
        }
        foreach ($furniture['component_rows'] as $c) {
            if (($c['component_type'] ?? '') !== 'HARDWARE') {
                continue;
            }
            $qty = (float) $c['quantity'];
            $unit = 35.0;
            $stmt = $pdo->prepare('INSERT INTO bom_items (bom_revision_id, item_type, catalog_product_id, description, quantity, uom, unit_cost, total_cost, source_ref) VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$bomRevId, 'HARDWARE', $c['name'], $qty, 'PCS', $unit, $qty * $unit, $c['component_key']]);
        }
        return $bomRevId;
    }

    public function release(int $tenantId, int $packageId): array
    {
        Auth::requirePermission('manufacturing.release');
        $pkg = $this->getPackage($tenantId, $packageId);
        foreach ($pkg['validation']['issues'] ?? [] as $issue) {
            if (($issue['severity'] ?? '') === 'BLOCKER') {
                throw new \RuntimeException('Cannot release with BLOCKER issues');
            }
        }
        if ($pkg['status'] === 'RELEASED') {
            throw new \RuntimeException('Already released');
        }
        $pdo = Database::connection();
        $stmt = $pdo->prepare("UPDATE manufacturing_packages SET status='RELEASED', released_at=NOW(), released_by=?, updated_at=NOW() WHERE id=? AND tenant_id=?");
        $stmt->execute([Auth::id(), $packageId, $tenantId]);
        $stmt = $pdo->prepare("UPDATE panels SET status='RELEASED' WHERE manufacturing_package_id=? AND tenant_id=?");
        $stmt->execute([$packageId, $tenantId]);
        Audit::record('RELEASE', 'manufacturing_package', $packageId);
        return $this->getPackage($tenantId, $packageId);
    }

    public function cutlist(int $tenantId, int $packageId, ?string $scope = null): array
    {
        $pkg = $this->getPackage($tenantId, $packageId);
        $items = $pkg['cutlist'];
        $furniture = null;
        $hardware = [];
        if (!empty($pkg['furniture_id'])) {
            $furniture = (new FurnitureEngine())->get($tenantId, (int) $pkg['furniture_id']);
            foreach ($furniture['component_rows'] ?? [] as $c) {
                if (($c['component_type'] ?? '') !== 'HARDWARE') {
                    continue;
                }
                $hardware[] = [
                    'description' => $c['name'],
                    'quantity' => $c['quantity'],
                    'material_name' => 'Hardware',
                    'note' => $c['component_key'] ?? null,
                    'thickness_mm' => 0,
                    'finishing_length_mm' => 0,
                    'finishing_width_mm' => 0,
                    'cutting_length_mm' => 0,
                    'cutting_width_mm' => 0,
                    'colour' => null,
                    'edge_1' => null,
                    'edge_2' => null,
                    'edge_3' => null,
                    'edge_4' => null,
                ];
            }
        }
        if ($scope === 'furniture' && $pkg['furniture_id']) {
            // already package-scoped to one furniture
        }
        $expoByComponentId = [];
        foreach (($furniture ?? [])['component_rows'] ?? [] as $c) {
            $expoByComponentId[(int) $c['id']] = !empty($c['manufacturing_data']['expo']);
        }
        $pdo = Database::connection();
        $panelExpo = $pdo->prepare('SELECT id, component_id, edge_json, note FROM panels WHERE manufacturing_package_id=?');
        $panelExpo->execute([$packageId]);
        $expoByPanelId = [];
        foreach ($panelExpo->fetchAll() as $p) {
            $fromEdge = json_decode((string) ($p['edge_json'] ?? ''), true);
            if (is_array($fromEdge) && array_key_exists('expo', $fromEdge)) {
                $expoByPanelId[(int) $p['id']] = (bool) $fromEdge['expo'];
            } elseif (!empty($p['component_id']) && isset($expoByComponentId[(int) $p['component_id']])) {
                $expoByPanelId[(int) $p['id']] = $expoByComponentId[(int) $p['component_id']];
            } else {
                $note = (string) ($p['note'] ?? '');
                $expoByPanelId[(int) $p['id']] = str_contains($note, 'EXPO');
            }
        }
        $items = array_map(static function (array $row) use ($expoByPanelId): array {
            $pid = (int) ($row['panel_id'] ?? 0);
            $row['expo'] = $expoByPanelId[$pid] ?? false;
            return $row;
        }, $items);
        // Enrich face finish labels from panel edge_json
        $panelFaces = $pdo->prepare('SELECT id, edge_json FROM panels WHERE manufacturing_package_id=?');
        $panelFaces->execute([$packageId]);
        $faceByPanel = [];
        foreach ($panelFaces->fetchAll() as $p) {
            $ej = json_decode((string) ($p['edge_json'] ?? ''), true) ?: [];
            $faceByPanel[(int) $p['id']] = [
                'face_exterior_finish' => $ej['face_exterior_finish'] ?? null,
                'face_interior_finish' => $ej['face_interior_finish'] ?? null,
            ];
        }
        $items = array_map(static function (array $row) use ($faceByPanel): array {
            $pid = (int) ($row['panel_id'] ?? 0);
            $row['face_exterior_finish'] = $faceByPanel[$pid]['face_exterior_finish'] ?? null;
            $row['face_interior_finish'] = $faceByPanel[$pid]['face_interior_finish'] ?? null;
            return $row;
        }, $items);
        return [
            'package_id' => $packageId,
            'furniture_id' => $pkg['furniture_id'],
            'furniture_code' => $furniture['code'] ?? null,
            'furniture_name' => $furniture['name'] ?? null,
            'items' => $items,
            'hardware' => $hardware,
            'columns' => ['description', 'finishing_length_mm', 'finishing_width_mm', 'cutting_length_mm', 'cutting_width_mm', 'thickness_mm', 'quantity', 'material_name', 'colour', 'edge_1', 'edge_2', 'edge_3', 'edge_4', 'note', 'expo', 'face_exterior_finish', 'face_interior_finish'],
        ];
    }

    public function nest(int $tenantId, int $packageId, ?array $locked = null): array
    {
        $pkg = $this->getPackage($tenantId, $packageId);
        $sheet = $this->sheetById($tenantId, $pkg['sheet_definition_id'] ? (int) $pkg['sheet_definition_id'] : null)
            ?: $this->defaultSheet($tenantId);
        $sheetL = (float) $sheet['length_mm'];
        $sheetW = (float) $sheet['width_mm'];
        $kerf = (float) $sheet['kerf_mm'];
        $margin = (float) $sheet['margin_mm'];
        $usableL = $sheetL - (2 * $margin);
        $usableW = $sheetW - (2 * $margin);

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM panels WHERE manufacturing_package_id=? AND tenant_id=?');
        $stmt->execute([$packageId, $tenantId]);
        $panels = $stmt->fetchAll();

        $lockedPlacements = $locked ?? [];
        $lockedIds = [];
        foreach ($lockedPlacements as $lp) {
            $lockedIds[(int) $lp['panel_id'] . ':' . ($lp['instance'] ?? 0)] = $lp;
        }

        $sheets = [];
        $current = ['placements' => [], 'cursor_x' => 0.0, 'cursor_y' => 0.0, 'row_h' => 0.0];
        $used = 0.0;

        // Place locked first on sheet 0
        foreach ($lockedPlacements as $lp) {
            $current['placements'][] = $lp + ['locked' => true];
            $used += ((float) $lp['length_mm'] * (float) $lp['width_mm']);
        }

        foreach ($panels as $panel) {
            for ($q = 0; $q < (int) $panel['quantity']; $q++) {
                $key = (int) $panel['id'] . ':' . $q;
                if (isset($lockedIds[$key])) {
                    continue;
                }
                $l = (float) ($panel['cutting_length_mm'] ?? $panel['length_mm']);
                $w = (float) ($panel['cutting_width_mm'] ?? $panel['width_mm']);
                $rotated = false;
                if ($l > $usableL && $w <= $usableL) {
                    [$l, $w] = [$w, $l];
                    $rotated = true;
                }
                if ($current['cursor_x'] + $l > $usableL) {
                    $current['cursor_x'] = 0;
                    $current['cursor_y'] += $current['row_h'] + $kerf;
                    $current['row_h'] = 0;
                }
                if ($current['cursor_y'] + $w > $usableW) {
                    $sheets[] = $current;
                    $current = ['placements' => [], 'cursor_x' => 0.0, 'cursor_y' => 0.0, 'row_h' => 0.0];
                }
                $current['placements'][] = [
                    'panel_id' => (int) $panel['id'],
                    'public_id' => $panel['public_id'],
                    'name' => $panel['name'],
                    'instance' => $q,
                    'x' => $current['cursor_x'] + $margin,
                    'y' => $current['cursor_y'] + $margin,
                    'length_mm' => $l,
                    'width_mm' => $w,
                    'thickness_mm' => (float) $panel['thickness_mm'],
                    'rotated' => $rotated,
                    'locked' => false,
                ];
                $current['cursor_x'] += $l + $kerf;
                $current['row_h'] = max($current['row_h'], $w);
                $used += $l * $w;
                $pdo->prepare("UPDATE panels SET status='NESTED' WHERE id=? AND status IN ('RELEASED','CREATED','READY')")->execute([(int) $panel['id']]);
            }
        }
        if ($current['placements']) {
            $sheets[] = $current;
        }
        $sheetArea = $usableL * $usableW * max(1, count($sheets));
        $waste = $sheetArea > 0 ? (1 - ($used / $sheetArea)) * 100 : 0;

        $stmt = $pdo->prepare('INSERT INTO nesting_jobs (tenant_id, manufacturing_package_id, sheet_definition_id, sheet_length_mm, sheet_width_mm, kerf_mm, sheet_count, used_area, waste_percent, layout_json, locked_placements_json, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $layout = [
            'sheets' => $sheets,
            'sheet' => $sheet,
            'margin_mm' => $margin,
            'utilization_percent' => round(100 - $waste, 2),
        ];
        $stmt->execute([
            $tenantId, $packageId, (int) $sheet['id'], $sheetL, $sheetW, $kerf, count($sheets), $used, round($waste, 4),
            json_encode($layout), json_encode($lockedPlacements), 'COMPLETED',
        ]);
        $jobId = (int) $pdo->lastInsertId();
        Audit::record('NEST', 'nesting_job', $jobId);
        return $this->getNest($tenantId, $jobId);
    }

    public function updateNestPlacement(int $tenantId, int $nestId, array $placement): array
    {
        $nest = $this->getNest($tenantId, $nestId);
        $layout = $nest['layout'];
        $sheetL = (float) $nest['sheet_length_mm'];
        $sheetW = (float) $nest['sheet_width_mm'];
        $x = (float) $placement['x'];
        $y = (float) $placement['y'];
        $l = (float) $placement['length_mm'];
        $w = (float) $placement['width_mm'];
        if ($x < 0 || $y < 0 || $x + $l > $sheetL || $y + $w > $sheetW) {
            throw new \RuntimeException('Placement out of sheet bounds');
        }
        $sheetIndex = (int) ($placement['sheet_index'] ?? 0);
        $panelId = (int) $placement['panel_id'];
        $instance = (int) ($placement['instance'] ?? 0);
        foreach ($layout['sheets'][$sheetIndex]['placements'] ?? [] as $other) {
            if ((int) $other['panel_id'] === $panelId && (int) ($other['instance'] ?? 0) === $instance) {
                continue;
            }
            if ($this->rectsOverlap($x, $y, $l, $w, (float) $other['x'], (float) $other['y'], (float) $other['length_mm'], (float) $other['width_mm'])) {
                throw new \RuntimeException('Placement overlaps another panel');
            }
        }
        foreach ($layout['sheets'] as $si => &$sheet) {
            foreach ($sheet['placements'] as &$p) {
                if ((int) $p['panel_id'] === $panelId && (int) ($p['instance'] ?? 0) === $instance) {
                    $p['x'] = $x;
                    $p['y'] = $y;
                    $p['length_mm'] = $l;
                    $p['width_mm'] = $w;
                    $p['locked'] = !empty($placement['locked']);
                    $p['sheet_index'] = $sheetIndex;
                    if ($si !== $sheetIndex) {
                        // move between sheets: leave in place update for MVP
                    }
                }
            }
        }
        unset($sheet, $p);
        $locked = [];
        foreach ($layout['sheets'] as $sheet) {
            foreach ($sheet['placements'] as $p) {
                if (!empty($p['locked'])) {
                    $locked[] = $p;
                }
            }
        }
        $pdo = Database::connection();
        $pdo->prepare('UPDATE nesting_jobs SET layout_json=?, locked_placements_json=?, status=? WHERE id=? AND tenant_id=?')
            ->execute([json_encode($layout), json_encode($locked), 'MANUAL', $nestId, $tenantId]);
        Audit::record('UPDATE', 'nesting_job', $nestId, null, $placement);
        return $this->getNest($tenantId, $nestId);
    }

    public function renestPreservingLocks(int $tenantId, int $nestId): array
    {
        $nest = $this->getNest($tenantId, $nestId);
        $locked = json_decode($nest['locked_placements_json'] ?? '[]', true) ?: ($nest['locked_placements'] ?? []);
        if (!is_array($locked)) {
            $locked = [];
        }
        // If getNest already decoded
        if (isset($nest['locked_placements']) && is_array($nest['locked_placements'])) {
            $locked = $nest['locked_placements'];
        }
        return $this->nest($tenantId, (int) $nest['manufacturing_package_id'], $locked);
    }

    private function rectsOverlap(float $x1, float $y1, float $w1, float $h1, float $x2, float $y2, float $w2, float $h2): bool
    {
        return !($x1 + $w1 <= $x2 || $x2 + $w2 <= $x1 || $y1 + $h1 <= $y2 || $y2 + $h2 <= $y1);
    }

    public function labels(int $tenantId, int $packageId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT public_id, name, finishing_length_mm, finishing_width_mm, cutting_length_mm, cutting_width_mm, thickness_mm, quantity, status, finish_name FROM panels WHERE manufacturing_package_id=? AND tenant_id=?');
        $stmt->execute([$packageId, $tenantId]);
        return array_map(static function ($p) {
            return [
                'public_id' => $p['public_id'],
                'label_payload' => $p['public_id'],
                'title' => $p['name'],
                'dims' => ($p['cutting_length_mm'] ?? '') . 'x' . ($p['cutting_width_mm'] ?? '') . 'x' . $p['thickness_mm'],
                'finishing' => ($p['finishing_length_mm'] ?? '') . 'x' . ($p['finishing_width_mm'] ?? ''),
                'colour' => $p['finish_name'],
                'qty' => $p['quantity'],
                'status' => $p['status'],
            ];
        }, $stmt->fetchAll());
    }

    public function getPackage(int $tenantId, int $id): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM manufacturing_packages WHERE id=? AND tenant_id=?');
        $stmt->execute([$id, $tenantId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new \RuntimeException('Manufacturing package not found');
        }
        $row['validation'] = json_decode($row['validation_json'] ?? '{}', true);
        $row['snapshot'] = json_decode($row['snapshot_json'] ?? '{}', true);
        unset($row['validation_json'], $row['snapshot_json']);
        $stmt = $pdo->prepare('SELECT * FROM panels WHERE manufacturing_package_id=?');
        $stmt->execute([$id]);
        $row['panels'] = $stmt->fetchAll();
        $stmt = $pdo->prepare('SELECT * FROM cutlist_items WHERE manufacturing_package_id=?');
        $stmt->execute([$id]);
        $row['cutlist'] = $stmt->fetchAll();
        return $row;
    }

    public function getJob(int $tenantId, int $id): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM manufacturing_jobs WHERE id=? AND tenant_id=?');
        $stmt->execute([$id, $tenantId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new \RuntimeException('Manufacturing job not found');
        }
        $row['validation'] = json_decode($row['validation_json'] ?? '{}', true);
        $row['snapshot'] = json_decode($row['snapshot_json'] ?? '{}', true);
        unset($row['validation_json'], $row['snapshot_json']);
        $stmt = $pdo->prepare('SELECT * FROM manufacturing_job_furniture WHERE manufacturing_job_id=?');
        $stmt->execute([$id]);
        $row['furniture'] = $stmt->fetchAll();
        return $row;
    }

    public function getNest(int $tenantId, int $id): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM nesting_jobs WHERE id=? AND tenant_id=?');
        $stmt->execute([$id, $tenantId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new \RuntimeException('Nesting job not found');
        }
        $row['layout'] = json_decode($row['layout_json'], true);
        $row['locked_placements'] = json_decode($row['locked_placements_json'] ?? '[]', true) ?: [];
        unset($row['layout_json'], $row['locked_placements_json']);
        return $row;
    }

    public function defaultSheet(int $tenantId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare("SELECT * FROM sheet_definitions WHERE tenant_id=? AND status='ACTIVE' ORDER BY is_default DESC, id ASC LIMIT 1");
        $stmt->execute([$tenantId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new \RuntimeException('No sheet_definitions configured for tenant');
        }
        return $row;
    }

    private function sheetById(int $tenantId, ?int $id): ?array
    {
        if (!$id) {
            return null;
        }
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM sheet_definitions WHERE id=? AND tenant_id=?');
        $stmt->execute([$id, $tenantId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function defaultEdges(int $tenantId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare("SELECT rule_value_json FROM tenant_manufacturing_rules WHERE tenant_id=? AND rule_key='default_edges' AND is_active=1 LIMIT 1");
        $stmt->execute([$tenantId]);
        $json = $stmt->fetchColumn();
        return $json ? (json_decode((string) $json, true) ?: []) : ['edge_1' => 0.8, 'edge_2' => 0.8, 'edge_3' => 0.8, 'edge_4' => 0.8, 'apply_to_thickness_gte_mm' => 12];
    }

    private function cuttingRule(int $tenantId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare("SELECT rule_value_json FROM tenant_manufacturing_rules WHERE tenant_id=? AND rule_key='cutting_size' AND is_active=1 LIMIT 1");
        $stmt->execute([$tenantId]);
        $json = $stmt->fetchColumn();
        return $json ? (json_decode((string) $json, true) ?: []) : ['mode' => 'sum_opposite_edges', 'rounding_decimals' => 1];
    }
}
