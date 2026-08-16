<?php

declare(strict_types=1);

namespace Fmos\Domains\Furniture;

/**
 * Single source of truth for two-sided panel finish resolution.
 *
 * Rule:
 *   EXPO = false → both faces Interior laminate
 *   EXPO = true  → exterior/client face Exterior laminate, other face Interior
 *
 * Does not invent face geometry — callers supply which physical face is the
 * client-facing ("exterior") side for a given component role.
 */
final class PanelFinishResolver
{
    /**
     * @return array{
     *   expo: bool,
     *   face_exterior: array{name:string,expo:bool,finish_id:int|null,finish_role:string},
     *   face_interior: array{name:string,expo:bool,finish_id:int|null,finish_role:string}
     * }
     */
    public static function resolve(
        string $componentRole,
        array $expoMap,
        ?int $exteriorFinishId,
        ?int $interiorFinishId
    ): array {
        $isExpo = FurnitureExpo::isExpo($componentRole, $expoMap);
        $interiorId = $interiorFinishId ?: $exteriorFinishId;
        $exteriorId = $exteriorFinishId ?: $interiorFinishId;

        // Glass is never an EXPO laminate surface — keep finish resolution non-EXPO.
        if (strtoupper($componentRole) === 'MIRROR_PANEL') {
            return [
                'expo' => false,
                'face_exterior' => [
                    'name' => 'glass_front',
                    'expo' => false,
                    'finish_id' => null,
                    'finish_role' => 'mirror',
                ],
                'face_interior' => [
                    'name' => 'glass_back',
                    'expo' => false,
                    'finish_id' => null,
                    'finish_role' => 'mirror',
                ],
            ];
        }

        if ($isExpo) {
            return [
                'expo' => true,
                'face_exterior' => [
                    'name' => 'client_facing',
                    'expo' => true,
                    'finish_id' => $exteriorId,
                    'finish_role' => 'exterior',
                ],
                'face_interior' => [
                    'name' => 'cabinet_facing',
                    'expo' => false,
                    'finish_id' => $interiorId,
                    'finish_role' => 'interior',
                ],
            ];
        }

        return [
            'expo' => false,
            'face_exterior' => [
                'name' => 'outer',
                'expo' => false,
                'finish_id' => $interiorId,
                'finish_role' => 'interior',
            ],
            'face_interior' => [
                'name' => 'inner',
                'expo' => false,
                'finish_id' => $interiorId,
                'finish_role' => 'interior',
            ],
        ];
    }

    /**
     * Three.js BoxGeometry material slot for the client-facing face of a role.
     * Indices: 0=+X 1=-X 2=+Y 3=-Y 4=+Z 5=-Z
     */
    public static function expoFaceIndex(string $componentRole): ?int
    {
        return match (strtoupper($componentRole)) {
            'LEFT_PANEL', 'FILLER_LEFT' => 1,   // -X outside (wall / client side)
            'RIGHT_PANEL', 'FILLER_RIGHT' => 0, // +X outside
            'TOP_PANEL' => 2,    // +Y outside
            'BOTTOM_PANEL' => 3, // -Y outside
            'BACK_PANEL' => 5,   // -Z outside (rear of unit)
            'SHUTTER', 'SLIDING_DOOR', 'LOFT_SHUTTER', 'DRAWER_FRONT', 'NICHE_BACK' => 4, // +Z front
            'NICHE_SIDE_LEFT' => 0,  // +X toward niche opening
            'NICHE_SIDE_RIGHT' => 1, // -X toward niche opening
            'NICHE_SILL' => 2,       // +Y top of sill
            'NICHE_HEADER' => 3,     // -Y underside of niche top
            'MIRROR_PANEL' => 4, // glass front (+Z); not an EXPO laminate face
            default => null,
        };
    }

    /**
     * Primary finish used when a single material must be chosen (cutlist colour).
     *
     * @param array{expo:bool,face_exterior:array,face_interior:array} $faces
     */
    public static function primaryFinishId(array $faces): ?int
    {
        if (!empty($faces['expo'])) {
            $id = $faces['face_exterior']['finish_id'] ?? null;
            return $id !== null ? (int) $id : null;
        }
        $id = $faces['face_interior']['finish_id'] ?? null;
        return $id !== null ? (int) $id : null;
    }
}
