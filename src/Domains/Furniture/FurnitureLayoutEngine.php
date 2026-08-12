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
        $backT = (float) ($p['back_thickness'] ?? 6);
        $layout = $this->normalizeLayout($p);
        $plinth = (float) ($layout['plinth_height_mm'] ?? 0);
        $partT = (float) ($layout['partition_thickness_mm'] ?? $t);
        $doorType = (string) ($p['door_type'] ?? $layout['door_type'] ?? 'HINGED');
        $loft = $layout['loft'] ?? ['enabled' => false, 'height_mm' => 0, 'shelf_count' => 0];
        $loftH = !empty($loft['enabled']) ? (float) ($loft['height_mm'] ?? 0) : 0.0;
        $mainH = max(1.0, $h - $plinth - $loftH);
        $internalW = max(1.0, $w - (2 * $t));
        $internalMainH = max(1.0, $mainH - (2 * $t));
        $unit = $this->productLabel($templateCode, $p);

        $logical = [];
        // Outer carcass
        $logical[] = $this->panel($this->partName($unit, 'Left Panel'), $h, $d, $t, 1, 'carcass', 'LEFT_PANEL');
        $logical[] = $this->panel($this->partName($unit, 'Right Panel'), $h, $d, $t, 1, 'carcass', 'RIGHT_PANEL');
        $logical[] = $this->panel($this->partName($unit, 'Top Panel'), $internalW, $d, $t, 1, 'carcass', 'TOP_PANEL');
        $logical[] = $this->panel($this->partName($unit, 'Bottom Panel'), $internalW, $d, $t, 1, 'carcass', 'BOTTOM_PANEL');
        $logical[] = $this->panel($this->partName($unit, 'Back Panel'), $h, $w, $backT, 1, 'carcass', 'BACK_PANEL');

        if ($plinth > 0) {
            $logical[] = $this->panel($this->partName($unit, 'Plinth Front'), $w, $plinth, $t, 1, 'plinth', 'PLINTH_FRONT');
            $logical[] = $this->panel($this->partName($unit, 'Plinth Side'), $d - $t, $plinth, $t, 2, 'plinth', 'PLINTH_SIDE');
        }

        if ($loftH > 0) {
            $logical[] = $this->panel($this->partName($unit, 'Loft Base Panel'), $internalW, max(1, $d - $t), $t, 1, 'loft', 'LOFT_BASE');
            $loftShelves = (int) ($loft['shelf_count'] ?? 0);
            if ($loftShelves > 0) {
                $logical[] = $this->panel($this->partName($unit, 'Loft Shelf'), $internalW, max(1, $d - $t), $t, $loftShelves, 'loft', 'LOFT_SHELF');
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
                $d - $t,
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
                    if ($count > 0) {
                        $logical[] = $this->panel(
                            $this->partName($unit, "{$secPrefix} - Shelf"),
                            max(1, $bayW - 2),
                            max(1, $d - $t),
                            $t,
                            $count,
                            'shelf',
                            'SHELF'
                        );
                    }
                } elseif ($type === 'DRAWERS') {
                    $count = max(1, (int) ($section['drawer_count'] ?? 1));
                    $drawerH = (float) ($section['drawer_height_mm'] ?? 180);
                    $drawerW = max(1, $bayW - 20);
                    $drawerD = max(1, $d - 40);
                    $logical[] = $this->panel($this->partName($unit, "{$secPrefix} - Drawer Front"), $drawerW, $drawerH, $t, $count, 'drawer', 'DRAWER_FRONT');
                    $logical[] = $this->panel($this->partName($unit, "{$secPrefix} - Drawer Side"), $drawerD, $drawerH, $t, $count * 2, 'drawer', 'DRAWER_SIDE');
                    $logical[] = $this->panel($this->partName($unit, "{$secPrefix} - Drawer Back"), $drawerW - (2 * $t), $drawerH, $t, $count, 'drawer', 'DRAWER_BACK');
                    $logical[] = $this->panel($this->partName($unit, "{$secPrefix} - Drawer Bottom"), $drawerW - (2 * $t), $drawerD - $t, $backT, $count, 'drawer', 'DRAWER_BOTTOM');
                    $logical[] = $this->hardware($this->partName($unit, "{$secPrefix} - Drawer Slide"), $count * 2, 'DRAWER_SLIDE');
                } elseif ($type === 'HANGING') {
                    // Rail/cleat board + hanging rod so the section appears on cutlist and hardware list
                    $logical[] = $this->panel(
                        $this->partName($unit, "{$secPrefix} - Rail Cleat"),
                        max(1, $bayW - 2),
                        80,
                        $t,
                        1,
                        'hanging',
                        'HANGING_CLEAT'
                    );
                    $logical[] = $this->hardware($this->partName($unit, "{$secPrefix} - Hanging Rod"), 1, 'HANGING_ROD');
                } elseif ($type === 'OPEN') {
                    // Optional back-of-niche shelf strip not required; keep open void.
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
