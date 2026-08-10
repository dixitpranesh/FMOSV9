<?php

declare(strict_types=1);

namespace Fmos\Domains\Manufacturing;

use Fmos\Core\Audit;
use Fmos\Core\Auth;
use Fmos\Core\Database;
use Fmos\Domains\Furniture\FurnitureEngine;

final class ManufacturingService
{
    public function validateAndGenerate(int $tenantId, int $projectId, int $furnitureId): array
    {
        $engine = new FurnitureEngine();
        // Rebuild sheet-fit components from current params (fixes oversized Top/Bottom/Shelf/Shutter)
        $furniture = $engine->refreshComponents($tenantId, $furnitureId);
        $issues = [];
        foreach ($furniture['parameters'] as $k => $v) {
            if (!is_numeric($v)) {
                $issues[] = ['severity' => 'ERROR', 'code' => 'PARAM', 'message' => "{$k} invalid"];
            }
        }
        foreach ($furniture['components'] as $c) {
            if (($c['type'] ?? '') === 'HARDWARE') {
                continue;
            }
            $l = (float) $c['length_mm'];
            $w = (float) $c['width_mm'];
            $fits = ($l <= 2440 && $w <= 1220) || ($w <= 2440 && $l <= 1220);
            if (!$fits) {
                $issues[] = ['severity' => 'BLOCKER', 'code' => 'PANEL_SIZE', 'message' => $c['name'] . ' exceeds sheet even with rotation'];
            } elseif (($l > 2440 || $w > 1220) || ($w > 2440 || $l > 1220)) {
                $issues[] = ['severity' => 'WARNING', 'code' => 'PANEL_ROTATE', 'message' => $c['name'] . ' requires rotation to fit sheet'];
            }
            if (!empty($c['note'])) {
                $issues[] = ['severity' => 'INFO', 'code' => 'PANEL_SPLIT', 'message' => $c['name'] . ': ' . $c['note']];
            }
        }
        $hasBlocker = (bool) array_filter($issues, static fn ($i) => $i['severity'] === 'BLOCKER');

        $pdo = Database::connection();
        $rev = 1;
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(revision_number),0)+1 FROM manufacturing_packages WHERE tenant_id=? AND project_id=? AND furniture_id=?');
        $stmt->execute([$tenantId, $projectId, $furnitureId]);
        $rev = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare('INSERT INTO manufacturing_packages (tenant_id, project_id, furniture_id, revision_number, status, validation_json, snapshot_json, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $snapshot = ['furniture' => $furniture, 'generated_at' => date('c')];
        $stmt->execute([
            $tenantId,
            $projectId,
            $furnitureId,
            $rev,
            $hasBlocker ? 'BLOCKED' : 'READY',
            json_encode(['issues' => $issues]),
            json_encode($snapshot),
        ]);
        $pkgId = (int) $pdo->lastInsertId();

        $panels = [];
        if (!$hasBlocker) {
            $panelSeq = 0;
            foreach ($furniture['components'] as $idx => $c) {
                if (($c['type'] ?? '') === 'HARDWARE') {
                    continue;
                }
                $panelSeq++;
                // Unique per package revision — regenerating same furniture must not collide
                $publicId = sprintf('P-%d-%d-R%d-%d', $projectId, $furnitureId, $rev, $panelSeq);
                $stmt = $pdo->prepare('INSERT INTO panels (tenant_id, project_id, manufacturing_package_id, furniture_id, public_id, name, material_name, thickness_mm, length_mm, width_mm, quantity, grain_direction, edge_json, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
                $stmt->execute([
                    $tenantId, $projectId, $pkgId, $furnitureId, $publicId, $c['name'], 'Board',
                    $c['thickness_mm'], $c['length_mm'], $c['width_mm'], $c['qty'], 'LENGTH',
                    json_encode(['TOP' => true, 'BOTTOM' => true, 'LEFT' => true, 'RIGHT' => true]),
                    'CREATED',
                ]);
                $panelId = (int) $pdo->lastInsertId();
                $stmt = $pdo->prepare('INSERT INTO cutlist_items (tenant_id, manufacturing_package_id, panel_id, description, length_mm, width_mm, thickness_mm, quantity, material_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$tenantId, $pkgId, $panelId, $c['name'], $c['length_mm'], $c['width_mm'], $c['thickness_mm'], $c['qty'], 'Board']);
                $panels[] = ['id' => $panelId, 'public_id' => $publicId, 'name' => $c['name']];
            }
        }

        Audit::record('GENERATE', 'manufacturing_package', $pkgId, null, ['issues' => $issues]);
        return $this->getPackage($tenantId, $pkgId);
    }

    public function release(int $tenantId, int $packageId): array
    {
        Auth::requirePermission('manufacturing.release');
        $pkg = $this->getPackage($tenantId, $packageId);
        $validation = $pkg['validation'];
        foreach ($validation['issues'] ?? [] as $issue) {
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

    public function nest(int $tenantId, int $packageId, float $sheetL = 2440, float $sheetW = 1220, float $kerf = 3): array
    {
        $pkg = $this->getPackage($tenantId, $packageId);
        if ($pkg['status'] !== 'RELEASED' && $pkg['status'] !== 'READY') {
            // Allow nesting on READY for MVP preview; prefer RELEASED
        }
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM panels WHERE manufacturing_package_id=? AND tenant_id=?');
        $stmt->execute([$packageId, $tenantId]);
        $panels = $stmt->fetchAll();

        // Basic shelf packing heuristic
        $sheets = [];
        $current = ['placements' => [], 'cursor_x' => 0.0, 'cursor_y' => 0.0, 'row_h' => 0.0];
        $used = 0.0;
        foreach ($panels as $panel) {
            for ($q = 0; $q < (int) $panel['quantity']; $q++) {
                $l = (float) $panel['length_mm'];
                $w = (float) $panel['width_mm'];
                // rotate if helps
                if ($l > $sheetL && $w <= $sheetL) {
                    [$l, $w] = [$w, $l];
                }
                if ($current['cursor_x'] + $l > $sheetL) {
                    $current['cursor_x'] = 0;
                    $current['cursor_y'] += $current['row_h'] + $kerf;
                    $current['row_h'] = 0;
                }
                if ($current['cursor_y'] + $w > $sheetW) {
                    $sheets[] = $current;
                    $current = ['placements' => [], 'cursor_x' => 0.0, 'cursor_y' => 0.0, 'row_h' => 0.0];
                }
                $current['placements'][] = [
                    'panel_id' => (int) $panel['id'],
                    'public_id' => $panel['public_id'],
                    'x' => $current['cursor_x'],
                    'y' => $current['cursor_y'],
                    'length_mm' => $l,
                    'width_mm' => $w,
                ];
                $current['cursor_x'] += $l + $kerf;
                $current['row_h'] = max($current['row_h'], $w);
                $used += $l * $w;
                // QR canonical order: RELEASED before NESTED
                $pdo->prepare("UPDATE panels SET status='NESTED' WHERE id=? AND status IN ('RELEASED','CREATED','READY')")->execute([(int) $panel['id']]);
            }
        }
        if ($current['placements']) {
            $sheets[] = $current;
        }
        $sheetArea = $sheetL * $sheetW * max(1, count($sheets));
        $waste = $sheetArea > 0 ? (1 - ($used / $sheetArea)) * 100 : 0;

        $stmt = $pdo->prepare('INSERT INTO nesting_jobs (tenant_id, manufacturing_package_id, sheet_length_mm, sheet_width_mm, kerf_mm, sheet_count, used_area, waste_percent, layout_json, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $layout = ['sheets' => $sheets];
        $stmt->execute([$tenantId, $packageId, $sheetL, $sheetW, $kerf, count($sheets), $used, round($waste, 4), json_encode($layout), 'COMPLETED']);
        $jobId = (int) $pdo->lastInsertId();
        Audit::record('NEST', 'nesting_job', $jobId);
        return $this->getNest($tenantId, $jobId);
    }

    public function labels(int $tenantId, int $packageId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT public_id, name, length_mm, width_mm, thickness_mm, quantity, status FROM panels WHERE manufacturing_package_id=? AND tenant_id=?');
        $stmt->execute([$packageId, $tenantId]);
        $panels = $stmt->fetchAll();
        return array_map(static function ($p) {
            return [
                'public_id' => $p['public_id'],
                'label_payload' => $p['public_id'], // ID only per SRS
                'title' => $p['name'],
                'dims' => $p['length_mm'] . 'x' . $p['width_mm'] . 'x' . $p['thickness_mm'],
                'qty' => $p['quantity'],
                'status' => $p['status'],
            ];
        }, $panels);
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
        unset($row['layout_json']);
        return $row;
    }
}
