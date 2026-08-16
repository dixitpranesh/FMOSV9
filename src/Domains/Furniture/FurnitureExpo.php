<?php

declare(strict_types=1);

namespace Fmos\Domains\Furniture;

/**
 * EXPO = client-visible / decorative surfaces (independent of doors).
 *
 * Stored on furniture parameters as:
 *   parameters.expo = { "LEFT_PANEL": true, "RIGHT_PANEL": false, ... }
 *
 * Missing keys inherit defaults (doors true, everything else false).
 */
final class FurnitureExpo
{
    public const ROLES = [
        'LEFT_PANEL' => 'Left Panel',
        'RIGHT_PANEL' => 'Right Panel',
        'TOP_PANEL' => 'Top Panel',
        'BOTTOM_PANEL' => 'Bottom Panel',
        'BACK_PANEL' => 'Back Panel',
        'SHELF' => 'Shelves',
        'VERTICAL_PARTITION' => 'Vertical Partition',
        'PLINTH_FRONT' => 'Plinth Front',
        'PLINTH_SIDE' => 'Plinth Side',
        'SHUTTER' => 'Shutter / Door',
        'SLIDING_DOOR' => 'Sliding Door',
        'LOFT_SHUTTER' => 'Loft Shutter',
        'LOFT_SHELF' => 'Loft Shelf',
        'LOFT_BASE' => 'Loft Base',
        'HANGING_CLEAT' => 'Hanging Cleat',
        'DRAWER_FRONT' => 'Drawer Front',
        'MIRROR_PANEL' => 'Mirror (glass)',
        'NICHE_BACK' => 'Dressing Niche Back',
        'NICHE_SIDE_LEFT' => 'Dressing Niche Side (Left)',
        'NICHE_SIDE_RIGHT' => 'Dressing Niche Side (Right)',
        'NICHE_SILL' => 'Dressing Niche Sill',
        'NICHE_HEADER' => 'Dressing Niche Header',
        'FILLER_LEFT' => 'Left Filler',
        'FILLER_RIGHT' => 'Right Filler',
    ];

    /** Roles that default to EXPO without user action (doors/fronts/fillers/niche surrounds). */
    public static function defaultTrueRoles(): array
    {
        // Mirror is glass — never implied EXPO laminate. Niche liners are client-visible surrounds.
        return [
            'SHUTTER', 'SLIDING_DOOR', 'LOFT_SHUTTER', 'DRAWER_FRONT',
            'NICHE_BACK', 'NICHE_SIDE_LEFT', 'NICHE_SIDE_RIGHT', 'NICHE_SILL', 'NICHE_HEADER',
            'FILLER_LEFT', 'FILLER_RIGHT',
        ];
    }

    /**
     * @param array<string,mixed>|null $expo
     * @return array<string,bool>
     */
    public static function normalize(?array $expo): array
    {
        $out = [];
        foreach (self::ROLES as $role => $_label) {
            if (is_array($expo) && array_key_exists($role, $expo)) {
                $out[$role] = (bool) $expo[$role];
            } else {
                $out[$role] = in_array($role, self::defaultTrueRoles(), true);
            }
        }
        return $out;
    }

    /**
     * @param array<string,mixed> $parameters
     */
    public static function fromParameters(array $parameters): array
    {
        $raw = $parameters['expo'] ?? null;
        return self::normalize(is_array($raw) ? $raw : null);
    }

    public static function isExpo(string $role, array $expoMap): bool
    {
        if ($role === '' || $role === 'HARDWARE' || $role === 'PANEL') {
            return false;
        }
        if (array_key_exists($role, $expoMap)) {
            return (bool) $expoMap[$role];
        }
        return in_array($role, self::defaultTrueRoles(), true);
    }

    /**
     * Eligible expo toggles for roles present on this furniture.
     *
     * @param list<array<string,mixed>> $componentRows
     * @return list<array{role:string,label:string,expo:bool,count:int}>
     */
    public static function optionsForComponents(array $componentRows, array $expoMap): array
    {
        $counts = [];
        foreach ($componentRows as $row) {
            if (($row['component_type'] ?? '') === 'HARDWARE') {
                continue;
            }
            $role = self::roleFromRow($row);
            if ($role === '' || !isset(self::ROLES[$role])) {
                continue;
            }
            $counts[$role] = ($counts[$role] ?? 0) + (int) ($row['quantity'] ?? 1);
        }
        $options = [];
        foreach (self::ROLES as $role => $label) {
            if (!isset($counts[$role])) {
                continue;
            }
            $options[] = [
                'role' => $role,
                'label' => $label,
                'expo' => self::isExpo($role, $expoMap),
                'count' => $counts[$role],
            ];
        }
        return $options;
    }

