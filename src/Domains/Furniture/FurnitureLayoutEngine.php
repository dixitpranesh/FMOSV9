<?php

declare(strict_types=1);

namespace Fmos\Domains\Furniture;

/**
 * Builds manufacturable panels from outer dims + internal layout (bays/sections).
 */
final class FurnitureLayoutEngine
{
    /**
     * @param array<string,mixed> $p parameter values including layout
     * @return list<array<string,mixed>>
     */
    public function generate(string $templateCode, array $p): array
    {
        $t = (float) ($p['carcass_thickness'] ?? 18);
        $w = (float) $p['width'];
        $h = (float) $p['height'];
        $d = (float) $p['depth'];
        $backT = (float) ($p['back_thickness'] ?? 18);
        $layout = $this->normalizeLayout($p);
        $plinth = (float) ($layout['plinth_height_mm'] ?? 0);
        $partT = (float) ($layout['partition_thickness_mm'] ?? $t);
        $doorType = (string) ($p['door_type'] ?? $layout['door_type'] ?? 'HINGED');
        $loft = $layout['loft'] ?? ['enabled' => false, 'height_mm' => 0, 'shelf_count' => 0];
        $loftH = !empty($loft['enabled']) ? (float) ($loft['height_mm'] ?? 0) : 0.0;
        $mainH = max(1.0, $h - $plinth - $loftH);
        $internalW = max(1.0, $w - (2 * $t));
        $internalMainH = max(1.0, $mainH - (2 * $t));
        // Usable internal depth: leave room for rear back panel (max with carcass setback).
        // For legacy 6mm backs with 18mm carcass this equals d-t (unchanged cut sizes).
        $internalDepth = max(1.0, $d - max($t, $backT));
        $unit = $this->productLabel($templateCode, $p);

        $logical = [];
        // Outer carcass
        $logical[] = $this->panel($this->partName($unit, 'Left Panel'), $h, $d, $t, 1, 'carcass', 'LEFT_PANEL');
        $logical[] = $this->panel($this->partName($unit, 'Right Panel'), $h, $d, $t, 1, 'carcass', 'RIGHT_PANEL');
        $logical[] = $this->panel($this->partName($unit, 'Top Panel'), $internalW, $d, $t, 1, 'carcass', 'TOP_PANEL');
        $logical[] = $this->panel($this->partName($unit, 'Bottom Panel'), $internalW, $d, $t, 1, 'carcass', 'BOTTOM_PANEL');
        $logical[] = $this->panel($this->partName($unit, 'Back Panel'), $h, $w, $backT, 1, 'carcass', 'BACK_PANEL');

        // Installation fillers (opt-in only — never inferred from 3D gaps).
        // Cut size matches user example: height × filler width × board thickness.
        $fillers = FurnitureFillers::fromParameters($p);
        $leftFillerW = FurnitureFillers::leftWidth($fillers);
        $rightFillerW = FurnitureFillers::rightWidth($fillers);
        if ($leftFillerW > 0) {
            $logical[] = $this->panel(
                $this->partName($unit, 'Left Filler Panel'),
                $h,
                $leftFillerW,
                $t,
                1,
                'filler',
                'FILLER_LEFT'
            );
        }
        if ($rightFillerW > 0) {
            $logical[] = $this->panel(
                $this->partName($unit, 'Right Filler Panel'),
                $h,
                $rightFillerW,
                $t,
                1,
                'filler',
                'FILLER_RIGHT'
            );
        }

        if ($plinth > 0) {
            $logical[] = $this->panel($this->partName($unit, 'Plinth Front'), $w, $plinth, $t, 1, 'plinth', 'PLINTH_FRONT');
            $logical[] = $this->panel($this->partName($unit, 'Plinth Side'), $d - $t, $plinth, $t, 2, 'plinth', 'PLINTH_SIDE');
        }

        if ($loftH > 0) {
            $logical[] = $this->panel($this->partName($unit, 'Loft Base Panel'), $internalW, $internalDepth, $t, 1, 'loft', 'LOFT_BASE');
            $loftShelves = (int) ($loft['shelf_count'] ?? 0);
            if ($loftShelves > 0) {
                $logical[] = $this->panel($this->partName($unit, 'Loft Shelf'), $internalW, $internalDepth, $t, $loftShelves, 'loft', 'LOFT_SHELF');
            }
            $loftDoors = max(1, (int) ($p['shutter_count'] ?? count($layout['bays'])));
            $logical[] = $this->panel($this->partName($unit, 'Loft Shutter'), $loftH - $t, $internalW / $loftDoors, $t, $loftDoors, 'loft', 'LOFT_SHUTTER');
            $logical[] = $this->hardware($this->partName($unit, 'Loft Hinge'), $loftDoors * 2, 'LOFT_HINGE');
        }

        $bays = $layout['bays'] ?? [];
        if ($bays === []) {
            $bays = [['id' => 'bay-1', 'label' => 'Bay 1', 'width_mm' => null, 'sections' => [
                ['type' => 'SHELVES', 'shelf_count' => (int) ($p['shelf_count'] ?? 3), 'label' => 'Shelves'],
            ]]];
        }
        $bayWidths = $this->resolveBayWidths($bays, $internalW, $partT);

        for ($i = 1; $i < count($bayWidths); $i++) {
            $left = (string) ($bays[$i - 1]['label'] ?? ('Bay ' . $i));
            $right = (string) ($bays[$i]['label'] ?? ('Bay ' . ($i + 1)));
            $logical[] = $this->panel(
                $this->partName($unit, "Vertical Partition ({$left} / {$right})"),
                $internalMainH,
                $internalDepth,
                $partT,
                1,
                'partition',
                'VERTICAL_PARTITION'
            );
        }

        foreach ($bayWidths as $idx => $bayW) {
            $bay = $bays[$idx];
            $bayLabel = (string) ($bay['label'] ?? ('Bay ' . ($idx + 1)));
            $sections = $this->normalizeSections($bay['sections'] ?? [], $internalMainH);
            foreach ($sections as $section) {
                $type = strtoupper((string) ($section['type'] ?? 'OPEN'));
                $secLabel = (string) ($section['label'] ?? ucfirst(strtolower($type)));
                $secPrefix = "{$bayLabel} - {$secLabel}";
                if ($type === 'SHELVES') {
                    $count = max(0, (int) ($section['shelf_count'] ?? 1));
                    $shelfStyle = strtolower((string) ($section['shelf_style'] ?? 'standard'));
                    $isShoe = $shelfStyle === 'shoe';
                    $isPlate = $shelfStyle === 'plate_tray' || $shelfStyle === 'plate';
                    $isBottle = $shelfStyle === 'bottle';
                    if ($count > 0) {
                        $role = 'SHELF';
                        $vis = 'shelf';
                        $label = 'Shelf';
                        if ($isShoe) {
                            $role = 'SHELF_SHOE';
                            $vis = 'shoe';
                            $label = 'Shoe Shelf';
                        } elseif ($isPlate) {
                            $role = 'SHELF_PLATE_TRAY';
                            $vis = 'plate_tray';
                            $label = 'Plate Tray';
                        } elseif ($isBottle) {
                            $role = 'SHELF_BOTTLE';
                            $vis = 'bottle';
                            $label = 'Bottle Rack Shelf';
                        }
                        $logical[] = $this->panel(
                            $this->partName($unit, "{$secPrefix} - {$label}"),
                            max(1, $bayW - 2),
                            $internalDepth,
                            $t,
                            $count,
                            $vis,
                            $role
                        );
                        if ($isShoe) {
                            $logical[] = $this->hardware(
                                $this->partName($unit, "{$secPrefix} - Shoe Shelf Support"),
                                $count * 4,
                                'SHELF_PIN'
                            );
                        }
                        if ($isPlate || $isBottle) {
                            $logical[] = $this->hardware(
                                $this->partName($unit, "{$secPrefix} - Pull-Out Slide"),
                                $count * 2,
                                'PULL_OUT_SLIDE'
                            );
                        }
                        if ($isBottle) {
                            $logical[] = $this->hardware(
                                $this->partName($unit, "{$secPrefix} - Bottle Pull-Out Unit"),
                                1,
                                'BOTTLE_PULLOUT'
                            );
                        }
                    }
                } elseif ($type === 'DRAWERS') {
                    $count = max(1, (int) ($section['drawer_count'] ?? 1));
                    $drawerH = (float) ($section['drawer_height_mm'] ?? 180);
                    $drawerW = max(1, $bayW - 20);
                    $drawerD = max(1, $d - 40);
                    $isWicker = !empty($section['wicker_basket']);
                    if ($isWicker) {
                        // Bought baskets: false front + runners + basket SKUs (no wood box).
                        $logical[] = $this->panel(
                            $this->partName($unit, "{$secPrefix} - Basket Front"),
                            $drawerW,
                            $drawerH,
                            $t,
                            $count,
                            'wicker',
                            'WICKER_FRONT'
                        );
                        $logical[] = $this->hardware(
                            $this->partName($unit, "{$secPrefix} - Basket Slide"),
                            $count * 2,
                            'DRAWER_SLIDE'
                        );
                        $logical[] = $this->hardware(
                            $this->partName($unit, "{$secPrefix} - Wicker Basket"),
                            $count,
                            'WICKER_BASKET'
                        );
                    } else {
                        $logical[] = $this->panel($this->partName($unit, "{$secPrefix} - Drawer Front"), $drawerW, $drawerH, $t, $count, 'drawer', 'DRAWER_FRONT');
                        $logical[] = $this->panel($this->partName($unit, "{$secPrefix} - Drawer Side"), $drawerD, $drawerH, $t, $count * 2, 'drawer', 'DRAWER_SIDE');
                        $logical[] = $this->panel($this->partName($unit, "{$secPrefix} - Drawer Back"), $drawerW - (2 * $t), $drawerH, $t, $count, 'drawer', 'DRAWER_BACK');
                        $logical[] = $this->panel($this->partName($unit, "{$secPrefix} - Drawer Bottom"), $drawerW - (2 * $t), $drawerD - $t, $backT, $count, 'drawer', 'DRAWER_BOTTOM');
                        $logical[] = $this->hardware($this->partName($unit, "{$secPrefix} - Drawer Slide"), $count * 2, 'DRAWER_SLIDE');
                        if (!empty($section['cutlery_organizer'])) {
                            $logical[] = $this->hardware(
                                $this->partName($unit, "{$secPrefix} - Cutlery Organizer"),
                                max(1, $count),
                                'CUTLERY_ORGANIZER'
                            );
                        }
                    }
                } elseif ($type === 'HANGING') {
                    $style = strtolower((string) ($section['hanging_style'] ?? 'standard'));
                    if ($style === 'long') {
                        $style = 'long';
                    } elseif ($style === 'double' || $style === 'short') {
                        $style = 'double';
                    } else {
                        $style = 'standard';
                    }
                    $rodCount = $style === 'double' ? 2 : 1;
                    $cleatCount = $rodCount;
                    $logical[] = $this->panel(
                        $this->partName($unit, "{$secPrefix} - Rail Cleat"),
                        max(1, $bayW - 2),
                        80,
                        $t,
                        $cleatCount,
                        'hanging',
                        'HANGING_CLEAT'
                    );
                    $logical[] = $this->hardware(
                        $this->partName($unit, "{$secPrefix} - Hanging Rod"),
                        $rodCount,
                        'HANGING_ROD'
                    );
                    // Long hanging: top shelf above rod (FMOSV2 hanging_full top_shelf_count).
                    // Double/short: mid shelf between rods.
                    $extraShelves = 0;
                    if ($style === 'long') {
                        $extraShelves = max(0, (int) ($section['top_shelf_count'] ?? 1));
                    } elseif ($style === 'double') {
                        $extraShelves = 1;
                    }
                    if ($extraShelves > 0) {
                        $logical[] = $this->panel(
                            $this->partName($unit, "{$secPrefix} - Hanging Shelf"),
                            max(1, $bayW - 2),
                            $internalDepth,
                            $t,
                            $extraShelves,
                            'shelf',
                            'SHELF'
                        );
                    }
                } elseif ($type === 'OPEN' || $type === 'MIRROR') {
                    $secH = max(1.0, (float) ($section['height_mm'] ?? 1));
                    $this->appendNicheLiners(
                        $logical,
                        $unit,
                        $secPrefix,
                        $bayW,
                        $secH,
                        $internalDepth,
                        $t,
                        $backT
                    );
                    if (!empty($section['waste_bin'])) {
                        $logical[] = $this->hardware(
                            $this->partName($unit, "{$secPrefix} - Waste Bin"),
                            1,
                            'WASTE_BIN'
                        );
                        $logical[] = $this->hardware(
                            $this->partName($unit, "{$secPrefix} - Waste Bin Slide"),
                            2,
                            'DRAWER_SLIDE'
                        );
                    }
                    if (!empty($section['trouser_rack'])) {
                        $arms = max(1, (int) ($section['arm_count'] ?? 9));
                        $logical[] = $this->hardware(
                            $this->partName($unit, "{$secPrefix} - Trouser Rack"),
                            1,
                            'TROUSER_RACK'
                        );
                        $logical[] = $this->hardware(
                            $this->partName($unit, "{$secPrefix} - Trouser Rack Slide"),
                            2,
                            'DRAWER_SLIDE'
                        );
                        $logical[] = $this->hardware(
                            $this->partName($unit, "{$secPrefix} - Trouser Arm"),
                            $arms,
                            'TROUSER_ARM'
                        );
                    }
                    if (!empty($section['hob_bay'])) {
                        $logical[] = $this->hardware(
                            $this->partName($unit, "{$secPrefix} - Hob Clearance / Vent Note"),
                            1,
                            'HOB_CLEARANCE'
                        );
                    }
                    if ($type === 'MIRROR') {
                        $glass = FurnitureMirror::resolveGlass($section, $bayW, $secH);
                        $logical[] = $this->panel(
                            $this->partName($unit, "{$secPrefix} - Mirror"),
                            $glass['height_mm'],
                            $glass['width_mm'],
                            FurnitureMirror::THICKNESS_MM,
                            1,
                            'mirror',
                            'MIRROR_PANEL'
                        );
                    }
                }
            }
        }

        $shutterCount = (int) ($p['shutter_count'] ?? 0);
        if ($doorType !== 'NONE' && $shutterCount > 0) {
            $doorH = $internalMainH;
            $doorW = $internalW / $shutterCount;
            if ($doorType === 'SLIDING') {
                $logical[] = $this->panel($this->partName($unit, 'Sliding Door'), $doorH, $doorW, $t, $shutterCount, 'door', 'SLIDING_DOOR');
                $logical[] = $this->hardware($this->partName($unit, 'Sliding Track Set'), 1, 'SLIDING_TRACK');
            } else {
                $logical[] = $this->panel($this->partName($unit, 'Shutter / Door'), $doorH, $doorW, $t, $shutterCount, 'door', 'SHUTTER');
                $logical[] = $this->hardware($this->partName($unit, 'Hinge'), $shutterCount * 2, 'HINGE');
            }
        }

        return $logical;
    }

