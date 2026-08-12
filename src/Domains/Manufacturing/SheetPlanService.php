<?php

declare(strict_types=1);

namespace Fmos\Domains\Manufacturing;

use Fmos\Core\Audit;
use Fmos\Core\Database;
use Fmos\Support\SimplePdf;
use Fmos\Support\TextNormalizer;

/**
 * Project-level sheet plans: nest all furniture panels grouped by laminate + thickness.
 */
final class SheetPlanService
{
    public function __construct(private readonly ManufacturingService $mfg = new ManufacturingService())
    {
    }

    /**
     * @param list<int> $packageIds
     * @return array<string,mixed>
     */
    public function buildProjectPlan(int $tenantId, int $projectId, array $packageIds = []): array
    {
        $packageIds = array_values(array_unique(array_filter(array_map('intval', $packageIds))));
        if ($packageIds === []) {
            $packageIds = $this->latestPackageIdsForProject($tenantId, $projectId);
        }
        if ($packageIds === []) {
            throw new \RuntimeException('No manufacturing packages found. Generate manufacturing for the project first.');
        }

        $sheet = $this->mfg->defaultSheet($tenantId);
        $sheetL = (float) $sheet['length_mm'];
        $sheetW = (float) $sheet['width_mm'];
        $kerf = (float) $sheet['kerf_mm'];
        $margin = (float) $sheet['margin_mm'];

        $pdo = Database::connection();
        $in = implode(',', array_fill(0, count($packageIds), '?'));
        $params = array_merge([$tenantId, $projectId], $packageIds);
        $stmt = $pdo->prepare("SELECT p.*, f.code AS furniture_code, f.name AS furniture_name
            FROM panels p
            LEFT JOIN furniture_instances f ON f.id = p.furniture_id
            WHERE p.tenant_id = ? AND p.project_id = ? AND p.manufacturing_package_id IN ($in)
            ORDER BY p.finish_name, p.thickness_mm, p.id");
        $stmt->execute($params);
        $panels = $stmt->fetchAll();
        if ($panels === []) {
            throw new \RuntimeException('No panels found for selected packages.');
        }

        $groupsMap = [];
        foreach ($panels as $panel) {
            $finishId = $panel['finish_id'] !== null ? (int) $panel['finish_id'] : 0;
            $finishName = trim((string) ($panel['finish_name'] ?? '')) ?: 'NO_LAMINATE';
            $thk = round((float) $panel['thickness_mm'], 2);
            $key = $finishId . '|' . $finishName . '|' . $thk;
            if (!isset($groupsMap[$key])) {
                $groupsMap[$key] = [
                    'key' => $key,
                    'finish_id' => $finishId ?: null,
                    'laminate' => $finishName,
                    'thickness_mm' => $thk,
                    'panels' => [],
                ];
            }
            $groupsMap[$key]['panels'][] = $panel;
        }

        $groups = [];
        $totalSheets = 0;
        $totalPlacements = 0;
        foreach ($groupsMap as $group) {
            $nested = $this->nestPanelGroup($group['panels'], $sheetL, $sheetW, $kerf, $margin);
            $group['sheet_count'] = count($nested['sheets']);
            $group['used_area'] = $nested['used_area'];
            $group['waste_percent'] = $nested['waste_percent'];
            $group['utilization_percent'] = $nested['utilization_percent'];
            $group['sheets'] = $nested['sheets'];
            unset($group['panels']);
            $groups[] = $group;
            $totalSheets += $group['sheet_count'];
            foreach ($group['sheets'] as $s) {
                $totalPlacements += count($s['placements']);
            }
        }

        usort($groups, static function (array $a, array $b): int {
            return [$a['laminate'], $a['thickness_mm']] <=> [$b['laminate'], $b['thickness_mm']];
        });

        $plan = [
            'project_id' => $projectId,
            'package_ids' => $packageIds,
            'sheet' => [
                'id' => (int) $sheet['id'],
                'code' => $sheet['code'],
                'name' => $sheet['name'],
                'length_mm' => $sheetL,
                'width_mm' => $sheetW,
                'kerf_mm' => $kerf,
                'margin_mm' => $margin,
            ],
            'groups' => $groups,
            'totals' => [
                'laminate_groups' => count($groups),
                'sheets' => $totalSheets,
                'panel_pieces' => $totalPlacements,
                'packages' => count($packageIds),
            ],
            'generated_at' => date('c'),
        ];

        // Persist nesting job against first package for audit/traceability
        $layout = [
            'mode' => 'PROJECT_BY_LAMINATE',
            'project_id' => $projectId,
            'package_ids' => $packageIds,
            'sheet' => $plan['sheet'],
            'groups' => $groups,
            'margin_mm' => $margin,
            'utilization_percent' => $groups[0]['utilization_percent'] ?? 0,
            'sheets' => $groups[0]['sheets'] ?? [], // keep legacy single-canvas preview compatible with first group
        ];
        $used = array_sum(array_map(static fn ($g) => (float) $g['used_area'], $groups));
        $wasteAvg = $totalSheets > 0
            ? array_sum(array_map(static fn ($g) => (float) $g['waste_percent'] * (int) $g['sheet_count'], $groups)) / $totalSheets
            : 0;
        $stmt = $pdo->prepare('INSERT INTO nesting_jobs (tenant_id, manufacturing_package_id, sheet_definition_id, sheet_length_mm, sheet_width_mm, kerf_mm, sheet_count, used_area, waste_percent, layout_json, locked_placements_json, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([
            $tenantId,
            $packageIds[0],
            (int) $sheet['id'],
            $sheetL,
            $sheetW,
            $kerf,
            $totalSheets,
            $used,
            round($wasteAvg, 4),
            json_encode($layout),
            json_encode([]),
            'COMPLETED',
        ]);
        $plan['nesting_job_id'] = (int) $pdo->lastInsertId();
        Audit::record('NEST', 'project_sheet_plan', $projectId, null, [
            'nesting_job_id' => $plan['nesting_job_id'],
            'package_ids' => $packageIds,
            'groups' => count($groups),
            'sheets' => $totalSheets,
        ]);
        return $plan;
    }

