<?php

declare(strict_types=1);

namespace Fmos\Domains\Furniture;

/**
 * Installation filler panels (user-opt-in).
 *
 * Stored on furniture parameters as:
 *   parameters.fillers = {
 *     "left":  { "enabled": true,  "width_mm": 80 },
 *     "right": { "enabled": false, "width_mm": 50 }
 *   }
 *
 * Never auto-enabled from 3D gaps — only when the designer opts in.
 * When enabled, fillers are real manufacturable components (cutlist/BOM).
 */
final class FurnitureFillers
{
    public const MIN_WIDTH_MM = 10.0;
    public const MAX_WIDTH_MM = 300.0;
    public const DEFAULT_WIDTH_MM = 50.0;

    /**
     * @param array<string,mixed>|null $raw
     * @return array{left:array{enabled:bool,width_mm:float},right:array{enabled:bool,width_mm:float}}
     */
    public static function normalize(?array $raw): array
    {
        return [
            'left' => self::normalizeSide(is_array($raw['left'] ?? null) ? $raw['left'] : null),
            'right' => self::normalizeSide(is_array($raw['right'] ?? null) ? $raw['right'] : null),
        ];
    }

    /**
     * @param array<string,mixed> $parameters
     * @return array{left:array{enabled:bool,width_mm:float},right:array{enabled:bool,width_mm:float}}
     */
    public static function fromParameters(array $parameters): array
    {
        $raw = $parameters['fillers'] ?? null;
        return self::normalize(is_array($raw) ? $raw : null);
    }

    /**
     * @param array<string,mixed>|null $side
     * @return array{enabled:bool,width_mm:float}
     */
    private static function normalizeSide(?array $side): array
    {
        $enabled = !empty($side['enabled']);
        $width = isset($side['width_mm']) && is_numeric($side['width_mm'])
            ? (float) $side['width_mm']
            : self::DEFAULT_WIDTH_MM;
        $width = max(self::MIN_WIDTH_MM, min(self::MAX_WIDTH_MM, $width));
        return [
            'enabled' => $enabled,
            'width_mm' => round($width, 2),
        ];
    }

    /** Active left filler width, or 0. */
    public static function leftWidth(array $fillers): float
    {
        return !empty($fillers['left']['enabled']) ? (float) $fillers['left']['width_mm'] : 0.0;
    }

    /** Active right filler width, or 0. */
    public static function rightWidth(array $fillers): float
    {
        return !empty($fillers['right']['enabled']) ? (float) $fillers['right']['width_mm'] : 0.0;
    }
}
