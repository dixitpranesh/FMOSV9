<?php

declare(strict_types=1);

namespace Fmos\Domains\Furniture;

use Fmos\Core\Database;
use Fmos\Domains\Catalog\MaterialService;

final class FurnitureViewService
{
    public function drawing2d(int $tenantId, int $furnitureId, string $view = 'FRONT'): array
    {
        $engine = new FurnitureEngine();
        $furniture = $engine->get($tenantId, $furnitureId);
        $view = strtoupper($view);
        $w = (float) ($furniture['width_mm'] ?? $furniture['parameters']['width'] ?? 0);
        $h = (float) ($furniture['height_mm'] ?? $furniture['parameters']['height'] ?? 0);
        $d = (float) ($furniture['depth_mm'] ?? $furniture['parameters']['depth'] ?? 0);
        $t = (float) ($furniture['parameters']['carcass_thickness'] ?? 18);
        $layout = (new FurnitureLayoutEngine())->normalizeLayout($furniture['parameters'] ?? []);
        $plinth = (float) ($layout['plinth_height_mm'] ?? 0);
        $loftH = !empty($layout['loft']['enabled']) ? (float) ($layout['loft']['height_mm'] ?? 0) : 0.0;
        $mainH = max(1.0, $h - $plinth - $loftH);
        $partT = (float) ($layout['partition_thickness_mm'] ?? $t);

        $elements = [];
        $dimensions = [];

        if (in_array($view, ['FRONT', 'INTERNAL', 'BACK'], true)) {
            $elements[] = ['type' => 'rect', 'x' => 0, 'y' => 0, 'w' => $w, 'h' => $h, 'label' => 'Carcass', 'role' => 'outer'];
            if ($plinth > 0) {
                $elements[] = ['type' => 'rect', 'x' => 0, 'y' => $h - $plinth, 'w' => $w, 'h' => $plinth, 'label' => 'Plinth', 'role' => 'plinth'];
            }
            if ($loftH > 0) {
                $elements[] = ['type' => 'rect', 'x' => $t, 'y' => 0, 'w' => max(0, $w - 2 * $t), 'h' => $loftH, 'label' => 'Loft', 'role' => 'loft'];
            }
            $mainTop = $loftH;
            $elements[] = ['type' => 'rect', 'x' => $t, 'y' => $mainTop + $t, 'w' => max(0, $w - 2 * $t), 'h' => max(0, $mainH - 2 * $t), 'label' => 'Internal', 'role' => 'inner'];

            $internalW = max(1.0, $w - 2 * $t);
            $bays = $layout['bays'] ?? [];
            $bayWidths = $this->bayWidths($bays, $internalW, $partT);
            $x = $t;
            foreach ($bayWidths as $i => $bayW) {
                $bay = $bays[$i] ?? [];
                $elements[] = ['type' => 'rect', 'x' => $x, 'y' => $mainTop + $t, 'w' => $bayW, 'h' => max(0, $mainH - 2 * $t), 'label' => $bay['label'] ?? ('Bay ' . ($i + 1)), 'role' => 'bay'];
                $y = $mainTop + $t;
                $innerH = max(1.0, $mainH - 2 * $t);
                $sections = $bay['sections'] ?? [];
                $secHeights = $this->sectionHeights($sections, $innerH);
                foreach ($secHeights as $sIdx => $secH) {
                    $sec = $sections[$sIdx] ?? [];
                    $type = strtoupper((string) ($sec['type'] ?? 'OPEN'));
                    $elements[] = [
                        'type' => 'rect',
                        'x' => $x + 2,
                        'y' => $y,
                        'w' => max(1, $bayW - 4),
                        'h' => $secH,
                        'label' => ($sec['label'] ?? $type),
                        'role' => strtolower($type),
                    ];
                    if ($type === 'SHELVES') {
                        $count = max(1, (int) ($sec['shelf_count'] ?? 1));
                        for ($s = 1; $s <= $count; $s++) {
                            $sy = $y + ($secH * $s / ($count + 1));
                            $elements[] = ['type' => 'line', 'x1' => $x + 4, 'y1' => $sy, 'x2' => $x + $bayW - 4, 'y2' => $sy, 'label' => 'Shelf', 'role' => 'shelf'];
                        }
                    }
                    if ($type === 'DRAWERS') {
                        $count = max(1, (int) ($sec['drawer_count'] ?? 1));
                        for ($s = 1; $s <= $count; $s++) {
                            $dy = $y + ($secH * $s / ($count + 1));
                            $elements[] = ['type' => 'line', 'x1' => $x + 6, 'y1' => $dy, 'x2' => $x + $bayW - 6, 'y2' => $dy, 'label' => 'Drawer', 'role' => 'drawer'];
                        }
                    }
                    $y += $secH;
                }
                $x += $bayW + $partT;
            }

            $dimensions[] = ['axis' => 'H', 'value' => $w, 'from' => [0, -$h * 0.08], 'to' => [$w, -$h * 0.08], 'label' => (string) $w];
            $dimensions[] = ['axis' => 'V', 'value' => $h, 'from' => [-$w * 0.08, 0], 'to' => [-$w * 0.08, $h], 'label' => (string) $h];
            if ($loftH > 0) {
                $dimensions[] = ['axis' => 'V', 'value' => $loftH, 'from' => [$w + 20, 0], 'to' => [$w + 20, $loftH], 'label' => 'Loft ' . $loftH];
            }
            if ($plinth > 0) {
                $dimensions[] = ['axis' => 'V', 'value' => $plinth, 'from' => [$w + 20, $h - $plinth], 'to' => [$w + 20, $h], 'label' => 'Plinth ' . $plinth];
            }
        } elseif ($view === 'PLAN') {
            $elements[] = ['type' => 'rect', 'x' => 0, 'y' => 0, 'w' => $w, 'h' => $d, 'label' => 'Plan', 'role' => 'outer'];
            $dimensions[] = ['axis' => 'H', 'value' => $w, 'from' => [0, -$d * 0.12], 'to' => [$w, -$d * 0.12], 'label' => (string) $w];
            $dimensions[] = ['axis' => 'V', 'value' => $d, 'from' => [-$w * 0.08, 0], 'to' => [-$w * 0.08, $d], 'label' => (string) $d];
        } else {
            $elements[] = ['type' => 'rect', 'x' => 0, 'y' => 0, 'w' => $d, 'h' => $h, 'label' => $view, 'role' => 'outer'];
            $dimensions[] = ['axis' => 'H', 'value' => $d, 'from' => [0, -$h * 0.08], 'to' => [$d, -$h * 0.08], 'label' => (string) $d];
            $dimensions[] = ['axis' => 'V', 'value' => $h, 'from' => [-$d * 0.12, 0], 'to' => [-$d * 0.12, $h], 'label' => (string) $h];
        }

        $pdo = Database::connection();
        $project = $pdo->prepare('SELECT p.name AS project_name, c.name AS client_name FROM projects p LEFT JOIN clients c ON c.id=p.client_id WHERE p.id=? AND p.tenant_id=?');
        $project->execute([(int) $furniture['project_id'], $tenantId]);
        $meta = $project->fetch() ?: ['project_name' => '', 'client_name' => ''];

        return [
            'view' => $view,
            'unit' => 'mm',
            'bounds' => ['width' => $w, 'height' => $h, 'depth' => $d],
            'layout' => $layout,
            'elements' => $elements,
            'dimensions' => $dimensions,
            'title_block' => [
                'project' => $meta['project_name'],
                'client' => $meta['client_name'],
                'furniture' => $furniture['name'],
                'code' => $furniture['code'],
                'revision' => (int) $furniture['revision'],
                'view' => $view,
            ],
        ];
    }

