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
        $fillers = FurnitureFillers::fromParameters(is_array($furniture['parameters'] ?? null) ? $furniture['parameters'] : []);
        $leftFillerW = FurnitureFillers::leftWidth($fillers);
        $rightFillerW = FurnitureFillers::rightWidth($fillers);

        if (in_array($view, ['FRONT', 'INTERNAL', 'BACK'], true)) {
            $ox = $leftFillerW;
            if ($leftFillerW > 0) {
                $elements[] = [
                    'type' => 'rect',
                    'x' => 0,
                    'y' => 0,
                    'w' => $leftFillerW,
                    'h' => $h,
                    'label' => 'Left Filler',
                    'role' => 'filler',
                    'component_role' => 'FILLER_LEFT',
                    'expo' => FurnitureExpo::isExpo('FILLER_LEFT', $expoMap),
                ];
            }
            if ($rightFillerW > 0) {
                $elements[] = [
                    'type' => 'rect',
                    'x' => $ox + $w,
                    'y' => 0,
                    'w' => $rightFillerW,
                    'h' => $h,
                    'label' => 'Right Filler',
                    'role' => 'filler',
                    'component_role' => 'FILLER_RIGHT',
                    'expo' => FurnitureExpo::isExpo('FILLER_RIGHT', $expoMap),
                ];
            }
            $elements[] = ['type' => 'rect', 'x' => $ox, 'y' => 0, 'w' => $w, 'h' => $h, 'label' => 'Carcass', 'role' => 'outer'];
            if ($plinth > 0) {
                $elements[] = ['type' => 'rect', 'x' => $ox, 'y' => $h - $plinth, 'w' => $w, 'h' => $plinth, 'label' => 'Plinth', 'role' => 'plinth'];
            }
            if ($loftH > 0) {
                $elements[] = ['type' => 'rect', 'x' => $ox + $t, 'y' => 0, 'w' => max(0, $w - 2 * $t), 'h' => $loftH, 'label' => 'Loft', 'role' => 'loft'];
            }
            $mainTop = $loftH;
            $elements[] = ['type' => 'rect', 'x' => $ox + $t, 'y' => $mainTop + $t, 'w' => max(0, $w - 2 * $t), 'h' => max(0, $mainH - 2 * $t), 'label' => 'Internal', 'role' => 'inner'];

            $internalW = max(1.0, $w - 2 * $t);
            $bays = $layout['bays'] ?? [];
            $bayWidths = $this->bayWidths($bays, $internalW, $partT);
            $x = $ox + $t;
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
                        'label' => $type === 'MIRROR'
                            ? ((isset($sec['label']) && strtoupper((string) $sec['label']) !== 'MIRROR') ? (string) $sec['label'] : 'Niche')
                            : ($sec['label'] ?? $type),
                        'role' => $type === 'MIRROR' ? 'niche' : strtolower($type),
                        'component_role' => $type === 'MIRROR' ? 'NICHE_BACK' : null,
                        'expo' => $type === 'MIRROR' ? FurnitureExpo::isExpo('NICHE_BACK', $expoMap) : false,
                    ];
                    if ($type === 'MIRROR') {
                        $glass = FurnitureMirror::resolveGlass($sec, max(1.0, $bayW - 4), $secH);
                        $elements[] = [
                            'type' => 'rect',
                            'x' => $x + 2 + $glass['margin_x_mm'],
                            'y' => $y + $glass['margin_y_mm'],
                            'w' => $glass['width_mm'],
                            'h' => $glass['height_mm'],
                            'label' => 'Mirror',
                            'role' => 'mirror',
                            'component_role' => 'MIRROR_PANEL',
                            'expo' => false,
                        ];
                    }
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
            $this->appendExpoSideMarkers($elements, $expoMap, $view, $w, $h, $d, $t, $ox);
            if ($leftFillerW > 0 && FurnitureExpo::isExpo('FILLER_LEFT', $expoMap)) {
                $elements[] = [
                    'type' => 'callout',
                    'text' => 'EXPO',
                    'anchor_x' => $leftFillerW * 0.5,
                    'anchor_y' => $h * 0.12,
                    'side' => 'left',
                    'role' => 'expo-label',
                    'expo' => true,
                    'component_role' => 'FILLER_LEFT',
                ];
            }
            if ($rightFillerW > 0 && FurnitureExpo::isExpo('FILLER_RIGHT', $expoMap)) {
                $elements[] = [
                    'type' => 'callout',
                    'text' => 'EXPO',
                    'anchor_x' => $ox + $w + $rightFillerW * 0.5,
                    'anchor_y' => $h * 0.12,
                    'side' => 'right',
                    'role' => 'expo-label',
                    'expo' => true,
                    'component_role' => 'FILLER_RIGHT',
                ];
            }

            $totalW = $w + $leftFillerW + $rightFillerW;
            // Staggered dimension lanes (mm space) so labels never share the same baseline.
            $lane = max(90.0, $h * 0.055);
            $rightLane = max(70.0, $w * 0.04);
            $dimensions[] = [
                'axis' => 'H',
                'value' => $w,
                'from' => [$ox, -$lane],
                'to' => [$ox + $w, -$lane],
                'label' => (string) $w,
                'lane' => 1,
            ];
            if ($leftFillerW > 0 || $rightFillerW > 0) {
                $dimensions[] = [
                    'axis' => 'H',
                    'value' => $totalW,
                    'from' => [0, -$lane * 2.05],
                    'to' => [$totalW, -$lane * 2.05],
                    'label' => 'Overall ' . $totalW,
                    'lane' => 2,
                ];
            }
            $dimensions[] = [
                'axis' => 'V',
                'value' => $h,
                'from' => [-$lane * 1.15, 0],
                'to' => [-$lane * 1.15, $h],
                'label' => (string) $h,
                'lane' => 1,
            ];
            if ($loftH > 0) {
                $dimensions[] = [
                    'axis' => 'V',
                    'value' => $loftH,
                    'from' => [$ox + $w + $rightLane, 0],
                    'to' => [$ox + $w + $rightLane, $loftH],
                    'label' => 'Loft ' . $loftH,
                    'lane' => 1,
                ];
            }
            if ($plinth > 0) {
                $dimensions[] = [
                    'axis' => 'V',
                    'value' => $plinth,
                    'from' => [$ox + $w + $rightLane * 2.1, $h - $plinth],
                    'to' => [$ox + $w + $rightLane * 2.1, $h],
                    'label' => 'Plinth ' . $plinth,
                    'lane' => 2,
                ];
            }
        } elseif ($view === 'PLAN') {
            $ox = $leftFillerW;
            if ($leftFillerW > 0) {
                $elements[] = [
                    'type' => 'rect', 'x' => 0, 'y' => 0, 'w' => $leftFillerW, 'h' => $d,
                    'label' => 'Left Filler', 'role' => 'filler', 'component_role' => 'FILLER_LEFT',
                    'expo' => FurnitureExpo::isExpo('FILLER_LEFT', $expoMap),
                ];
            }
            if ($rightFillerW > 0) {
                $elements[] = [
                    'type' => 'rect', 'x' => $ox + $w, 'y' => 0, 'w' => $rightFillerW, 'h' => $d,
                    'label' => 'Right Filler', 'role' => 'filler', 'component_role' => 'FILLER_RIGHT',
                    'expo' => FurnitureExpo::isExpo('FILLER_RIGHT', $expoMap),
                ];
            }
            $elements[] = ['type' => 'rect', 'x' => $ox, 'y' => 0, 'w' => $w, 'h' => $d, 'label' => 'Plan', 'role' => 'outer'];
            $this->appendExpoSideMarkers($elements, $expoMap, $view, $w, $h, $d, $t, $ox);
            $totalW = $w + $leftFillerW + $rightFillerW;
            $lane = max(90.0, $d * 0.12);
            $dimensions[] = ['axis' => 'H', 'value' => $totalW, 'from' => [0, -$lane], 'to' => [$totalW, -$lane], 'label' => (string) $totalW, 'lane' => 1];
            $dimensions[] = ['axis' => 'V', 'value' => $d, 'from' => [-$lane * 1.1, 0], 'to' => [-$lane * 1.1, $d], 'label' => (string) $d, 'lane' => 1];
        } else {
            $elements[] = ['type' => 'rect', 'x' => 0, 'y' => 0, 'w' => $d, 'h' => $h, 'label' => $view, 'role' => 'outer'];
            $this->appendExpoSideMarkers($elements, $expoMap, $view, $w, $h, $d, $t);
            $lane = max(90.0, $h * 0.055);
            $dimensions[] = ['axis' => 'H', 'value' => $d, 'from' => [0, -$lane], 'to' => [$d, -$lane], 'label' => (string) $d, 'lane' => 1];
            $dimensions[] = ['axis' => 'V', 'value' => $h, 'from' => [-$lane * 1.15, 0], 'to' => [-$lane * 1.15, $h], 'label' => (string) $h, 'lane' => 1];
        }

        $pdo = Database::connection();
        $project = $pdo->prepare('SELECT p.name AS project_name, c.name AS client_name FROM projects p LEFT JOIN clients c ON c.id=p.client_id WHERE p.id=? AND p.tenant_id=?');
        $project->execute([(int) $furniture['project_id'], $tenantId]);
        $meta = $project->fetch() ?: ['project_name' => '', 'client_name' => ''];

        $boundsW = $w + $leftFillerW + $rightFillerW;
        return [
            'view' => $view,
            'unit' => 'mm',
            'bounds' => ['width' => $boundsW, 'height' => $h, 'depth' => $d, 'carcass_width' => $w, 'filler_left' => $leftFillerW, 'filler_right' => $rightFillerW],
            'layout' => $layout,
            'fillers' => $fillers,
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
                        'base_color' => is_string($m['attributes']['base_color'] ?? null)
                            ? $m['attributes']['base_color']
                            : null,
                        'series_code' => $m['series_code'] ?? null,
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
        $fillers = FurnitureFillers::fromParameters(is_array($furniture['parameters'] ?? null) ? $furniture['parameters'] : []);
        $leftFillerW = FurnitureFillers::leftWidth($fillers);
        $rightFillerW = FurnitureFillers::rightWidth($fillers);
        $ox = $leftFillerW;
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

        // Fillers: cut size h × width × t → mesh (width, h, t) at front plane (matches manufacturing).
        if ($leftFillerW > 0) {
            $makePanel('Left Filler', $leftFillerW, $h, $t, $leftFillerW / 2, $h / 2, $d - $t / 2, 'filler', 'FILLER_LEFT');
        }
        if ($rightFillerW > 0) {
            $makePanel('Right Filler', $rightFillerW, $h, $t, $ox + $w + $rightFillerW / 2, $h / 2, $d - $t / 2, 'filler', 'FILLER_RIGHT');
        }

        $makePanel('Left Side', $t, $h, $d, $ox + $t / 2, $h / 2, $d / 2, 'carcass', 'LEFT_PANEL');
        $makePanel('Right Side', $t, $h, $d, $ox + $w - $t / 2, $h / 2, $d / 2, 'carcass', 'RIGHT_PANEL');
        $makePanel('Top', max(1, $w - 2 * $t), $t, $d, $ox + $w / 2, $h - $t / 2, $d / 2, 'carcass', 'TOP_PANEL');
        $makePanel('Bottom', max(1, $w - 2 * $t), $t, $d, $ox + $w / 2, $plinth + $t / 2, $d / 2, 'carcass', 'BOTTOM_PANEL');
        $makePanel('Back', $w, $h, $backT, $ox + $w / 2, $h / 2, $backT / 2, 'carcass', 'BACK_PANEL');

        if ($loftH > 0) {
            $makePanel('Loft Shelf', max(1, $w - 2 * $t), $t, $internalDepth, $ox + $w / 2, $h - $loftH, $backT + $internalDepth / 2, 'loft', 'LOFT_SHELF');
        }

        $internalW = max(1.0, $w - 2 * $t);
        $partT = (float) ($layout['partition_thickness_mm'] ?? $t);
        $bays = $layout['bays'] ?? [];
        $bayWidths = $this->bayWidths($bays, $internalW, $partT);
        $x = $ox + $t;
        foreach ($bayWidths as $i => $bayW) {
            if ($i > 0) {
                $makePanel('Partition ' . $i, $partT, max(1, $mainH - 2 * $t), $internalDepth, $x - $partT / 2, $plinth + $mainH / 2, $backT + $internalDepth / 2, 'partition', 'VERTICAL_PARTITION');
            }
            $bay = $bays[$i] ?? [];
            $innerH = max(1.0, $mainH - 2 * $t);
            // Layout section order is top→bottom (same as 2D). Stack from carcass top in floor-up coords.
            foreach ($this->sectionBandsFloorUp($bay['sections'] ?? [], $innerH, $plinth + $t) as $band) {
                $secH = $band['height'];
                $sec = $band['section'];
                $type = strtoupper((string) ($sec['type'] ?? 'OPEN'));
                if ($type === 'SHELVES') {
                    $count = max(1, (int) ($sec['shelf_count'] ?? 1));
                    for ($s = 1; $s <= $count; $s++) {
                        $sy = $band['y0'] + ($secH * $s / ($count + 1));
                        $makePanel('Shelf', max(1, $bayW - 4), $t, $internalDepth, $x + $bayW / 2, $sy, $backT + $internalDepth / 2, 'shelf', 'SHELF');
                    }
                }
            }
            $x += $bayW + $partT;
        }

        $shutters = max(0, (int) ($furniture['parameters']['shutter_count'] ?? 0));
        $doorType = (string) ($furniture['parameters']['door_type'] ?? $layout['door_type'] ?? 'HINGED');
        $doorRole = $doorType === 'SLIDING' ? 'SLIDING_DOOR' : 'SHUTTER';

        // Presentation-only front reveals (do not affect manufacturing cut sizes).
        // Door gaps stay modest; drawer gaps are wider so stacks read clearly on dark laminates.
        $frontGap = 3.0;
        $drawerGap = 7.0;
        $frontInset = 2.5;
        $frontProud = 2.5;
        $frontZ = $d - $t / 2 + $frontProud;
        $innerMainH = max(1.0, $mainH - 2 * $t);
        $frontY0 = $plinth + $t;

        $addFront = function (
            string $name,
            float $sx,
            float $sy,
            float $cx,
            float $cy,
            string $visRole,
            string $componentRole,
            ?array $finishOverride = null
        ) use (
            &$meshes,
            $expoMap,
            $exterior,
            $interior,
            $resolveFinish,
            $t,
            $frontZ
        ): void {
            $faces = PanelFinishResolver::resolve(
                $componentRole,
                $expoMap,
                isset($exterior['id']) ? (int) $exterior['id'] : null,
                isset($interior['id']) ? (int) $interior['id'] : null
            );
            $finish = $finishOverride ?: ($exterior ?: $interior);
            $meshes[] = $this->box(
                $name,
                max(1.0, $sx),
                max(1.0, $sy),
                $t,
                $cx,
                $cy,
                $frontZ,
                $finish,
                $visRole,
                $componentRole,
                !empty($faces['expo']),
                [
                    'exterior' => $finish,
                    'interior' => $interior ?: $finish,
                    'expo_face_index' => PanelFinishResolver::expoFaceIndex($componentRole),
                    'faces' => $faces,
                ],
                ['front' => true, 'reveal' => true]
            );
        };

        // Dark backing behind doors/drawers only — OPEN / MIRROR niches stay recessed (no front slab).
        if ($doorType !== 'NONE' || $this->layoutHasDrawers($bays)) {
            $rx = $ox + $t;
            foreach ($bayWidths as $i => $bayW) {
                $bay = $bays[$i] ?? [];
                foreach ($this->sectionBandsFloorUp($bay['sections'] ?? [], $innerMainH, $frontY0) as $band) {
                    $type = strtoupper((string) (($band['section']['type'] ?? 'OPEN')));
                    if ($type === 'OPEN' || $type === 'MIRROR') {
                        continue;
                    }
                    if ($type !== 'DRAWERS' && $doorType === 'NONE') {
                        continue;
                    }
                    $meshes[] = $this->box(
                        'Front Reveal Backing',
                        max(1.0, $bayW - 2),
                        max(1.0, $band['height'] - 2),
                        2.0,
                        $rx + $bayW / 2,
                        $band['y0'] + $band['height'] / 2,
                        $d - $t - 1.0,
                        null,
                        'reveal',
                        null,
                        false,
                        null,
                        ['front' => false, 'reveal' => true]
                    );
                }
                $rx += $bayW + $partT;
            }
        }

        $componentRows = $furniture['component_rows'] ?? [];
        $shutterRows = array_values(array_filter(
            $componentRows,
            static fn ($r) => preg_match('/shutter|sliding door|(?<!drawer )door/i', (string) $r['name'])
        ));
        $drawerRows = array_values(array_filter(
            $componentRows,
            static fn ($r) => preg_match('/drawer front/i', (string) $r['name'])
        ));
        $drawerRowIdx = 0;
        $shutterRowIdx = 0;

        $frontsBefore = count($meshes);
        if ($bays !== [] && $bayWidths !== []) {
            $bx = $ox + $t;
            foreach ($bayWidths as $i => $bayW) {
                $bay = $bays[$i] ?? [];
                $sections = $bay['sections'] ?? [];
                $bands = $this->sectionBandsFloorUp($sections, $innerMainH, $frontY0);
                $doorSpanTop = null; // floor-up Y of top of contiguous door span
                $doorSpanBot = null; // floor-up Y of bottom of contiguous door span

                $flushDoor = function () use (
                    &$doorSpanTop,
                    &$doorSpanBot,
                    &$shutterRowIdx,
                    $doorType,
                    $bayW,
                    $bx,
                    $frontGap,
                    $frontInset,
                    $addFront,
                    $exterior,
                    $interior,
                    $resolveFinish,
                    $shutterRows,
                    $doorRole
                ): void {
                    if ($doorSpanTop === null || $doorType === 'NONE') {
                        $doorSpanTop = null;
                        $doorSpanBot = null;
                        return;
                    }
                    $doorH = max(1.0, $doorSpanTop - $doorSpanBot - $frontGap);
                    $doorFinish = $exterior ?: $interior;
                    if (isset($shutterRows[$shutterRowIdx]['finish_id'])) {
                        $doorFinish = $resolveFinish((int) $shutterRows[$shutterRowIdx]['finish_id']) ?: $doorFinish;
                    }
                    $addFront(
                        ($doorType === 'SLIDING' ? 'Sliding Door' : 'Door') . ' ' . ($shutterRowIdx + 1),
                        max(1.0, $bayW - 2 * $frontInset),
                        $doorH,
                        $bx + $bayW / 2,
                        ($doorSpanTop + $doorSpanBot) / 2,
                        'shutter',
                        $doorRole,
                        $doorFinish
                    );
                    $shutterRowIdx++;
                    $doorSpanTop = null;
                    $doorSpanBot = null;
                };

                foreach ($bands as $band) {
                    $secH = $band['height'];
                    $sy = $band['y0'];
                    $sec = $band['section'];
                    $type = strtoupper((string) ($sec['type'] ?? 'OPEN'));

                    if ($type === 'DRAWERS') {
                        $flushDoor();
                        $count = max(1, (int) ($sec['drawer_count'] ?? 1));
                        $eachH = $secH / $count;
                        for ($di = 0; $di < $count; $di++) {
                            $fh = max(1.0, $eachH - $drawerGap);
                            // Top drawer first within the band (matches 2D top→bottom reading).
                            $fy = $sy + $secH - $eachH * $di - $eachH / 2;
                            $dFinish = $exterior ?: $interior;
                            if (isset($drawerRows[$drawerRowIdx]['finish_id'])) {
                                $dFinish = $resolveFinish((int) $drawerRows[$drawerRowIdx]['finish_id']) ?: $dFinish;
                            }
                            $fw = max(1.0, $bayW - 2 * $frontInset);
                            $addFront(
                                'Drawer Front ' . ($drawerRowIdx + 1),
                                $fw,
                                $fh,
                                $bx + $bayW / 2,
                                $fy,
                                'drawer',
                                'DRAWER_FRONT',
                                $dFinish
                            );
                            if ($di < $count - 1) {
                                $gapY = $sy + $secH - $eachH * ($di + 1);
                                $meshes[] = $this->box(
                                    'Drawer Gap ' . ($drawerRowIdx + 1),
                                    $fw,
                                    max(2.0, $drawerGap - 1.0),
                                    8.0,
                                    $bx + $bayW / 2,
                                    $gapY,
                                    $frontZ - 5.0,
                                    null,
                                    'reveal',
                                    null,
                                    false,
                                    null,
                                    ['front' => false, 'reveal' => true, 'drawer_gap' => true]
                                );
                            }
                            $drawerRowIdx++;
                        }
                    } elseif ($type === 'OPEN' || $type === 'MIRROR') {
                        // True niche: never cover with a door (even when door_type is HINGED/SLIDING).
                        $flushDoor();
                        $this->appendNicheMeshes(
                            $meshes,
                            $bx,
                            $bayW,
                            $sy,
                            $secH,
                            $backT,
                            $internalDepth,
                            $t,
                            $i,
                            $expoMap,
                            $exterior,
                            $interior
                        );
                        if ($type === 'MIRROR') {
                            $glass = FurnitureMirror::resolveGlass($sec, $bayW, $secH);
                            $nicheZ = $backT + 4.0;
                            $meshes[] = $this->box(
                                'Mirror ' . ($i + 1),
                                $glass['width_mm'],
                                $glass['height_mm'],
                                FurnitureMirror::THICKNESS_MM,
                                $bx + $bayW / 2,
                                $sy + $secH / 2,
                                $nicheZ + max(6.0, $backT) / 2 + FurnitureMirror::THICKNESS_MM / 2 + 1.0,
                                null,
                                'mirror',
                                'MIRROR_PANEL',
                                false,
                                null,
                                ['front' => false, 'mirror' => true]
                            );
                        }
                    } else {
                        // SHELVES / HANGING → door when doors enabled.
                        if ($doorType !== 'NONE') {
                            if ($doorSpanTop === null) {
                                $doorSpanTop = $sy + $secH;
                            }
                            $doorSpanBot = $sy;
                        }
                    }
                }

                $flushDoor();
                $bx += $bayW + $partT;
            }
        }

        // Legacy / empty-layout fallback: equal shutters across width.
        if (count($meshes) === $frontsBefore && $doorType !== 'NONE' && $shutters > 0) {
            $sw = $internalW / $shutters;
            for ($i = 0; $i < $shutters; $i++) {
                $finish = $exterior ?: $interior;
                if (isset($shutterRows[$i]['finish_id'])) {
                    $finish = $resolveFinish((int) $shutterRows[$i]['finish_id']) ?: $finish;
                }
                $addFront(
                    ($doorType === 'SLIDING' ? 'Sliding Door ' : 'Shutter ') . ($i + 1),
                    max(1.0, $sw - $frontGap),
                    max(1.0, $innerMainH - $frontGap),
                    $ox + $t + $sw * $i + $sw / 2,
                    $frontY0 + $innerMainH / 2,
                    'shutter',
                    $doorRole,
                    $finish
                );
            }
        }

        $totalW = $w + $leftFillerW + $rightFillerW;
        return [
            'furniture_id' => $furnitureId,
            'bounds' => [
                'width' => $totalW,
                'height' => $h,
                'depth' => $d,
                'carcass_width' => $w,
                'min_x' => 0,
                'max_x' => $totalW,
                'filler_left' => $leftFillerW,
                'filler_right' => $rightFillerW,
            ],
            'layout' => $layout,
            'fillers' => $fillers,
            'expo' => $expoMap,
            'back_thickness' => $backT,
            'back_material_id' => $furniture['parameters']['back_material_id'] ?? null,
            'default_exterior' => $exterior,
            'default_interior' => $interior,
            'presentation' => [
                'front_gap_mm' => $frontGap,
                'drawer_gap_mm' => $drawerGap,
                'front_proud_mm' => $frontProud,
            ],
            'meshes' => $meshes,
        ];
    }

    /** @param list<array<string,mixed>> $bays */
    private function layoutHasDrawers(array $bays): bool
    {
        foreach ($bays as $bay) {
            foreach ($bay['sections'] ?? [] as $sec) {
                if (strtoupper((string) ($sec['type'] ?? '')) === 'DRAWERS') {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Add EXPO markers for sides/top/bottom/back without obscuring geometry.
     *
     * @param list<array<string,mixed>> $elements
     * @param array<string,bool> $expoMap
     */
    private function appendExpoSideMarkers(
        array &$elements,
        array $expoMap,
        string $view,
        float $w,
        float $h,
        float $d,
        float $t,
        float $offsetX = 0.0
    ): void {
        $mark = static function (
            string $componentRole,
            string $label,
            float $x,
            float $y,
            float $rw,
            float $rh,
            string $side = 'left'
        ) use (&$elements, $expoMap): void {
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
            // Leader-line callout outside geometry (renderer places text off the drawing).
            $elements[] = [
                'type' => 'callout',
                'text' => 'EXPO',
                'anchor_x' => $x + max($rw * 0.5, 1.0),
                'anchor_y' => $y + max($rh * 0.5, 1.0),
                'side' => $side,
                'role' => 'expo-label',
                'expo' => true,
                'component_role' => $componentRole,
                'title' => $label,
            ];
        };

        if (in_array($view, ['FRONT', 'INTERNAL'], true)) {
            $mark('LEFT_PANEL', 'Left Side', $offsetX, 0, max(2.0, $t), $h, 'left');
            $mark('RIGHT_PANEL', 'Right Side', $offsetX + max(0.0, $w - $t), 0, max(2.0, $t), $h, 'right');
            $mark('TOP_PANEL', 'Top', $offsetX + $t, 0, max(1.0, $w - 2 * $t), max(2.0, $t), 'top');
            $mark('BOTTOM_PANEL', 'Bottom', $offsetX + $t, max(0.0, $h - $t), max(1.0, $w - 2 * $t), max(2.0, $t), 'bottom');
            if (FurnitureExpo::isExpo('BACK_PANEL', $expoMap) && $view === 'INTERNAL') {
                $elements[] = [
                    'type' => 'callout',
                    'text' => 'BACK EXPO',
                    'anchor_x' => $offsetX + $w * 0.5,
                    'anchor_y' => $h * 0.18,
                    'side' => 'top',
                    'role' => 'expo-label',
                    'expo' => true,
                    'component_role' => 'BACK_PANEL',
                ];
            }
        } elseif ($view === 'BACK') {
            $mark('RIGHT_PANEL', 'Right Side', $offsetX, 0, max(2.0, $t), $h, 'left');
            $mark('LEFT_PANEL', 'Left Side', $offsetX + max(0.0, $w - $t), 0, max(2.0, $t), $h, 'right');
            $mark('BACK_PANEL', 'Back', $offsetX + $t, $t, max(1.0, $w - 2 * $t), max(1.0, $h - 2 * $t), 'top');
        } elseif ($view === 'PLAN') {
            $mark('LEFT_PANEL', 'Left Side', $offsetX, 0, max(2.0, $t), $d, 'left');
            $mark('RIGHT_PANEL', 'Right Side', $offsetX + max(0.0, $w - $t), 0, max(2.0, $t), $d, 'right');
            $mark('BACK_PANEL', 'Back', $offsetX + $t, 0, max(1.0, $w - 2 * $t), max(2.0, $t), 'top');
        } elseif ($view === 'LEFT') {
            $mark('LEFT_PANEL', 'Left Side', 0, 0, $d, $h, 'left');
            $mark('TOP_PANEL', 'Top', 0, 0, $d, max(2.0, $t), 'top');
            $mark('BOTTOM_PANEL', 'Bottom', 0, max(0.0, $h - $t), $d, max(2.0, $t), 'bottom');
            $mark('BACK_PANEL', 'Back', 0, 0, max(2.0, $t), $h, 'left');
        } elseif ($view === 'RIGHT') {
            $mark('RIGHT_PANEL', 'Right Side', 0, 0, $d, $h, 'right');
            $mark('TOP_PANEL', 'Top', 0, 0, $d, max(2.0, $t), 'top');
            $mark('BOTTOM_PANEL', 'Bottom', 0, max(0.0, $h - $t), $d, max(2.0, $t), 'bottom');
            $mark('BACK_PANEL', 'Back', max(0.0, $d - $t), 0, max(2.0, $t), $h, 'right');
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

    /**
     * EXPO niche liners for open / dressing sections (back + sides + sill).
     *
     * @param list<array<string,mixed>> $meshes
     * @param array<string,bool> $expoMap
     */
    private function appendNicheMeshes(
        array &$meshes,
        float $bx,
        float $bayW,
        float $sy,
        float $secH,
        float $backT,
        float $internalDepth,
        float $t,
        int $bayIndex,
        array $expoMap,
        ?array $exterior,
        ?array $interior
    ): void {
        $linerT = max(6.0, min($t, 12.0));
        $depth = max(1.0, $internalDepth - 4.0);
        $cz = $backT + $internalDepth / 2.0;
        $extId = isset($exterior['id']) ? (int) $exterior['id'] : null;
        $intId = isset($interior['id']) ? (int) $interior['id'] : null;
        $tag = (string) ($bayIndex + 1);

        $add = function (
            string $name,
            float $sx,
            float $sySize,
            float $sz,
            float $cx,
            float $cy,
            float $czPos,
            string $role
        ) use (&$meshes, $expoMap, $exterior, $interior, $extId, $intId): void {
            $expo = FurnitureExpo::isExpo($role, $expoMap);
            $finish = $expo ? ($exterior ?: $interior) : ($interior ?: $exterior);
            $faces = PanelFinishResolver::resolve($role, $expoMap, $extId, $intId);
            $meshes[] = $this->box(
                $name,
                max(1.0, $sx),
                max(1.0, $sySize),
                max(1.0, $sz),
                $cx,
                $cy,
                $czPos,
                $finish,
                'niche',
                $role,
                $expo,
                [
                    'exterior' => $finish,
                    'interior' => $interior ?: $finish,
                    'expo_face_index' => PanelFinishResolver::expoFaceIndex($role),
                    'faces' => $faces,
                ],
                ['front' => false, 'niche' => true]
            );
        };

        $add(
            'Niche Back ' . $tag,
            max(1.0, $bayW - 2),
            max(1.0, $secH - 2),
            max(6.0, $backT),
            $bx + $bayW / 2,
            $sy + $secH / 2,
            $backT + 4.0,
            'NICHE_BACK'
        );
        $add(
            'Niche Side Left ' . $tag,
            $linerT,
            max(1.0, $secH - 2),
            $depth,
            $bx + $linerT / 2,
            $sy + $secH / 2,
            $cz,
            'NICHE_SIDE_LEFT'
        );
        $add(
            'Niche Side Right ' . $tag,
            $linerT,
            max(1.0, $secH - 2),
            $depth,
            $bx + $bayW - $linerT / 2,
            $sy + $secH / 2,
            $cz,
            'NICHE_SIDE_RIGHT'
        );
        $add(
            'Niche Sill ' . $tag,
            max(1.0, $bayW - 2 * $linerT - 2),
            $linerT,
            $depth,
            $bx + $bayW / 2,
            $sy + $linerT / 2,
            $cz,
            'NICHE_SILL'
        );
        // Underside of niche ceiling — visible when looking up into the dressing opening.
        $add(
            'Niche Header ' . $tag,
            max(1.0, $bayW - 2 * $linerT - 2),
            $linerT,
            $depth,
            $bx + $bayW / 2,
            $sy + $secH - $linerT / 2,
            $cz,
            'NICHE_HEADER'
        );
    }

    /**
     * Layout sections are ordered top→bottom (2D elevation). Map them into floor-up bands
     * so 3D carcass/fronts match the 2D reading order.
     *
     * @param list<array<string,mixed>> $sections
     * @return list<array{index:int,height:float,y0:float,section:array<string,mixed>}>
     */
    private function sectionBandsFloorUp(array $sections, float $innerH, float $floorY): array
    {
        $heights = $this->sectionHeights($sections, $innerH);
        $bands = [];
        $yTop = $floorY + $innerH;
        foreach ($heights as $sIdx => $secH) {
            $yTop -= $secH;
            $bands[] = [
                'index' => (int) $sIdx,
                'height' => (float) $secH,
                'y0' => $yTop,
                'section' => $sections[$sIdx] ?? [],
            ];
        }
        return $bands;
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
        ?array $faceFinishes = null,
        ?array $presentation = null
    ): array {
        if ($role === 'reveal') {
            $color = '#1c1e22';
        } elseif ($role === 'mirror') {
            $color = '#b8c4ce';
        } elseif ($role === 'niche') {
            $color = $finish['base_color'] ?? '#2a1820';
        } elseif ($role === 'shutter' || $role === 'drawer') {
            $color = $finish['base_color'] ?? '#2a1820';
        } else {
            $color = '#e6ebf0';
        }
        if ($expo && $role !== 'shutter' && $role !== 'drawer' && $role !== 'reveal' && $role !== 'mirror' && $role !== 'niche') {
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
            'presentation' => $presentation,
            // Keep a visible fallback color so 3D never goes black if texture is slow/missing.
            'color' => $color,
        ];
    }
}