    /** @param array<string,mixed> $row */
    public static function roleFromRow(array $row): string
    {
        $geom = $row['geometry'] ?? [];
        if (is_string($geom)) {
            $geom = json_decode($geom, true) ?: [];
        }
        if (!empty($geom['role']) && is_string($geom['role'])) {
            return strtoupper($geom['role']);
        }
        $mfg = $row['manufacturing_data'] ?? [];
        if (is_string($mfg)) {
            $mfg = json_decode($mfg, true) ?: [];
        }
        if (!empty($mfg['role']) && is_string($mfg['role'])) {
            return strtoupper($mfg['role']);
        }
        return self::inferRoleFromName((string) ($row['name'] ?? ''));
    }

    public static function inferRoleFromName(string $name): string
    {
        $n = strtolower($name);
        if (str_contains($n, 'left panel') || str_contains($n, 'left side')) {
            return 'LEFT_PANEL';
        }
        if (str_contains($n, 'right panel') || str_contains($n, 'right side')) {
            return 'RIGHT_PANEL';
        }
        if (str_contains($n, 'top panel') || preg_match('/\btop\b/', $n)) {
            return 'TOP_PANEL';
        }
        if (str_contains($n, 'bottom panel') || preg_match('/\bbottom\b/', $n) && !str_contains($n, 'drawer')) {
            return 'BOTTOM_PANEL';
        }
        if (str_contains($n, 'niche side left') || (str_contains($n, 'niche side') && str_contains($n, 'left'))) {
            return 'NICHE_SIDE_LEFT';
        }
        if (str_contains($n, 'niche side right') || (str_contains($n, 'niche side') && str_contains($n, 'right'))) {
            return 'NICHE_SIDE_RIGHT';
        }
        if (str_contains($n, 'niche sill')) {
            return 'NICHE_SILL';
        }
        if (str_contains($n, 'niche header') || str_contains($n, 'niche top')) {
            return 'NICHE_HEADER';
        }
        if (str_contains($n, 'niche back') || (str_contains($n, 'dressing niche') && !str_contains($n, 'side') && !str_contains($n, 'sill') && !str_contains($n, 'header'))) {
            return 'NICHE_BACK';
        }
        if (str_contains($n, 'back panel') || (preg_match('/\bback\b/', $n) && !str_contains($n, 'drawer') && !str_contains($n, 'niche'))) {
            return 'BACK_PANEL';
        }
        if (str_contains($n, 'sliding door')) {
            return 'SLIDING_DOOR';
        }
        if (str_contains($n, 'loft shutter')) {
            return 'LOFT_SHUTTER';
        }
        if (str_contains($n, 'shutter') || str_contains($n, 'door')) {
            return 'SHUTTER';
        }
        if (str_contains($n, 'drawer front')) {
            return 'DRAWER_FRONT';
        }
        if (str_contains($n, 'mirror')) {
            return 'MIRROR_PANEL';
        }
        if (str_contains($n, 'partition')) {
            return 'VERTICAL_PARTITION';
        }
        if (str_contains($n, 'plinth front')) {
            return 'PLINTH_FRONT';
        }
        if (str_contains($n, 'plinth side')) {
            return 'PLINTH_SIDE';
        }
        if (str_contains($n, 'hanging') && str_contains($n, 'cleat')) {
            return 'HANGING_CLEAT';
        }
        if (str_contains($n, 'loft base')) {
            return 'LOFT_BASE';
        }
        if (str_contains($n, 'loft shelf')) {
            return 'LOFT_SHELF';
        }
        if (str_contains($n, 'shelf')) {
            return 'SHELF';
        }
        if (str_contains($n, 'left filler') || (str_contains($n, 'filler') && str_contains($n, 'left'))) {
            return 'FILLER_LEFT';
        }
        if (str_contains($n, 'right filler') || (str_contains($n, 'filler') && str_contains($n, 'right'))) {
            return 'FILLER_RIGHT';
        }
        return '';
    }
}
