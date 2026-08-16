<?php

declare(strict_types=1);

namespace Fmos\Domains\Furniture;

/**
 * Resolves partial internal-mirror glass size inside a dressing/mirror section.
 *
 * Mirror is an individual component — never implied to equal the full section.
 * Margin / explicit width+height control how much carcass surround remains visible.
 */
final class FurnitureMirror
{
    public const DEFAULT_MARGIN_MM = 80.0;
    public const THICKNESS_MM = 5.0;

    /**
     * @param array<string,mixed> $section
     * @return array{
     *   width_mm: float,
     *   height_mm: float,
     *   margin_x_mm: float,
     *   margin_y_mm: float,
     *   full_section: bool
     * }
     */
    public static function resolveGlass(array $section, float $bayW, float $secH): array
    {
        $bayW = max(1.0, $bayW);
        $secH = max(1.0, $secH);

        $hasW = isset($section['mirror_width_mm']) && $section['mirror_width_mm'] !== null && $section['mirror_width_mm'] !== '';
        $hasH = isset($section['mirror_height_mm']) && $section['mirror_height_mm'] !== null && $section['mirror_height_mm'] !== '';

        $margin = self::DEFAULT_MARGIN_MM;
        if (isset($section['mirror_margin_mm']) && $section['mirror_margin_mm'] !== null && $section['mirror_margin_mm'] !== '') {
            $margin = max(0.0, (float) $section['mirror_margin_mm']);
        }

        if ($hasW) {
            $mw = max(1.0, min($bayW, (float) $section['mirror_width_mm']));
        } else {
            $mw = max(1.0, $bayW - 2.0 * $margin);
        }
        if ($hasH) {
            $mh = max(1.0, min($secH, (float) $section['mirror_height_mm']));
        } else {
            $mh = max(1.0, $secH - 2.0 * $margin);
        }

        // Keep at least 1mm surround when not intentionally full-section.
        if (!$hasW && !$hasH && $margin <= 0.0) {
            $mw = $bayW;
            $mh = $secH;
        }

        $mx = max(0.0, ($bayW - $mw) / 2.0);
        $my = max(0.0, ($secH - $mh) / 2.0);
        $full = ($mw >= $bayW - 0.5) && ($mh >= $secH - 0.5);

        return [
            'width_mm' => round($mw, 2),
            'height_mm' => round($mh, 2),
            'margin_x_mm' => round($mx, 2),
            'margin_y_mm' => round($my, 2),
            'full_section' => $full,
        ];
    }
}
