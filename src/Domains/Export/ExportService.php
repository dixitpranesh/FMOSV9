<?php

declare(strict_types=1);

namespace Fmos\Domains\Export;

use Fmos\Core\Audit;
use Fmos\Domains\Furniture\FurnitureViewService;
use Fmos\Domains\Manufacturing\ManufacturingService;
use Fmos\Support\TextNormalizer;

final class ExportService
{
    public function manufacturingPackageCsv(int $tenantId, int $packageId): array
    {
        return $this->manufacturingPackagesCsv($tenantId, [$packageId]);
    }

    /**
     * Combined cutlist CSV for one or more manufacturing packages (e.g. full job).
     *
     * @param list<int> $packageIds
     */
    public function manufacturingPackagesCsv(int $tenantId, array $packageIds): array
    {
        $packageIds = array_values(array_unique(array_filter(array_map('intval', $packageIds))));
        if ($packageIds === []) {
            throw new \RuntimeException('package_ids required');
        }
        $mfg = new ManufacturingService();
        $cols = ['SL', 'FURNITURE_CODE', 'FURNITURE_NAME', 'DESCRIPTION', 'FINISH_L', 'FINISH_W', 'CUT_L', 'CUT_W', 'THICKNESS', 'QTY', 'MATERIAL', 'COLOUR', 'E1', 'E2', 'E3', 'E4', 'NOTE', 'EXPO', 'FACE_EXT', 'FACE_INT'];
        $lines = [implode(',', $cols)];
        $sl = 0;
        $codes = [];
        foreach ($packageIds as $packageId) {
            $cut = $mfg->cutlist($tenantId, $packageId);
            $code = TextNormalizer::ascii((string) ($cut['furniture_code'] ?? ('PKG-' . $packageId)));
            $name = TextNormalizer::ascii((string) ($cut['furniture_name'] ?? ''));
            $codes[] = $code;
            foreach ($cut['items'] as $row) {
                $sl++;
                $lines[] = implode(',', [
                    $sl,
                    $this->csv($code),
                    $this->csv($name),
                    $this->csv(TextNormalizer::ascii((string) ($row['description'] ?? ''))),
                    $row['finishing_length_mm'] ?? '',
                    $row['finishing_width_mm'] ?? '',
                    $row['cutting_length_mm'] ?? '',
                    $row['cutting_width_mm'] ?? '',
                    $row['thickness_mm'] ?? '',
                    $row['quantity'] ?? '',
                    $this->csv(TextNormalizer::ascii((string) ($row['material_name'] ?? ''))),
                    $this->csv(TextNormalizer::ascii((string) ($row['colour'] ?? ''))),
                    $row['edge_1'] ?? '',
                    $row['edge_2'] ?? '',
                    $row['edge_3'] ?? '',
                    $row['edge_4'] ?? '',
                    $this->csv(TextNormalizer::ascii((string) ($row['note'] ?? ''))),
                    !empty($row['expo']) ? 'YES' : 'NO',
                    $this->csv(TextNormalizer::ascii((string) ($row['face_exterior_finish'] ?? ''))),
                    $this->csv(TextNormalizer::ascii((string) ($row['face_interior_finish'] ?? ''))),
                ]);
            }
            foreach ($cut['hardware'] ?? [] as $row) {
                $sl++;
                $desc = (string) ($row['description'] ?? '');
                $sku = (string) ($row['sku'] ?? '');
                if ($sku !== '' && !str_contains($desc, $sku)) {
                    $desc = $sku . ' — ' . $desc;
                }
                $lines[] = implode(',', [
                    $sl,
                    $this->csv($code),
                    $this->csv($name),
                    $this->csv(TextNormalizer::ascii($desc)),
                    '',
                    '',
                    '',
                    '',
                    '',
                    $row['quantity'] ?? '',
                    $this->csv(TextNormalizer::ascii($sku !== '' ? $sku : 'Hardware')),
                    '',
                    '',
                    '',
                    '',
                    '',
                    $this->csv(TextNormalizer::ascii((string) ($row['note'] ?? 'hardware'))),
                    'NO',
                    '',
                    '',
                ]);
            }
        }
        // UTF-8 BOM so Excel shows labels correctly
        $csv = "\xEF\xBB\xBF" . implode("\n", $lines);
        $dir = dirname(__DIR__, 3) . '/storage/tenants/' . $tenantId . '/exports';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $tag = count($packageIds) === 1 ? ('pkg-' . $packageIds[0]) : ('job-' . implode('-', $packageIds));
        $file = 'cutlist-' . $tag . '-' . time() . '.csv';
        $path = $dir . '/' . $file;
        file_put_contents($path, $csv);
        Audit::record('EXPORT', 'cutlist_csv', $packageIds[0], null, ['file' => $file, 'package_ids' => $packageIds, 'furniture' => $codes]);
        return [
            'filename' => $file,
            'path' => 'storage/tenants/' . $tenantId . '/exports/' . $file,
            'mime' => 'text/csv',
            'content' => $csv,
            'package_ids' => $packageIds,
            'furniture_codes' => $codes,
            'row_count' => $sl,
        ];
    }

