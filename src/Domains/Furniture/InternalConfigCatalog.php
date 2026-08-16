<?php

declare(strict_types=1);

namespace Fmos\Domains\Furniture;

/**
 * Named internal configurations that compile to layout section patches.
 *
 * Phase 1 maps to existing section types only (HANGING/SHELVES/DRAWERS/OPEN/MIRROR).
 * Specialty kitchen configs marked implemented=false are catalog stubs for later LayoutEngine work.
 *
 * Dimensional thresholds tagged rule_source=business_default are new explicit defaults,
 * not pre-existing manufacturing rules verified from elsewhere in the codebase.
 */
final class InternalConfigCatalog
{
    public const SECTION_TYPES = ['HANGING', 'SHELVES', 'DRAWERS', 'OPEN', 'MIRROR'];

    /**
     * @return array<string, array<string,mixed>>
     */
    public static function all(): array
    {
        return [
            // —— Wardrobe / shared storage ——
            'CFG_HANGING' => [
                'id' => 'CFG_HANGING',
                'name' => 'Hanging section',
                'category' => 'STORAGE',
                'description' => 'Hanging rod + cleat (existing LayoutEngine HANGING)',
                'maps_to_section_type' => 'HANGING',
                'implemented' => true,
                'tier' => 'recommended',
                'eligibility' => [
                    'min_height_mm' => 900,
                    'min_width_mm' => 300,
                    'min_depth_mm' => 400,
                    'rule_source' => 'business_default',
                ],
                'section_defaults' => [
                    'type' => 'HANGING',
                    'height_mm' => 1100,
                    'label' => 'Hanging',
                ],
                'incompatible_with' => [],
                'kitchen_preset' => null,
            ],
            'CFG_SHELVES' => [
                'id' => 'CFG_SHELVES',
                'name' => 'Adjustable shelves',
                'category' => 'STORAGE',
                'description' => 'Fixed/adjustable shelf pack (existing SHELVES)',
                'maps_to_section_type' => 'SHELVES',
                'implemented' => true,
                'tier' => 'recommended',
                'eligibility' => [
                    'min_height_mm' => 200,
                    'min_width_mm' => 250,
                    'min_depth_mm' => 250,
                    'rule_source' => 'business_default',
                ],
                'section_defaults' => [
                    'type' => 'SHELVES',
                    'height_mm' => null,
                    'shelf_count' => 3,
                    'label' => 'Shelves',
                ],
                'incompatible_with' => [],
                'kitchen_preset' => 'shelf',
            ],
            'CFG_DRAWERS' => [
                'id' => 'CFG_DRAWERS',
                'name' => 'Drawer pack',
                'category' => 'STORAGE',
                'description' => 'Drawer fronts/boxes + slides (existing DRAWERS)',
                'maps_to_section_type' => 'DRAWERS',
                'implemented' => true,
                'tier' => 'optional',
                'eligibility' => [
                    'min_height_mm' => 350,
                    'min_width_mm' => 300,
                    'min_depth_mm' => 350,
                    'rule_source' => 'business_default',
                ],
                'section_defaults' => [
                    'type' => 'DRAWERS',
                    'height_mm' => 750,
                    'drawer_count' => 3,
                    'drawer_height_mm' => 200,
                    'label' => 'Drawers',
                ],
                'layout_overrides' => [],
                'incompatible_with' => [],
                'kitchen_preset' => 'drawers',
            ],
            'CFG_OPEN' => [
                'id' => 'CFG_OPEN',
                'name' => 'Open niche',
                'category' => 'STORAGE',
                'description' => 'Open niche with liners (existing OPEN)',
                'maps_to_section_type' => 'OPEN',
                'implemented' => true,
                'tier' => 'optional',
                'eligibility' => [
                    'min_height_mm' => 150,
                    'min_width_mm' => 250,
                    'min_depth_mm' => 250,
                    'rule_source' => 'business_default',
                ],
                'section_defaults' => [
                    'type' => 'OPEN',
                    'height_mm' => null,
                    'label' => 'Open',
                ],
                'incompatible_with' => [],
                'kitchen_preset' => 'open',
            ],
            'CFG_MIRROR' => [
                'id' => 'CFG_MIRROR',
                'name' => 'Internal mirror',
                'category' => 'ACCESSORY',
                'description' => 'Mirror glass in niche (existing MIRROR; does not force unit EXPO)',
                'maps_to_section_type' => 'MIRROR',
                'implemented' => true,
                'tier' => 'optional',
                'eligibility' => [
                    'min_height_mm' => 400,
                    'min_width_mm' => 300,
                    'min_depth_mm' => 300,
                    'rule_source' => 'business_default',
                ],
                'section_defaults' => [
                    'type' => 'MIRROR',
                    'height_mm' => null,
                    'mirror_margin_mm' => 80,
                    'label' => 'Mirror',
                ],
                'incompatible_with' => [],
                'kitchen_preset' => null,
            ],

            // —— Kitchen-oriented aliases (same section types) ——
            'CFG_KB_SHELF' => [
                'id' => 'CFG_KB_SHELF',
                'name' => 'Kitchen standard shelf',
                'category' => 'KITCHEN',
                'description' => 'Single adjustable shelf for kitchen base',
                'maps_to_section_type' => 'SHELVES',
                'implemented' => true,
                'tier' => 'recommended',
                'eligibility' => [
                    'min_height_mm' => 400,
                    'min_width_mm' => 300,
                    'min_depth_mm' => 400,
                    'rule_source' => 'business_default',
                ],
                'section_defaults' => [
                    'type' => 'SHELVES',
                    'height_mm' => null,
                    'shelf_count' => 1,
                    'label' => 'Shelf',
                ],
                'replace_bay' => true,
                'incompatible_with' => ['CFG_KB_DRAWERS', 'CFG_KB_SINK'],
                'kitchen_preset' => 'shelf',
            ],
            'CFG_KB_DRAWERS' => [
                'id' => 'CFG_KB_DRAWERS',
                'name' => 'Kitchen drawer base',
                'category' => 'KITCHEN',
                'description' => 'Full drawer pack; sets door_type NONE',
                'maps_to_section_type' => 'DRAWERS',
                'implemented' => true,
                'tier' => 'optional',
                'eligibility' => [
                    'min_height_mm' => 500,
                    'min_width_mm' => 400,
                    'min_depth_mm' => 450,
                    'rule_source' => 'business_default',
                ],
                'section_defaults' => [
                    'type' => 'DRAWERS',
                    'height_mm' => null,
                    'drawer_count' => 3,
                    'drawer_height_mm' => 180,
                    'label' => 'Drawers',
                ],
                'layout_overrides' => [
                    'door_type' => 'NONE',
                ],
                'replace_bay' => true,
                'incompatible_with' => ['CFG_KB_SHELF', 'CFG_KB_SINK'],
                'kitchen_preset' => 'drawers',
            ],
            'CFG_KB_SINK' => [
                'id' => 'CFG_KB_SINK',
                'name' => 'Sink / plumbing clearance',
                'category' => 'KITCHEN',
                'description' => 'Open niche for plumbing (Phase 1 = OPEN; specialty sink carcass later)',
                'maps_to_section_type' => 'OPEN',
                'implemented' => true,
                'tier' => 'optional',
                'eligibility' => [
                    'min_height_mm' => 500,
                    'min_width_mm' => 450,
                    'min_depth_mm' => 450,
                    'rule_source' => 'business_default',
                ],
                'section_defaults' => [
                    'type' => 'OPEN',
                    'height_mm' => null,
                    'label' => 'Plumbing',
                ],
                'replace_bay' => true,
                'incompatible_with' => ['CFG_KB_SHELF', 'CFG_KB_DRAWERS', 'CFG_KB_PLATE_TRAY'],
                'kitchen_preset' => 'sink',
            ],

            // —— Future specialty (stubs — not generated by LayoutEngine yet) ——
            'CFG_KB_PLATE_TRAY' => [
                'id' => 'CFG_KB_PLATE_TRAY',
                'name' => 'Plate tray',
                'category' => 'KITCHEN',
                'description' => 'Plate storage tray — requires LayoutEngine support (not yet implemented)',
                'maps_to_section_type' => null,
                'implemented' => false,
                'tier' => 'optional',
                'eligibility' => [
                    'min_height_mm' => 500,
                    'min_width_mm' => 500,
                    'min_depth_mm' => 500,
                    'rule_source' => 'business_default',
                ],
                'section_defaults' => [],
                'incompatible_with' => ['CFG_KB_SINK'],
                'kitchen_preset' => null,
                'unavailable_reason' => 'Plate tray manufacturing components are not implemented yet.',
            ],
            'CFG_KB_CUTLERY' => [
                'id' => 'CFG_KB_CUTLERY',
                'name' => 'Cutlery drawer',
                'category' => 'KITCHEN',
                'description' => 'Drawer + organizer accessory — LayoutEngine accessory not yet implemented',
                'maps_to_section_type' => 'DRAWERS',
                'implemented' => false,
                'tier' => 'optional',
                'eligibility' => [
                    'min_height_mm' => 150,
                    'min_width_mm' => 400,
                    'min_depth_mm' => 450,
                    'rule_source' => 'business_default',
                ],
                'section_defaults' => [],
                'incompatible_with' => [],
                'kitchen_preset' => null,
                'unavailable_reason' => 'Cutlery organizer hardware is not modeled yet.',
            ],
            'CFG_KB_BOTTLE' => [
                'id' => 'CFG_KB_BOTTLE',
                'name' => 'Bottle pull-out',
                'category' => 'KITCHEN',
                'description' => 'Narrow bottle pull-out — requires LayoutEngine + hardware model',
                'maps_to_section_type' => null,
                'implemented' => false,
                'tier' => 'optional',
                'eligibility' => [
                    'min_height_mm' => 500,
                    'min_width_mm' => 150,
                    'max_width_mm' => 300,
                    'min_depth_mm' => 450,
                    'rule_source' => 'business_default',
                ],
                'section_defaults' => [],
                'incompatible_with' => [],
                'kitchen_preset' => null,
                'unavailable_reason' => 'Bottle pull-out hardware is not implemented yet.',
            ],
            'CFG_KB_WASTE' => [
                'id' => 'CFG_KB_WASTE',
                'name' => 'Waste bin module',
                'category' => 'KITCHEN',
                'description' => 'Waste pull-out — requires LayoutEngine + hardware model',
                'maps_to_section_type' => null,
                'implemented' => false,
                'tier' => 'optional',
                'eligibility' => [
                    'min_height_mm' => 500,
                    'min_width_mm' => 400,
                    'min_depth_mm' => 450,
                    'rule_source' => 'business_default',
                ],
                'section_defaults' => [],
                'incompatible_with' => [],
                'kitchen_preset' => null,
                'unavailable_reason' => 'Waste-bin pull-out is not implemented yet.',
            ],
            'CFG_HANGING_LONG' => [
                'id' => 'CFG_HANGING_LONG',
                'name' => 'Long hanging',
                'category' => 'WARDROBE',
                'description' => 'Full-height hanging for coats/dresses — variant params not yet in LayoutEngine',
                'maps_to_section_type' => 'HANGING',
                'implemented' => false,
                'tier' => 'optional',
                'eligibility' => [
                    'min_height_mm' => 1400,
                    'min_width_mm' => 400,
                    'min_depth_mm' => 500,
                    'rule_source' => 'business_default',
                ],
                'section_defaults' => [],
                'incompatible_with' => [],
                'kitchen_preset' => null,
                'unavailable_reason' => 'Long-hanging variant parameters are not implemented yet; use Hanging section.',
            ],
            'CFG_SHOE' => [
                'id' => 'CFG_SHOE',
                'name' => 'Shoe storage',
                'category' => 'WARDROBE',
                'description' => 'Angled/pull-out shoe shelves — not yet in LayoutEngine',
                'maps_to_section_type' => null,
                'implemented' => false,
                'tier' => 'optional',
                'eligibility' => [
                    'min_height_mm' => 400,
                    'min_width_mm' => 400,
                    'min_depth_mm' => 350,
                    'rule_source' => 'business_default',
                ],
                'section_defaults' => [],
                'incompatible_with' => [],
                'kitchen_preset' => null,
                'unavailable_reason' => 'Shoe storage components are not implemented yet.',
            ],
        ];
    }