    /** Short product family label used as cutlist name prefix. */
    public function productLabel(string $templateCode, array $p = []): string
    {
        if (!empty($p['product_label']) && is_string($p['product_label'])) {
            return trim($p['product_label']);
        }
        return match (strtoupper($templateCode)) {
            'WARDROBE', 'WARDROBE_SLIDING', 'WARDROBE_LOFT' => 'Wardrobe',
            'TV_UNIT' => 'TV Unit',
            'KITCHEN_BASE' => 'Kitchen Base',
            'KITCHEN_CORNER' => 'Kitchen Corner',
            'KITCHEN_WALL' => 'Kitchen Wall',
            'KITCHEN_TALL' => 'Kitchen Tall',
            'CHEST_DRAWERS' => 'Chest',
            'BOOKCASE' => 'Bookcase',
            'CROCKERY' => 'Crockery',
            'VANITY' => 'Vanity',
            'STUDY_TABLE' => 'Study Table',
            default => ucwords(strtolower(str_replace('_', ' ', $templateCode))),
        };
    }

    private function partName(string $unit, string $part): string
    {
        return trim($unit) . ' - ' . trim($part);
    }

    /**
     * Normalize/merge layout from parameters.
     *
     * @param array<string,mixed> $p
     * @return array<string,mixed>
     */
    public function normalizeLayout(array $p): array
    {
        $layout = $p['layout'] ?? null;
        if (!is_array($layout)) {
            $shelfCount = (int) ($p['shelf_count'] ?? 3);
            $layout = [
                'plinth_height_mm' => 0,
                'partition_thickness_mm' => (float) ($p['carcass_thickness'] ?? 18),
                'door_type' => $p['door_type'] ?? 'HINGED',
                'loft' => ['enabled' => false, 'height_mm' => 0, 'shelf_count' => 0],
                'bays' => [[
                    'id' => 'bay-1',
                    'label' => 'Bay 1',
                    'width_mm' => null,
                    'sections' => [
                        ['type' => 'SHELVES', 'height_mm' => null, 'shelf_count' => $shelfCount, 'label' => 'Shelves'],
                    ],
                ]],
            ];
        }
        $layout['plinth_height_mm'] = (float) ($layout['plinth_height_mm'] ?? 0);
        $layout['partition_thickness_mm'] = (float) ($layout['partition_thickness_mm'] ?? ($p['carcass_thickness'] ?? 18));
        $layout['door_type'] = (string) ($layout['door_type'] ?? $p['door_type'] ?? 'HINGED');
        $layout['bays'] = array_values($layout['bays'] ?? []);
        $layout['loft'] = is_array($layout['loft'] ?? null) ? $layout['loft'] : ['enabled' => false, 'height_mm' => 0, 'shelf_count' => 0];
        return $layout;
    }

