<?php

declare(strict_types=1);

namespace Fmos\Domains\Manufacturing;

use Fmos\Core\Audit;
use Fmos\Core\Auth;
use Fmos\Core\Database;
use Fmos\Domains\Catalog\MaterialService;
use Fmos\Domains\Furniture\FurnitureEngine;

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

        foreach ($furniture['parameters'] as $k => $v) {
            if (!is_numeric($v)) {
                $issues[] = ['severity' => 'ERROR', 'code' => 'PARAM', 'message' => "{$k} invalid"];
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
        $hasBlocker = (bool) array_filter($issues, static fn ($i) => $i['severity'] === 'BLOCKER');
        return [
            'furniture_id' => $furnitureId,
            'ok' => !$hasBlocker,
            'issues' => $issues,
            'sheet' => $sheet,
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

            $compFinishId = $c['finish_id'] ?? $finishId;
            $compFinishName = $finishName;
            if (!empty($c['finish_id'])) {
                try {
                    $compFinishName = $matSvc->get($tenantId, (int) $c['finish_id'])['sku'];
                } catch (\Throwable) {
                }
            }

            $publicId = sprintf('P-%d-%d-R%d-%d', $projectId, $furnitureId, $rev, $panelSeq);
            $note = $c['manufacturing_data']['note'] ?? null;
            $stmt = $pdo->prepare('INSERT INTO panels (
                tenant_id, project_id, manufacturing_package_id, furniture_id, component_id, public_id, name, material_name, material_id, finish_id, finish_name,
                thickness_mm, length_mm, width_mm, finishing_length_mm, finishing_width_mm, cutting_length_mm, cutting_width_mm,
                quantity, grain_direction, edge_json, edge_1, edge_2, edge_3, edge_4, note, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            $stmt->execute([
                $tenantId, $projectId, $pkgId, $furnitureId, (int) $c['id'], $publicId, $c['name'], 'Board',
                $compFinishId, $compFinishName,
                $c['thickness_mm'], $cutL, $cutW, $finL, $finW, $cutL, $cutW,
                $c['quantity'], 'LENGTH',
                json_encode(['1' => $e1, '2' => $e2, '3' => $e3, '4' => $e4]),
                $e1, $e2, $e3, $e4, $note, 'CREATED',
            ]);
            $panelId = (int) $pdo->lastInsertId();
            $stmt = $pdo->prepare('INSERT INTO cutlist_items (
                tenant_id, manufacturing_package_id, panel_id, description, length_mm, width_mm, thickness_mm, quantity, material_name,
                finishing_length_mm, finishing_width_mm, cutting_length_mm, cutting_width_mm, colour, edgeband_color,
                edge_1, edge_2, edge_3, edge_4, note, rotate_flag
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)');
            $stmt->execute([
                $tenantId, $pkgId, $panelId, $c['name'], $cutL, $cutW, $c['thickness_mm'], $c['quantity'], 'Board',
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
        if ($scope === 'furniture' && $pkg['furniture_id']) {
            // already package-scoped to one furniture
        }
        return [
            'package_id' => $packageId,
            'furniture_id' => $pkg['furniture_id'],
            'items' => $items,
            'columns' => ['description', 'finishing_length_mm', 'finishing_width_mm', 'cutting_length_mm', 'cutting_width_mm', 'thickness_mm', 'quantity', 'material_name', 'colour', 'edge_1', 'edge_2', 'edge_3', 'edge_4', 'note'],
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