    /**
     * @param array<string,mixed> $plan
     * @return array{filename:string,path:string,mime:string,content:string}
     */
    public function renderPdf(array $plan, string $projectName = ''): array
    {
        $pdf = new SimplePdf();
        $pageW = 841.89; // A4 landscape
        $pageH = 595.28;
        $sheetL = (float) $plan['sheet']['length_mm'];
        $sheetW = (float) $plan['sheet']['width_mm'];
        $marginMm = (float) $plan['sheet']['margin_mm'];

        $title = $projectName !== '' ? $projectName : ('Project ' . ($plan['project_id'] ?? ''));
        $sheetIndexGlobal = 0;
        $totalSheets = (int) ($plan['totals']['sheets'] ?? 0);

        foreach ($plan['groups'] as $group) {
            $gSheets = $group['sheets'] ?? [];
            foreach ($gSheets as $si => $sheetLayout) {
                $sheetIndexGlobal++;
                $pdf->addPage($pageW, $pageH);
                $pdf->setFillColor(255, 255, 255);
                $pdf->setStrokeColor(28, 36, 48);
                $pdf->setLineWidth(0.8);

                $pdf->text(36, $pageH - 36, 'FMOS Sheet Plan', 14);
                $pdf->text(36, $pageH - 52, TextNormalizer::ascii($title), 11);
                $pdf->text(36, $pageH - 66, sprintf(
                    'Laminate: %s   |   Thickness: %s mm   |   Sheet %d of %d (group %d/%d)   |   Board %s (%sx%s)',
                    TextNormalizer::ascii((string) $group['laminate']),
                    $group['thickness_mm'],
                    $si + 1,
                    count($gSheets),
                    $sheetIndexGlobal,
                    $totalSheets,
                    $plan['sheet']['code'] ?? '',
                    (int) $sheetL,
                    (int) $sheetW
                ), 9);
                $pdf->text(36, $pageH - 80, sprintf(
                    'Group waste: %s%%   |   Utilization: %s%%   |   Packages: %s',
                    $group['waste_percent'],
                    $group['utilization_percent'],
                    implode(',', $plan['package_ids'] ?? [])
                ), 8);

                // Drawing area
                $drawX = 36.0;
                $drawY = 48.0;
                $drawW = $pageW - 72;
                $drawH = $pageH - 150;
                $scale = min($drawW / $sheetL, $drawH / $sheetW);
                $ox = $drawX;
                $oy = $drawY;

                // Sheet outline (PDF y grows upward)
                $pdf->setStrokeColor(28, 75, 143);
                $pdf->setLineWidth(1.4);
                $pdf->rect($ox, $oy, $sheetL * $scale, $sheetW * $scale, false, true);

                // Usable margin
                $pdf->setStrokeColor(150, 160, 170);
                $pdf->setLineWidth(0.6);
                $pdf->rect(
                    $ox + $marginMm * $scale,
                    $oy + $marginMm * $scale,
                    ($sheetL - 2 * $marginMm) * $scale,
                    ($sheetW - 2 * $marginMm) * $scale,
                    false,
                    true
                );

                foreach ($sheetLayout['placements'] as $p) {
                    $px = $ox + ((float) $p['x']) * $scale;
                    // Convert top-left nesting y to PDF bottom-left within sheet box
                    $py = $oy + ($sheetW - ((float) $p['y']) - ((float) $p['width_mm'])) * $scale;
                    $pw = ((float) $p['length_mm']) * $scale;
                    $ph = ((float) $p['width_mm']) * $scale;
                    $pdf->setFillColor(243, 230, 208);
                    $pdf->setStrokeColor(91, 70, 54);
                    $pdf->setLineWidth(0.7);
                    $pdf->rect($px, $py, $pw, $ph, true, true);
                    $label = (string) ($p['name'] ?? $p['public_id'] ?? '');
                    if (strlen($label) > 42) {
                        $label = substr($label, 0, 39) . '...';
                    }
                    $pdf->setFillColor(30, 30, 30);
                    $pdf->setStrokeColor(30, 30, 30);
                    if ($ph > 18 && $pw > 40) {
                        $pdf->text($px + 3, $py + $ph - 10, $label, 7);
                        $pdf->text(
                            $px + 3,
                            $py + $ph - 20,
                            sprintf('%sx%sx%s', (int) $p['length_mm'], (int) $p['width_mm'], (int) ($p['thickness_mm'] ?? 0)),
                            6
                        );
                        if (!empty($p['furniture_code'])) {
                            $pdf->text($px + 3, $py + 4, (string) $p['furniture_code'], 6);
                        }
                    }
                }

                $pdf->setStrokeColor(28, 36, 48);
                $pdf->text(36, 28, sprintf(
                    'Generated %s  |  FMOS project sheet plan by laminate',
                    $plan['generated_at'] ?? date('c')
                ), 8);
            }
        }

        if ($sheetIndexGlobal === 0) {
            $pdf->addPage($pageW, $pageH);
            $pdf->text(36, $pageH - 50, 'No sheets to print', 14);
        }

        $binary = $pdf->output();
        $dir = dirname(__DIR__, 3) . '/storage/exports';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $filename = 'sheet-plan-project-' . ($plan['project_id'] ?? 'x') . '-' . time() . '.pdf';
        $path = $dir . '/' . $filename;
        file_put_contents($path, $binary);
        Audit::record('EXPORT', 'sheet_plan_pdf', (int) ($plan['project_id'] ?? 0), null, ['file' => $filename]);

        return [
            'filename' => $filename,
            'path' => 'storage/exports/' . $filename,
            'mime' => 'application/pdf',
            'content_base64' => base64_encode($binary),
            'sheet_count' => $sheetIndexGlobal,
            'laminate_groups' => count($plan['groups'] ?? []),
        ];
    }