    public function model3d(int $tenantId, int $furnitureId): array
    {
        $engine = new FurnitureEngine();
        $furniture = $engine->get($tenantId, $furnitureId);
        $matSvc = new MaterialService();
        $finishCache = [];

        $resolveFinish = function (?int $finishId) use ($tenantId, $matSvc, &$finishCache): ?array {
            if (!$finishId) {
                return null;
            }
            if (!isset($finishCache[$finishId])) {
                try {
                    $m = $matSvc->get($tenantId, $finishId);
                    $url = null;
                    foreach ($m['assets'] ?? [] as $a) {
                        if (($a['asset_type'] ?? '') === 'TEXTURE_ALBEDO' && !empty($a['public_url'])) {
                            $url = $a['public_url'];
                            break;
                        }
                    }
                    if ($url === null) {
                        foreach ($m['assets'] ?? [] as $a) {
                            if (!empty($a['public_url'])) {
                                $url = $a['public_url'];
                                break;
                            }
                        }
                    }
                    $finishCache[$finishId] = [
                        'id' => $finishId,
                        'sku' => $m['sku'],
                        'texture_url' => $url,
                        'roughness' => (float) ($m['default_roughness'] ?? 0.55),
                        'metalness' => (float) ($m['default_metalness'] ?? 0),
                    ];
                } catch (\Throwable) {
                    $finishCache[$finishId] = null;
                }
            }
            return $finishCache[$finishId];
        };

        $exterior = $resolveFinish($furniture['exterior_finish_id'] !== null ? (int) $furniture['exterior_finish_id'] : null);
        $interior = $resolveFinish($furniture['interior_finish_id'] !== null ? (int) $furniture['interior_finish_id'] : null);

        $w = (float) ($furniture['width_mm'] ?? 0);
        $h = (float) ($furniture['height_mm'] ?? 0);
        $d = (float) ($furniture['depth_mm'] ?? 0);
        $t = (float) ($furniture['parameters']['carcass_thickness'] ?? 18);
        $layout = (new FurnitureLayoutEngine())->normalizeLayout($furniture['parameters'] ?? []);
        $plinth = (float) ($layout['plinth_height_mm'] ?? 0);
        $loftH = !empty($layout['loft']['enabled']) ? (float) ($layout['loft']['height_mm'] ?? 0) : 0.0;
        $mainH = max(1.0, $h - $plinth - $loftH);
        $meshes = [];

        $meshes[] = $this->box('Left Side', $t, $h, $d, $t / 2, $h / 2, $d / 2, $interior ?: $exterior, 'carcass');
        $meshes[] = $this->box('Right Side', $t, $h, $d, $w - $t / 2, $h / 2, $d / 2, $interior ?: $exterior, 'carcass');
        $meshes[] = $this->box('Top', max(1, $w - 2 * $t), $t, $d, $w / 2, $h - $t / 2, $d / 2, $interior ?: $exterior, 'carcass');
        $meshes[] = $this->box('Bottom', max(1, $w - 2 * $t), $t, $d, $w / 2, $plinth + $t / 2, $d / 2, $interior ?: $exterior, 'carcass');
        $backT = (float) ($furniture['parameters']['back_thickness'] ?? 6);
        $meshes[] = $this->box('Back', $w, $h, $backT, $w / 2, $h / 2, $backT / 2, $interior ?: $exterior, 'carcass');

        if ($loftH > 0) {
            $meshes[] = $this->box('Loft Shelf', max(1, $w - 2 * $t), $t, max(1, $d - $t), $w / 2, $h - $loftH, $d / 2, $interior ?: $exterior, 'loft');
        }

        $internalW = max(1.0, $w - 2 * $t);
        $partT = (float) ($layout['partition_thickness_mm'] ?? $t);
        $bays = $layout['bays'] ?? [];
        $bayWidths = $this->bayWidths($bays, $internalW, $partT);
        $x = $t;
        foreach ($bayWidths as $i => $bayW) {
            if ($i > 0) {
                $meshes[] = $this->box('Partition ' . $i, $partT, max(1, $mainH - 2 * $t), max(1, $d - $t), $x - $partT / 2, $plinth + $mainH / 2, $d / 2, $interior ?: $exterior, 'partition');
            }
            $bay = $bays[$i] ?? [];
            $y = $plinth + $t;
            $innerH = max(1.0, $mainH - 2 * $t);
            foreach ($this->sectionHeights($bay['sections'] ?? [], $innerH) as $sIdx => $secH) {
                $sec = ($bay['sections'] ?? [])[$sIdx] ?? [];
                $type = strtoupper((string) ($sec['type'] ?? 'OPEN'));
                if ($type === 'SHELVES') {
                    $count = max(1, (int) ($sec['shelf_count'] ?? 1));
                    for ($s = 1; $s <= $count; $s++) {
                        $sy = $y + ($secH * $s / ($count + 1));
                        $meshes[] = $this->box('Shelf', max(1, $bayW - 4), $t, max(1, $d - $t), $x + $bayW / 2, $sy, $d / 2, $interior ?: $exterior, 'shelf');
                    }
                }
                $y += $secH;
            }
            $x += $bayW + $partT;
        }

        $shutters = max(0, (int) ($furniture['parameters']['shutter_count'] ?? 0));
        $doorType = (string) ($furniture['parameters']['door_type'] ?? $layout['door_type'] ?? 'HINGED');
        if ($doorType !== 'NONE' && $shutters > 0) {
            $sw = $internalW / $shutters;
            $componentRows = $furniture['component_rows'] ?? [];
            $shutterRows = array_values(array_filter($componentRows, static fn ($r) => preg_match('/shutter|sliding door|door/i', (string) $r['name'])));
            for ($i = 0; $i < $shutters; $i++) {
                $finish = $exterior;
                if (isset($shutterRows[$i]) && !empty($shutterRows[$i]['finish_id'])) {
                    $finish = $resolveFinish((int) $shutterRows[$i]['finish_id']) ?: $finish;
                }
                $meshes[] = $this->box(
                    ($doorType === 'SLIDING' ? 'Sliding Door ' : 'Shutter ') . ($i + 1),
                    $sw - 2,
                    max(1, $mainH - 2 * $t),
                    $t,
                    $t + $sw * $i + $sw / 2,
                    $plinth + $mainH / 2,
                    $d - $t / 2,
                    $finish,
                    'shutter'
                );
            }
        }

        return [
            'furniture_id' => $furnitureId,
            'bounds' => ['width' => $w, 'height' => $h, 'depth' => $d],
            'layout' => $layout,
            'default_exterior' => $exterior,
            'default_interior' => $interior,
            'meshes' => $meshes,
        ];
    }