    /**
     * @param list<array<string,mixed>> $bays
     * @return list<float>
     */
    private function resolveBayWidths(array $bays, float $internalW, float $partT): array
    {
        $n = count($bays);
        if ($n === 0) {
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

    /**
     * @param list<array<string,mixed>> $sections
     * @return list<array<string,mixed>>
     */
    private function normalizeSections(array $sections, float $internalH): array
    {
        if ($sections === []) {
            return [['type' => 'OPEN', 'height_mm' => $internalH, 'label' => 'Open']];
        }
        $fixed = 0.0;
        $flexIdx = [];
        foreach ($sections as $i => $s) {
            if (isset($s['height_mm']) && $s['height_mm'] !== null && $s['height_mm'] !== '') {
                $fixed += (float) $s['height_mm'];
            } else {
                $flexIdx[] = $i;
            }
        }
        $remain = max(0.0, $internalH - $fixed);
        $each = count($flexIdx) > 0 ? $remain / count($flexIdx) : 0;
        foreach ($flexIdx as $i) {
            $sections[$i]['height_mm'] = $each;
        }
        return array_values($sections);
    }

    /**
     * Client-visible liners for open / dressing niches (EXPO by role default).
     * Covers partition/end-panel interiors that would otherwise stay interior laminate.
     *
     * @param list<array<string,mixed>> $logical
     */
    private function appendNicheLiners(
        array &$logical,
        string $unit,
        string $secPrefix,
        float $bayW,
        float $secH,
        float $internalDepth,
        float $t,
        float $backT
    ): void {
        $linerT = max(6.0, min($t, 12.0));
        $logical[] = $this->panel(
            $this->partName($unit, "{$secPrefix} - Niche Back"),
            $secH,
            max(1.0, $bayW - 2),
            max(6.0, $backT),
            1,
            'niche',
            'NICHE_BACK'
        );
        $logical[] = $this->panel(
            $this->partName($unit, "{$secPrefix} - Niche Side Left"),
            $secH,
            max(1.0, $internalDepth - 4),
            $linerT,
            1,
            'niche',
            'NICHE_SIDE_LEFT'
        );
        $logical[] = $this->panel(
            $this->partName($unit, "{$secPrefix} - Niche Side Right"),
            $secH,
            max(1.0, $internalDepth - 4),
            $linerT,
            1,
            'niche',
            'NICHE_SIDE_RIGHT'
        );
        $logical[] = $this->panel(
            $this->partName($unit, "{$secPrefix} - Niche Sill"),
            max(1.0, $bayW - 2),
            max(1.0, $internalDepth - 4),
            $linerT,
            1,
            'niche',
            'NICHE_SILL'
        );
        // Underside of niche top (client looks up into dressing opening).
        $logical[] = $this->panel(
            $this->partName($unit, "{$secPrefix} - Niche Header"),
            max(1.0, $bayW - 2),
            max(1.0, $internalDepth - 4),
            $linerT,
            1,
            'niche',
            'NICHE_HEADER'
        );
    }

    /** @return array<string,mixed> */
    private function panel(string $name, float $length, float $width, float $thickness, int $qty, string $group = 'panel', string $role = 'PANEL'): array
    {
        return [
            'name' => $name,
            'length_mm' => round(max(0, $length), 2),
            'width_mm' => round(max(0, $width), 2),
            'thickness_mm' => $thickness,
            'qty' => max(1, $qty),
            'type' => 'PANEL',
            'group' => $group,
            'role' => $role,
        ];
    }

    /** @return array<string,mixed> */
    private function hardware(string $name, int $qty, string $role = 'HARDWARE'): array
    {
        return [
            'name' => $name,
            'length_mm' => 0,
            'width_mm' => 0,
            'thickness_mm' => 0,
            'qty' => max(1, $qty),
            'type' => 'HARDWARE',
            'group' => 'hardware',
            'role' => $role,
        ];
    }
}