    /**
     * @return list<int>
     */
    private function latestPackageIdsForProject(int $tenantId, int $projectId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id FROM manufacturing_jobs WHERE tenant_id=? AND project_id=? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$tenantId, $projectId]);
        $jobId = (int) $stmt->fetchColumn();
        if ($jobId > 0) {
            $stmt = $pdo->prepare('SELECT manufacturing_package_id FROM manufacturing_job_furniture WHERE manufacturing_job_id=?');
            $stmt->execute([$jobId]);
            $ids = array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
            if ($ids !== []) {
                return $ids;
            }
        }
        $stmt = $pdo->prepare('SELECT id FROM manufacturing_packages WHERE tenant_id=? AND project_id=? ORDER BY id DESC');
        $stmt->execute([$tenantId, $projectId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    /**
     * @param list<array<string,mixed>> $panels
     * @return array{sheets:list<array<string,mixed>>,used_area:float,waste_percent:float,utilization_percent:float}
     */
    private function nestPanelGroup(array $panels, float $sheetL, float $sheetW, float $kerf, float $margin): array
    {
        $usableL = $sheetL - (2 * $margin);
        $usableW = $sheetW - (2 * $margin);
        $sheets = [];
        $current = ['placements' => [], 'cursor_x' => 0.0, 'cursor_y' => 0.0, 'row_h' => 0.0];
        $used = 0.0;

        // Larger panels first for denser packing
        usort($panels, static function (array $a, array $b): int {
            $aa = ((float) ($a['cutting_length_mm'] ?? $a['length_mm'])) * ((float) ($a['cutting_width_mm'] ?? $a['width_mm']));
            $bb = ((float) ($b['cutting_length_mm'] ?? $b['length_mm'])) * ((float) ($b['cutting_width_mm'] ?? $b['width_mm']));
            return $bb <=> $aa;
        });

        foreach ($panels as $panel) {
            for ($q = 0; $q < (int) $panel['quantity']; $q++) {
                $l = (float) ($panel['cutting_length_mm'] ?? $panel['length_mm']);
                $w = (float) ($panel['cutting_width_mm'] ?? $panel['width_mm']);
                $rotated = false;
                if ($l > $usableL && $w <= $usableL && $l <= $usableW) {
                    [$l, $w] = [$w, $l];
                    $rotated = true;
                } elseif ($w > $usableW && $l <= $usableW && $w <= $usableL) {
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
                    'name' => TextNormalizer::ascii((string) $panel['name']),
                    'furniture_code' => TextNormalizer::ascii((string) ($panel['furniture_code'] ?? '')),
                    'furniture_name' => TextNormalizer::ascii((string) ($panel['furniture_name'] ?? '')),
                    'finish_name' => TextNormalizer::ascii((string) ($panel['finish_name'] ?? '')),
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
            }
        }
        if ($current['placements']) {
            $sheets[] = $current;
        }
        $sheetArea = $usableL * $usableW * max(1, count($sheets));
        $waste = $sheetArea > 0 ? (1 - ($used / $sheetArea)) * 100 : 0;
        return [
            'sheets' => $sheets,
            'used_area' => $used,
            'waste_percent' => round($waste, 4),
            'utilization_percent' => round(100 - $waste, 2),
        ];
    }
}
