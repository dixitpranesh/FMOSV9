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

        $logical = [];
        // Outer carcass sides span full height
        $logical[] = $this->panel('Left Side', $h, $d, $t, 1);
        $logical[] = $this->panel('Right Side', $h, $d, $t, 1);
        $logical[] = $this->panel('Top', $internalW, $d, $t, 1);
        $logical[] = $this->panel('Bottom', $internalW, $d, $t, 1);
        $logical[] = $this->panel('Back', $h, $w, $backT, 1);

        if ($plinth > 0) {
            $logical[] = $this->panel('Plinth Front', $w, $plinth, $t, 1);
            $logical[] = $this->panel('Plinth Side', $d - $t, $plinth, $t, 2);
        }

        if ($loftH > 0) {
            $logical[] = $this->panel('Loft Shelf / Base', $internalW, max(1, $d - $t), $t, 1);
            $loftShelves = (int) ($loft['shelf_count'] ?? 0);
            if ($loftShelves > 0) {
                $logical[] = $this->panel('Loft Shelf', $internalW, max(1, $d - $t), $t, $loftShelves);
            }
            $loftDoors = max(1, (int) ($p['shutter_count'] ?? count($layout['bays'])));
            $logical[] = $this->panel('Loft Shutter', $loftH - $t, $internalW / $loftDoors, $t, $loftDoors);
            $logical[] = $this->hardware('Loft Hinge', $loftDoors * 2);
        }

        $bays = $layout['bays'] ?? [];
        if ($bays === []) {
            $bays = [['id' => 'bay-1', 'label' => 'Main', 'width_mm' => null, 'sections' => [
                ['type' => 'SHELVES', 'shelf_count' => (int) ($p['shelf_count'] ?? 3)],
            ]]];
        }
        $bayWidths = $this->resolveBayWidths($bays, $internalW, $partT);
        $partitionCount = max(0, count($bayWidths) - 1);
        if ($partitionCount > 0) {
            $logical[] = $this->panel('Vertical Partition', $internalMainH, $d - $t, $partT, $partitionCount);
        }

        foreach ($bayWidths as $idx => $bayW) {
            $bay = $bays[$idx];
            $label = (string) ($bay['label'] ?? ('Bay ' . ($idx + 1)));
            $sections = $this->normalizeSections($bay['sections'] ?? [], $internalMainH);
            foreach ($sections as $sIdx => $section) {
                $type = strtoupper((string) ($section['type'] ?? 'OPEN'));
                $secLabel = (string) ($section['label'] ?? $type);
                $prefix = "{$label} {$secLabel}";
                if ($type === 'SHELVES') {
                    $count = max(0, (int) ($section['shelf_count'] ?? 1));
                    if ($count > 0) {
                        $logical[] = $this->panel("{$prefix} Shelf", max(1, $bayW - 2), max(1, $d - $t), $t, $count);
                    }
                } elseif ($type === 'DRAWERS') {
                    $count = max(1, (int) ($section['drawer_count'] ?? 1));
                    $drawerH = (float) ($section['drawer_height_mm'] ?? 180);
                    $drawerW = max(1, $bayW - 20);
                    $drawerD = max(1, $d - 40);
                    $logical[] = $this->panel("{$prefix} Drawer Front", $drawerW, $drawerH, $t, $count);
                    $logical[] = $this->panel("{$prefix} Drawer Side", $drawerD, $drawerH, $t, $count * 2);
                    $logical[] = $this->panel("{$prefix} Drawer Back", $drawerW - (2 * $t), $drawerH, $t, $count);
                    $logical[] = $this->panel("{$prefix} Drawer Bottom", $drawerW - (2 * $t), $drawerD - $t, $backT, $count);
                    $logical[] = $this->hardware("{$prefix} Drawer Slide", $count * 2);
                } elseif ($type === 'HANGING') {
                    $logical[] = $this->hardware("{$prefix} Hanging Rod", 1);
                }
                // OPEN: no extra panels
            }
        }

        $shutterCount = (int) ($p['shutter_count'] ?? 0);
        if ($doorType !== 'NONE' && $shutterCount > 0) {
            $doorH = $internalMainH;
            $doorW = $internalW / $shutterCount;
            $name = $doorType === 'SLIDING' ? 'Sliding Door' : 'Shutter';
            $logical[] = $this->panel($name, $doorH, $doorW, $t, $shutterCount);
            if ($doorType === 'HINGED') {
                $logical[] = $this->hardware('Hinge', $shutterCount * 2);
            } else {
                $logical[] = $this->hardware('Sliding Track Set', 1);
            }
        }

        return $logical;
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
            // Legacy simple wardrobe params
            $shelfCount = (int) ($p['shelf_count'] ?? 3);
            $layout = [
                'plinth_height_mm' => 0,
                'partition_thickness_mm' => (float) ($p['carcass_thickness'] ?? 18),
                'door_type' => $p['door_type'] ?? 'HINGED',
                'loft' => ['enabled' => false, 'height_mm' => 0, 'shelf_count' => 0],
                'bays' => [[
                    'id' => 'bay-1',
                    'label' => 'Main',
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
    private function panel(string $name, float $length, float $width, float $thickness, int $qty): array
    {
        return [
            'name' => $name,
            'length_mm' => round(max(0, $length), 2),
            'width_mm' => round(max(0, $width), 2),
            'thickness_mm' => $thickness,
            'qty' => max(1, $qty),
            'type' => 'PANEL',
        ];
    }

    /** @return array<string,mixed> */
    private function hardware(string $name, int $qty): array
    {
        return [
            'name' => $name,
            'length_mm' => 0,
            'width_mm' => 0,
            'thickness_mm' => 0,
            'qty' => max(1, $qty),
            'type' => 'HARDWARE',
        ];
    }
}