    public function manufacturingJobCsv(int $tenantId, int $jobId): array
    {
        $job = (new ManufacturingService())->getJob($tenantId, $jobId);
        $packageIds = array_map(
            static fn (array $f): int => (int) $f['manufacturing_package_id'],
            $job['furniture'] ?? []
        );
        return $this->manufacturingPackagesCsv($tenantId, $packageIds);
    }

    public function designHtml(int $tenantId, int $furnitureId, string $view = 'FRONT'): array
    {
        $draw = (new FurnitureViewService())->drawing2d($tenantId, $furnitureId, $view);
        $tb = $draw['title_block'];
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>'
            . htmlspecialchars((string) $tb['furniture']) . '</title>'
            . '<style>body{font-family:Segoe UI,Arial,sans-serif;padding:24px}svg{border:1px solid #ccc;background:#fff}.tb{margin-top:12px;font-size:12px}</style>'
            . '</head><body>';
        $html .= '<h1>' . htmlspecialchars((string) $tb['furniture']) . ' — ' . htmlspecialchars($view) . '</h1>';
        $html .= '<div class="tb">Project: ' . htmlspecialchars((string) $tb['project'])
            . ' | Client: ' . htmlspecialchars((string) $tb['client'])
            . ' | Code: ' . htmlspecialchars((string) ($tb['code'] ?? ''))
            . ' | Rev: ' . (int) $tb['revision'] . '</div>';
        $bw = max(1.0, (float) $draw['bounds']['width']);
        $bh = max(1.0, (float) ($view === 'PLAN' ? $draw['bounds']['depth'] : $draw['bounds']['height']));
        $minX = 0.0;
        $minY = 0.0;
        $maxX = $bw;
        $maxY = $bh;
        foreach ($draw['dimensions'] as $dim) {
            $minX = min($minX, (float) $dim['from'][0], (float) $dim['to'][0]);
            $maxX = max($maxX, (float) $dim['from'][0], (float) $dim['to'][0]);
            $minY = min($minY, (float) $dim['from'][1], (float) $dim['to'][1]);
            $maxY = max($maxY, (float) $dim['from'][1], (float) $dim['to'][1]);
        }
        $pad = max(140.0, $bw * 0.08);
        $minX -= $pad * 0.45;
        $maxX += $pad;
        $minY -= $pad * 0.35;
        $maxY += 40.0;
        $contentW = max(1.0, $maxX - $minX);
        $contentH = max(1.0, $maxY - $minY);
        $scale = min(900 / $contentW, 620 / $contentH);
        $svgW = (int) ($contentW * $scale + 24);
        $svgH = (int) ($contentH * $scale + 24);
        $sw = 1.5 / $scale;
        $html .= '<svg width="' . $svgW . '" height="' . $svgH . '" viewBox="0 0 ' . $svgW . ' ' . $svgH . '">';
        $html .= '<g transform="translate(' . (12 - $minX * $scale) . ',' . (12 - $minY * $scale) . ') scale(' . $scale . ')">';
        $skipLabel = ['outer' => true, 'inner' => true, 'bay' => true, 'expo' => true, 'carcass' => true];
        $sideStacks = ['left' => 0, 'right' => 0, 'top' => 0, 'bottom' => 0];
        foreach ($draw['elements'] as $el) {
            $type = (string) ($el['type'] ?? '');
            if ($type === 'rect') {
                $role = (string) ($el['role'] ?? '');
                $fill = $role === 'expo' ? 'rgba(30,136,229,0.18)' : 'none';
                $stroke = $role === 'expo' ? '#1565c0' : '#222';
                $dash = $role === 'expo' ? ' stroke-dasharray="' . (6 / $scale) . ' ' . (4 / $scale) . '"' : '';
                $html .= '<rect x="' . $el['x'] . '" y="' . $el['y'] . '" width="' . $el['w'] . '" height="' . $el['h']
                    . '" fill="' . $fill . '" stroke="' . $stroke . '" stroke-width="' . ($role === 'expo' ? 2.4 / $scale : $sw) . '"' . $dash . '/>';
                if (!empty($el['label']) && empty($skipLabel[$role]) && (float) $el['w'] > 80 && (float) $el['h'] > 40) {
                    $html .= '<text x="' . ((float) $el['x'] + 8) . '" y="' . ((float) $el['y'] + 22)
                        . '" font-size="' . (13 / $scale) . '" fill="#334">' . htmlspecialchars((string) $el['label']) . '</text>';
                }
            } elseif ($type === 'line') {
                $html .= '<line x1="' . $el['x1'] . '" y1="' . $el['y1'] . '" x2="' . $el['x2'] . '" y2="' . $el['y2']
                    . '" stroke="' . (!empty($el['expo']) ? '#1565c0' : '#666') . '" stroke-width="' . (!empty($el['expo']) ? 2.4 / $scale : $sw) . '"/>';
            } elseif ($type === 'callout' || (($el['role'] ?? '') === 'expo-label')) {
                $side = (string) ($el['side'] ?? 'right');
                $stack = $sideStacks[$side] ?? 0;
                $sideStacks[$side] = $stack + 1;
                $ax = (float) ($el['anchor_x'] ?? $el['x'] ?? 0);
                $ay = (float) ($el['anchor_y'] ?? $el['y'] ?? 0);
                $gap = 36 + $stack * 22;
                $lx = $ax;
                $ly = $ay;
                if ($side === 'left') {
                    $lx = $ax - $gap - 40;
                    $ly = $ay + $stack * 18;
                } elseif ($side === 'right') {
                    $lx = $ax + $gap + 10;
                    $ly = $ay + $stack * 18;
                } elseif ($side === 'top') {
                    $lx = $ax + 10 + $stack * 14;
                    $ly = $ay - $gap;
                } else {
                    $lx = $ax + 10 + $stack * 14;
                    $ly = $ay + $gap;
                }
                $html .= '<circle cx="' . $ax . '" cy="' . $ay . '" r="' . (2.4 / $scale) . '" fill="#0d47a1"/>';
                $html .= '<line x1="' . $ax . '" y1="' . $ay . '" x2="' . $lx . '" y2="' . $ly
                    . '" stroke="#1565c0" stroke-width="' . $sw . '"/>';
                $html .= '<text x="' . $lx . '" y="' . $ly . '" font-size="' . (12 / $scale)
                    . '" font-weight="700" fill="#0d47a1">' . htmlspecialchars((string) ($el['text'] ?? 'EXPO')) . '</text>';
            }
        }
        foreach ($draw['dimensions'] as $dim) {
            $x1 = (float) $dim['from'][0];
            $y1 = (float) $dim['from'][1];
            $x2 = (float) $dim['to'][0];
            $y2 = (float) $dim['to'][1];
            $horizontal = (($dim['axis'] ?? '') === 'H') || abs($y2 - $y1) < abs($x2 - $x1);
            $html .= '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2
                . '" stroke="#c62828" stroke-width="' . $sw . '"/>';
            $tick = 8 / $scale;
            if ($horizontal) {
                $html .= '<line x1="' . $x1 . '" y1="' . ($y1 - $tick) . '" x2="' . $x1 . '" y2="' . ($y1 + $tick)
                    . '" stroke="#c62828" stroke-width="' . $sw . '"/>';
                $html .= '<line x1="' . $x2 . '" y1="' . ($y2 - $tick) . '" x2="' . $x2 . '" y2="' . ($y2 + $tick)
                    . '" stroke="#c62828" stroke-width="' . $sw . '"/>';
                $mx = ($x1 + $x2) / 2;
                $my = $y1 - (10 / $scale);
                $html .= '<text x="' . $mx . '" y="' . $my . '" text-anchor="middle" font-size="' . (13 / $scale)
                    . '" fill="#c62828">' . htmlspecialchars((string) $dim['label']) . '</text>';
            } else {
                $html .= '<line x1="' . ($x1 - $tick) . '" y1="' . $y1 . '" x2="' . ($x1 + $tick) . '" y2="' . $y1
                    . '" stroke="#c62828" stroke-width="' . $sw . '"/>';
                $html .= '<line x1="' . ($x2 - $tick) . '" y1="' . $y2 . '" x2="' . ($x2 + $tick) . '" y2="' . $y2
                    . '" stroke="#c62828" stroke-width="' . $sw . '"/>';
                $outsideRight = (($x1 + $x2) / 2) > ($bw * 0.5);
                $mx = $outsideRight ? $x1 + (14 / $scale) : $x1 - (10 / $scale);
                $my = ($y1 + $y2) / 2;
                $html .= '<text x="' . $mx . '" y="' . $my . '" text-anchor="middle" font-size="' . (13 / $scale)
                    . '" fill="#c62828" transform="rotate(-90 ' . $mx . ' ' . $my . ')">'
                    . htmlspecialchars((string) $dim['label']) . '</text>';
            }
        }
        $html .= '</g></svg></body></html>';

        $dir = dirname(__DIR__, 3) . '/storage/tenants/' . $tenantId . '/exports';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $name = 'design-f' . $furnitureId . '-' . strtolower($view) . '-' . time() . '.html';
        file_put_contents($dir . '/' . $name, $html);
        Audit::record('EXPORT', 'design_html', $furnitureId, null, ['file' => $name]);
        return ['filename' => $name, 'path' => 'storage/tenants/' . $tenantId . '/exports/' . $name, 'mime' => 'text/html', 'content' => $html];
    }

    private function csv(string $v): string
    {
        $v = TextNormalizer::ascii($v);
        return '"' . str_replace('"', '""', $v) . '"';
    }
}