    /** @param list<array<string,mixed>> $bays @return list<float> */
    private function bayWidths(array $bays, float $internalW, float $partT): array
    {
        $n = max(1, count($bays));
        if ($bays === []) {
            return [$internalW];
        }
        $fixed = 0.0;
        $flex = 0;
        $widths = array_fill(0, $n, 0.0);
        foreach ($bays as $i => $bay) {
            if (!empty($bay['width_mm']) && (float) $bay['width_mm'] > 0) {
                $widths[$i] = (float) $bay['width_mm'];
                $fixed += $widths[$i];
            } else {
                $flex++;
            }
        }
        $available = max(1.0, $internalW - ($partT * max(0, $n - 1)));
        $remain = max(1.0, $available - $fixed);
        $each = $flex > 0 ? $remain / $flex : 0;
        foreach ($widths as $i => $w) {
            if ($w <= 0) {
                $widths[$i] = $each;
            }
        }
        return $widths;
    }

    /** @param list<array<string,mixed>> $sections @return list<float> */
    private function sectionHeights(array $sections, float $innerH): array
    {
        if ($sections === []) {
            return [$innerH];
        }
        $fixed = 0.0;
        $flex = [];
        $heights = [];
        foreach ($sections as $i => $s) {
            if (isset($s['height_mm']) && $s['height_mm'] !== null && $s['height_mm'] !== '') {
                $heights[$i] = (float) $s['height_mm'];
                $fixed += $heights[$i];
            } else {
                $flex[] = $i;
                $heights[$i] = 0.0;
            }
        }
        $each = count($flex) > 0 ? max(0.0, $innerH - $fixed) / count($flex) : 0;
        foreach ($flex as $i) {
            $heights[$i] = $each;
        }
        return array_values($heights);
    }

    private function box(string $name, float $sx, float $sy, float $sz, float $x, float $y, float $z, ?array $finish, string $role): array
    {
        return [
            'name' => $name,
            'role' => $role,
            'size' => [round($sx, 2), round($sy, 2), round($sz, 2)],
            'position' => [round($x, 2), round($y, 2), round($z, 2)],
            'finish' => $finish,
            // Keep a visible fallback color so 3D never goes black if texture is slow/missing.
            'color' => $role === 'shutter' ? '#c9d6df' : '#e6ebf0',
        ];
    }
}