    /** @return array<string,mixed>|null */
    public static function get(string $id): ?array
    {
        $all = self::all();
        return $all[$id] ?? null;
    }

    /**
     * Layout presets formerly hardcoded in furniture.js — served from catalog.
     *
     * @return list<array{id:string,label:string,category:string,config_ids:list<string>,layout:array<string,mixed>}>
     */
    public static function layoutPresets(?string $category = null): array
    {
        $common = static fn (string $doorType = 'HINGED') => [
            'partition_thickness_mm' => 18,
            'door_type' => $doorType,
            'loft' => ['enabled' => false, 'height_mm' => 600, 'shelf_count' => 1],
        ];

        $presets = [
            [
                'id' => 'hang-draw-shelf',
                'label' => 'Hang + drawers + shelves',
                'category' => 'WARDROBE',
                'config_ids' => ['CFG_HANGING', 'CFG_DRAWERS', 'CFG_SHELVES'],
                'layout' => $common() + [
                    'plinth_height_mm' => 110,
                    'bays' => [
                        [
                            'id' => 'bay-1',
                            'label' => 'Hang/Draw',
                            'width_mm' => null,
                            'sections' => [
                                ['type' => 'HANGING', 'height_mm' => 1100, 'label' => 'Hanging'],
                                ['type' => 'DRAWERS', 'height_mm' => 750, 'drawer_count' => 3, 'drawer_height_mm' => 200, 'label' => 'Drawers'],
                                ['type' => 'SHELVES', 'height_mm' => null, 'shelf_count' => 1, 'label' => 'Bottom'],
                            ],
                        ],
                        [
                            'id' => 'bay-2',
                            'label' => 'Shelves',
                            'width_mm' => null,
                            'sections' => [
                                ['type' => 'SHELVES', 'height_mm' => null, 'shelf_count' => 5, 'label' => 'Shelves'],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'full-hang',
                'label' => 'Full hanging',
                'category' => 'WARDROBE',
                'config_ids' => ['CFG_HANGING'],
                'layout' => $common() + [
                    'plinth_height_mm' => 110,
                    'bays' => [[
                        'id' => 'bay-1',
                        'label' => 'Hanging',
                        'width_mm' => null,
                        'sections' => [['type' => 'HANGING', 'height_mm' => null, 'label' => 'Hanging']],
                    ]],
                ],
            ],
            [
                'id' => 'with-loft',
                'label' => '2 bay + loft',
                'category' => 'WARDROBE',
                'config_ids' => ['CFG_HANGING', 'CFG_DRAWERS', 'CFG_SHELVES'],
                'layout' => [
                    'partition_thickness_mm' => 18,
                    'door_type' => 'HINGED',
                    'plinth_height_mm' => 110,
                    'loft' => ['enabled' => true, 'height_mm' => 600, 'shelf_count' => 1],
                    'bays' => [
                        [
                            'id' => 'bay-1',
                            'label' => 'Left',
                            'width_mm' => null,
                            'sections' => [
                                ['type' => 'HANGING', 'height_mm' => 1100, 'label' => 'Hanging'],
                                ['type' => 'DRAWERS', 'height_mm' => null, 'drawer_count' => 3, 'drawer_height_mm' => 200, 'label' => 'Drawers'],
                            ],
                        ],
                        [
                            'id' => 'bay-2',
                            'label' => 'Right',
                            'width_mm' => null,
                            'sections' => [
                                ['type' => 'SHELVES', 'height_mm' => null, 'shelf_count' => 5, 'label' => 'Shelves'],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'tv-3bay',
                'label' => 'Open centre + side storage',
                'category' => 'TV_UNIT',
                'config_ids' => ['CFG_OPEN', 'CFG_DRAWERS', 'CFG_SHELVES'],
                'layout' => $common() + [
                    'plinth_height_mm' => 80,
                    'bays' => [
                        [
                            'id' => 'bay-1',
                            'label' => 'Left',
                            'width_mm' => null,
                            'sections' => [
                                ['type' => 'OPEN', 'height_mm' => 220, 'label' => 'Niche'],
                                ['type' => 'DRAWERS', 'height_mm' => null, 'drawer_count' => 2, 'drawer_height_mm' => 180, 'label' => 'Drawers'],
                            ],
                        ],
                        [
                            'id' => 'bay-2',
                            'label' => 'TV niche',
                            'width_mm' => null,
                            'sections' => [['type' => 'OPEN', 'height_mm' => null, 'label' => 'Open']],
                        ],
                        [
                            'id' => 'bay-3',
                            'label' => 'Right',
                            'width_mm' => null,
                            'sections' => [
                                ['type' => 'OPEN', 'height_mm' => 220, 'label' => 'Niche'],
                                ['type' => 'SHELVES', 'shelf_count' => 1, 'height_mm' => null, 'label' => 'Shelf'],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'tv-drawers',
                'label' => 'All drawers',
                'category' => 'TV_UNIT',
                'config_ids' => ['CFG_DRAWERS'],
                'layout' => $common() + [
                    'plinth_height_mm' => 80,
                    'bays' => [[
                        'id' => 'bay-1',
                        'label' => 'Drawers',
                        'width_mm' => null,
                        'sections' => [[
                            'type' => 'DRAWERS',
                            'height_mm' => null,
                            'drawer_count' => 3,
                            'drawer_height_mm' => 160,
                            'label' => 'Drawers',
                        ]],
                    ]],
                ],
            ],
            [
                'id' => 'kit-shelf',
                'label' => 'Single shelf',
                'category' => 'KITCHEN',
                'config_ids' => ['CFG_KB_SHELF'],
                'layout' => $common() + [
                    'plinth_height_mm' => 100,
                    'bays' => [[
                        'id' => 'bay-1',
                        'label' => 'Cabinet',
                        'width_mm' => null,
                        'sections' => [['type' => 'SHELVES', 'shelf_count' => 1, 'height_mm' => null, 'label' => 'Shelf']],
                    ]],
                ],
            ],
            [
                'id' => 'kit-drawers',
                'label' => 'Drawer pack',
                'category' => 'KITCHEN',
                'config_ids' => ['CFG_KB_DRAWERS'],
                'layout' => [
                    'partition_thickness_mm' => 18,
                    'door_type' => 'NONE',
                    'plinth_height_mm' => 100,
                    'loft' => ['enabled' => false, 'height_mm' => 0, 'shelf_count' => 0],
                    'bays' => [[
                        'id' => 'bay-1',
                        'label' => 'Drawers',
                        'width_mm' => null,
                        'sections' => [[
                            'type' => 'DRAWERS',
                            'drawer_count' => 3,
                            'drawer_height_mm' => 150,
                            'height_mm' => null,
                            'label' => 'Drawers',
                        ]],
                    ]],
                ],
            ],
            [
                'id' => 'kit-pantry',
                'label' => 'Pantry shelves',
                'category' => 'KITCHEN',
                'config_ids' => ['CFG_SHELVES'],
                'layout' => $common() + [
                    'plinth_height_mm' => 100,
                    'bays' => [[
                        'id' => 'bay-1',
                        'label' => 'Pantry',
                        'width_mm' => null,
                        'sections' => [['type' => 'SHELVES', 'shelf_count' => 5, 'height_mm' => null, 'label' => 'Shelves']],
                    ]],
                ],
            ],
            [
                'id' => 'kit-sink',
                'label' => 'Sink / plumbing',
                'category' => 'KITCHEN',
                'config_ids' => ['CFG_KB_SINK'],
                'layout' => $common() + [
                    'plinth_height_mm' => 100,
                    'bays' => [[
                        'id' => 'bay-1',
                        'label' => 'Sink',
                        'width_mm' => null,
                        'sections' => [['type' => 'OPEN', 'height_mm' => null, 'label' => 'Plumbing']],
                    ]],
                ],
            ],
            [
                'id' => 'chest',
                'label' => '4 drawers',
                'category' => 'STORAGE',
                'config_ids' => ['CFG_DRAWERS'],
                'layout' => [
                    'partition_thickness_mm' => 18,
                    'door_type' => 'NONE',
                    'plinth_height_mm' => 80,
                    'loft' => ['enabled' => false, 'height_mm' => 0, 'shelf_count' => 0],
                    'bays' => [[
                        'id' => 'bay-1',
                        'label' => 'Chest',
                        'width_mm' => null,
                        'sections' => [[
                            'type' => 'DRAWERS',
                            'drawer_count' => 4,
                            'drawer_height_mm' => 180,
                            'height_mm' => null,
                            'label' => 'Drawers',
                        ]],
                    ]],
                ],
            ],
            [
                'id' => 'books',
                'label' => 'Open shelves',
                'category' => 'STORAGE',
                'config_ids' => ['CFG_SHELVES'],
                'layout' => [
                    'partition_thickness_mm' => 18,
                    'door_type' => 'NONE',
                    'plinth_height_mm' => 0,
                    'loft' => ['enabled' => false, 'height_mm' => 0, 'shelf_count' => 0],
                    'bays' => [[
                        'id' => 'bay-1',
                        'label' => 'Shelves',
                        'width_mm' => null,
                        'sections' => [['type' => 'SHELVES', 'shelf_count' => 5, 'height_mm' => null, 'label' => 'Shelves']],
                    ]],
                ],
            ],
            [
                'id' => 'vanity-draw',
                'label' => '2 drawers',
                'category' => 'BATHROOM',
                'config_ids' => ['CFG_DRAWERS'],
                'layout' => $common() + [
                    'plinth_height_mm' => 80,
                    'bays' => [[
                        'id' => 'bay-1',
                        'label' => 'Vanity',
                        'width_mm' => null,
                        'sections' => [[
                            'type' => 'DRAWERS',
                            'drawer_count' => 2,
                            'drawer_height_mm' => 180,
                            'height_mm' => null,
                            'label' => 'Drawers',
                        ]],
                    ]],
                ],
            ],
        ];

        if ($category === null || $category === '') {
            return $presets;
        }
        $cat = strtoupper($category);
        return array_values(array_filter(
            $presets,
            static fn ($p) => strtoupper((string) $p['category']) === $cat
        ));
    }

    /** Map kitchen composition spawn preset → config id. */
    public static function configIdForKitchenPreset(string $preset): string
    {
        return match (strtolower($preset)) {
            'drawers' => 'CFG_KB_DRAWERS',
            'sink', 'open' => 'CFG_KB_SINK',
            default => 'CFG_KB_SHELF',
        };
    }
}
