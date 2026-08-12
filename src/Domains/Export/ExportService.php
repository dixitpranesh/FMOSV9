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
        $cols = ['SL', 'FURNITURE_CODE', 'FURNITURE_NAME', 'DESCRIPTION', 'FINISH_L', 'FINISH_W', 'CUT_L', 'CUT_W', 'THICKNESS', 'QTY', 'MATERIAL', 'COLOUR', 'E1', 'E2', 'E3', 'E4', 'NOTE'];
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
                ]);
            }
            foreach ($cut['hardware'] ?? [] as $row) {
                $sl++;
                $lines[] = implode(',', [
                    $sl,
                    $this->csv($code),
                    $this->csv($name),
                    $this->csv(TextNormalizer::ascii((string) ($row['description'] ?? ''))),
                    '',
                    '',
                    '',
                    '',
                    '',
                    $row['quantity'] ?? '',
                    $this->csv('Hardware'),
                    '',
                    '',
                    '',
                    '',
                    '',
                    $this->csv(TextNormalizer::ascii((string) ($row['note'] ?? 'hardware'))),
                ]);
            }
        }
        // UTF-8 BOM so Excel shows labels correctly
        $csv = "\xEF\xBB\xBF" . implode("\n", $lines);
        $dir = dirname(__DIR__, 3) . '/storage/exports';
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
            'path' => 'storage/exports/' . $file,
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
        $bw = max(1, (float) $draw['bounds']['width']);
        $bh = max(1, (float) ($view === 'PLAN' ? $draw['bounds']['depth'] : $draw['bounds']['height']));
        $scale = min(800 / $bw, 500 / $bh);
        $html .= '<svg width="' . (int) ($bw * $scale + 80) . '" height="' . (int) ($bh * $scale + 80) . '">';
        $html .= '<g transform="translate(40,40) scale(' . $scale . ')">';
        foreach ($draw['elements'] as $el) {
            if ($el['type'] === 'rect') {
                $html .= '<rect x="' . $el['x'] . '" y="' . $el['y'] . '" width="' . $el['w'] . '" height="' . $el['h']
                    . '" fill="none" stroke="#222" stroke-width="' . (2 / $scale) . '"/>';
            } elseif ($el['type'] === 'line') {
                $html .= '<line x1="' . $el['x1'] . '" y1="' . $el['y1'] . '" x2="' . $el['x2'] . '" y2="' . $el['y2']
                    . '" stroke="#666" stroke-width="' . (1.5 / $scale) . '"/>';
            }
        }
        foreach ($draw['dimensions'] as $dim) {
            $html .= '<text x="' . (($dim['from'][0] + $dim['to'][0]) / 2) . '" y="' . (($dim['from'][1] + $dim['to'][1]) / 2)
                . '" font-size="' . (14 / $scale) . '" fill="#c00">' . htmlspecialchars((string) $dim['label']) . '</text>';
        }
        $html .= '</g></svg></body></html>';

        $dir = dirname(__DIR__, 3) . '/storage/exports';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $name = 'design-f' . $furnitureId . '-' . strtolower($view) . '-' . time() . '.html';
        file_put_contents($dir . '/' . $name, $html);
        Audit::record('EXPORT', 'design_html', $furnitureId, null, ['file' => $name]);
        return ['filename' => $name, 'path' => 'storage/exports/' . $name, 'mime' => 'text/html', 'content' => $html];
    }

    private function csv(string $v): string
    {
        $v = TextNormalizer::ascii($v);
        return '"' . str_replace('"', '""', $v) . '"';
    }
}
