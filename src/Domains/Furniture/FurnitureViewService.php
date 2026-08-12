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
        $expoMap = FurnitureExpo::fromParameters(is_array($furniture['parameters'] ?? null) ? $furniture['parameters'] : []);

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
            $shelvesExpo = FurnitureExpo::isExpo('SHELF', $expoMap);
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
                            $elements[] = [
                                'type' => 'line',
                                'x1' => $x + 4,
                                'y1' => $sy,
                                'x2' => $x + $bayW - 4,
                                'y2' => $sy,
                                'label' => 'Shelf',
                                'role' => 'shelf',
                                'expo' => $shelvesExpo,
                                'component_role' => 'SHELF',
                            ];
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

            // Structural EXPO highlights (front/internal/back elevation)
            $this->appendExpoSideMarkers($elements, $expoMap, $view, $w, $h, $d, $t);

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
            $this->appendExpoSideMarkers($elements, $expoMap, $view, $w, $h, $d, $t);
            $dimensions[] = ['axis' => 'H', 'value' => $w, 'from' => [0, -$d * 0.12], 'to' => [$w, -$d * 0.12], 'label' => (string) $w];
            $dimensions[] = ['axis' => 'V', 'value' => $d, 'from' => [-$w * 0.08, 0], 'to' => [-$w * 0.08, $d], 'label' => (string) $d];
        } else {
            $elements[] = ['type' => 'rect', 'x' => 0, 'y' => 0, 'w' => $d, 'h' => $h, 'label' => $view, 'role' => 'outer'];
            $this->appendExpoSideMarkers($elements, $expoMap, $view, $w, $h, $d, $t);
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
            'expo' => $expoMap,
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
        $expoMap = FurnitureExpo::fromParameters(is_array($furniture['parameters'] ?? null) ? $furniture['parameters'] : []);

        $w = (float) ($furniture['width_mm'] ?? 0);
        $h = (float) ($furniture['height_mm'] ?? 0);
        $d = (float) ($furniture['depth_mm'] ?? 0);
        $t = (float) ($furniture['parameters']['carcass_thickness'] ?? 18);
        $backT = (float) ($furniture['parameters']['back_thickness'] ?? 18);
        $layout = (new FurnitureLayoutEngine())->normalizeLayout($furniture['parameters'] ?? []);
        $plinth = (float) ($layout['plinth_height_mm'] ?? 0);
        $loftH = !empty($layout['loft']['enabled']) ? (float) ($layout['loft']['height_mm'] ?? 0) : 0.0;
        $mainH = max(1.0, $h - $plinth - $loftH);
        $internalDepth = max(1.0, $d - max($t, $backT));
        $meshes = [];

        $makePanel = function (
            string $name,
            float $sx,
            float $sy,
            float $sz,
            float $x,
            float $y,
            float $z,
            string $visRole,
            string $componentRole
        ) use (&$meshes, $expoMap, $exterior, $interior): void {
            $faces = PanelFinishResolver::resolve(
                $componentRole,
                $expoMap,
                isset($exterior['id']) ? (int) $exterior['id'] : null,
                isset($interior['id']) ? (int) $interior['id'] : null
            );
            // Attach resolved finish objects (not just ids) for the renderer.
            $extFinish = !empty($faces['expo']) ? ($exterior ?: $interior) : ($interior ?: $exterior);
            $intFinish = $interior ?: $exterior;
            if (!empty($faces['expo'])) {
                $faceExt = $exterior ?: $interior;
                $faceInt = $interior ?: $exterior;
            } else {
                $faceExt = $interior ?: $exterior;
                $faceInt = $interior ?: $exterior;
            }
            $meshes[] = $this->box(
                $name,
                $sx,
                $sy,
                $sz,
                $x,
                $y,
                $z,
                $extFinish,
                $visRole,
                $componentRole,
                !empty($faces['expo']),
                [
                    'exterior' => $faceExt,
                    'interior' => $faceInt,
                    'expo_face_index' => PanelFinishResolver::expoFaceIndex($componentRole),
                    'faces' => $faces,
                ]
            );
        };

        $makePanel('Left Side', $t, $h, $d, $t / 2, $h / 2, $d / 2, 'carcass', 'LEFT_PANEL');
        $makePanel('Right Side', $t, $h, $d, $w - $t / 2, $h / 2, $d / 2, 'carcass', 'RIGHT_PANEL');
        $makePanel('Top', max(1, $w - 2 * $t), $t, $d, $w / 2, $h - $t / 2, $d / 2, 'carcass', 'TOP_PANEL');
        $makePanel('Bottom', max(1, $w - 2 * $t), $t, $d, $w / 2, $plinth + $t / 2, $d / 2, 'carcass', 'BOTTOM_PANEL');
        $makePanel('Back', $w, $h, $backT, $w / 2, $h / 2, $backT / 2, 'carcass', 'BACK_PANEL');

        if ($loftH > 0) {
            $makePanel('Loft Shelf', max(1, $w - 2 * $t), $t, $internalDepth, $w / 2, $h - $loftH, $backT + $internalDepth / 2, 'loft', 'LOFT_SHELF');
        }

        $internalW = max(1.0, $w - 2 * $t);
        $partT = (float) ($layout['partition_thickness_mm'] ?? $t);
        $bays = $layout['bays'] ?? [];
        $bayWidths = $this->bayWidths($bays, $internalW, $partT);
        $x = $t;
        foreach ($bayWidths as $i => $bayW) {
            if ($i > 0) {
                $makePanel('Partition ' . $i, $partT, max(1, $mainH - 2 * $t), $internalDepth, $x - $partT / 2, $plinth + $mainH / 2, $backT + $internalDepth / 2, 'partition', 'VERTICAL_PARTITION');
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
                        $makePanel('Shelf', max(1, $bayW - 4), $t, $internalDepth, $x + $bayW / 2, $sy, $backT + $internalDepth / 2, 'shelf', 'SHELF');
                    }
                }
                $y += $secH;
            }
            $x += $bayW + $partT;
        }

        $shutters = max(0, (int) ($furniture['parameters']['shutter_count'] ?? 0));
        $doorType = (string) ($furniture['parameters']['door_type'] ?? $layout['door_type'] ?? 'HINGED');
        $doorRole = $doorType === 'SLIDING' ? 'SLIDING_DOOR' : 'SHUTTER';
        if ($doorType !== 'NONE' && $shutters > 0) {
            $sw = $internalW / $shutters;
            $componentRows = $furniture['component_rows'] ?? [];
            $shutterRows = array_values(array_filter($componentRows, static fn ($r) => preg_match('/shutter|sliding door|door/i', (string) $r['name'])));
            for ($i = 0; $i < $shutters; $i++) {
                $faces = PanelFinishResolver::resolve(
                    $doorRole,
                    $expoMap,
                    isset($exterior['id']) ? (int) $exterior['id'] : null,
                    isset($interior['id']) ? (int) $interior['id'] : null
                );
                $finish = $exterior ?: $interior;
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
                    'shutter',
                    $doorRole,
                    !empty($faces['expo']),
                    [
                        'exterior' => $finish,
                        'interior' => $interior ?: $finish,
                        'expo_face_index' => PanelFinishResolver::expoFaceIndex($doorRole),
                        'faces' => $faces,
                    ]
                );
            }
        }

        return [
            'furniture_id' => $furnitureId,
            'bounds' => ['width' => $w, 'height' => $h, 'depth' => $d],
            'layout' => $layout,
            'expo' => $expoMap,
            'back_thickness' => $backT,
            'back_material_id' => $furniture['parameters']['back_material_id'] ?? null,
            'default_exterior' => $exterior,
            'default_interior' => $interior,
            'meshes' => $meshes,
        ];
    }

    /**
     * Add EXPO markers for sides/top/bottom/back without obscuring geometry.
     *
     * @param list<array<string,mixed>> $elements
     * @param array<string,bool> $expoMap
     */
    private function appendExpoSideMarkers(array &$elements, array $expoMap, string $view, float $w, float $h, float $d, float $t): void
    {
        $mark = static function (string $componentRole, string $label, float $x, float $y, float $rw, float $rh) use (&$elements, $expoMap): void {
            if (!FurnitureExpo::isExpo($componentRole, $expoMap)) {
                return;
            }
            $elements[] = [
                'type' => 'rect',
                'x' => $x,
                'y' => $y,
                'w' => $rw,
                'h' => $rh,
                'label' => $label,
                'role' => 'expo',
                'expo' => true,
                'component_role' => $componentRole,
            ];
            $elements[] = [
                'type' => 'label',
                'x' => $x + max(2.0, $rw * 0.1),
                'y' => $y + max(12.0, $rh * 0.5),
                'text' => 'EXPO',
                'role' => 'expo-label',
                'expo' => true,
                'component_role' => $componentRole,
            ];
        };

        if (in_array($view, ['FRONT', 'INTERNAL'], true)) {
            $mark('LEFT_PANEL', 'Left Side', 0, 0, max(2.0, $t), $h);
            $mark('RIGHT_PANEL', 'Right Side', max(0.0, $w - $t), 0, max(2.0, $t), $h);
            $mark('TOP_PANEL', 'Top', $t, 0, max(1.0, $w - 2 * $t), max(2.0, $t));
            $mark('BOTTOM_PANEL', 'Bottom', $t, max(0.0, $h - $t), max(1.0, $w - 2 * $t), max(2.0, $t));
            if (FurnitureExpo::isExpo('BACK_PANEL', $expoMap) && $view === 'INTERNAL') {
                $elements[] = [
                    'type' => 'label',
                    'x' => $w * 0.42,
                    'y' => $h * 0.08,
                    'text' => 'BACK EXPO',
                    'role' => 'expo-label',
                    'expo' => true,
                    'component_role' => 'BACK_PANEL',
                ];
            }
        } elseif ($view === 'BACK') {
            $mark('RIGHT_PANEL', 'Right Side', 0, 0, max(2.0, $t), $h);
            $mark('LEFT_PANEL', 'Left Side', max(0.0, $w - $t), 0, max(2.0, $t), $h);
            $mark('BACK_PANEL', 'Back', $t, $t, max(1.0, $w - 2 * $t), max(1.0, $h - 2 * $t));
        } elseif ($view === 'PLAN') {
            $mark('LEFT_PANEL', 'Left Side', 0, 0, max(2.0, $t), $d);
            $mark('RIGHT_PANEL', 'Right Side', max(0.0, $w - $t), 0, max(2.0, $t), $d);
            $mark('BACK_PANEL', 'Back', $t, 0, max(1.0, $w - 2 * $t), max(2.0, $t));
            $mark('TOP_PANEL', 'Top', $w * 0.35, $d * 0.4, max(40.0, $w * 0.3), max(20.0, $d * 0.15));
        } elseif ($view === 'LEFT') {
            $mark('LEFT_PANEL', 'Left Side', 0, 0, $d, $h);
            $mark('TOP_PANEL', 'Top', 0, 0, $d, max(2.0, $t));
            $mark('BOTTOM_PANEL', 'Bottom', 0, max(0.0, $h - $t), $d, max(2.0, $t));
            $mark('BACK_PANEL', 'Back', 0, 0, max(2.0, $t), $h);
        } elseif ($view === 'RIGHT') {
            $mark('RIGHT_PANEL', 'Right Side', 0, 0, $d, $h);
            $mark('TOP_PANEL', 'Top', 0, 0, $d, max(2.0, $t));
            $mark('BOTTOM_PANEL', 'Bottom', 0, max(0.0, $h - $t), $d, max(2.0, $t));
            $mark('BACK_PANEL', 'Back', max(0.0, $d - $t), 0, max(2.0, $t), $h);
        }
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

    private function box(
        string $name,
        float $sx,
        float $sy,
        float $sz,
        float $x,
        float $y,
        float $z,
        ?array $finish,
        string $role,
        ?string $componentRole = null,
        bool $expo = false,
        ?array $faceFinishes = null
    ): array {
        $color = $role === 'shutter' ? '#c9d6df' : '#e6ebf0';
        if ($expo && $role !== 'shutter') {
            $color = '#d7e8f5';
        }
        return [
            'name' => $name,
            'role' => $role,
            'component_role' => $componentRole,
            'expo' => $expo,
            'size' => [round($sx, 2), round($sy, 2), round($sz, 2)],
            'position' => [round($x, 2), round($y, 2), round($z, 2)],
            'finish' => $finish,
            'face_finishes' => $faceFinishes,
            // Keep a visible fallback color so 3D never goes black if texture is slow/missing.
            'color' => $color,
        ];
    }
}
