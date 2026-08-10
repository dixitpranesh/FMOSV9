<?php

declare(strict_types=1);

namespace Fmos\Domains\Furniture;

/**
 * System furniture templates + default internal layouts.
 */
final class FurnitureTemplateCatalog
{
    /** @return array<string, array{name:string,category:string,description:string,parameters:array<string,mixed>}> */
    public static function all(): array
    {
        $commonCarcass = [
            'width' => ['default' => 2400, 'min' => 600, 'max' => 4500, 'unit' => 'mm'],
            'height' => ['default' => 2400, 'min' => 700, 'max' => 3000, 'unit' => 'mm'],
            'depth' => ['default' => 600, 'min' => 300, 'max' => 800, 'unit' => 'mm'],
            'carcass_thickness' => ['default' => 18, 'min' => 12, 'max' => 25, 'unit' => 'mm'],
            'back_thickness' => ['default' => 6, 'min' => 3, 'max' => 12, 'unit' => 'mm'],
            'shutter_count' => ['default' => 2, 'min' => 0, 'max' => 8, 'unit' => 'pcs'],
        ];

        return [
            'WARDROBE' => [
                'name' => 'Wardrobe (Custom Layout)',
                'category' => 'WARDROBE',
                'description' => 'Fully customizable bays, hanging, shelves, drawers, loft and plinth',
                'parameters' => $commonCarcass + [
                    'door_type' => ['default' => 'HINGED', 'type' => 'enum', 'options' => ['HINGED', 'SLIDING']],
                    'layout' => ['type' => 'layout', 'default' => self::defaultWardrobeLayout(false, 'HINGED')],
                ],
            ],
            'WARDROBE_SLIDING' => [
                'name' => 'Sliding Wardrobe',
                'category' => 'WARDROBE',
                'description' => 'Sliding doors with multi-bay internals',
                'parameters' => array_replace($commonCarcass, [
                    'width' => ['default' => 2550, 'min' => 1200, 'max' => 4500, 'unit' => 'mm'],
                    'height' => ['default' => 2400, 'min' => 1800, 'max' => 3000, 'unit' => 'mm'],
                    'shutter_count' => ['default' => 2, 'min' => 2, 'max' => 4, 'unit' => 'pcs'],
                    'door_type' => ['default' => 'SLIDING', 'type' => 'enum', 'options' => ['SLIDING', 'HINGED']],
                    'layout' => ['type' => 'layout', 'default' => self::defaultWardrobeLayout(false, 'SLIDING')],
                ]),
            ],
            'WARDROBE_LOFT' => [
                'name' => 'Wardrobe with Loft',
                'category' => 'WARDROBE',
                'description' => 'Main wardrobe plus loft storage above',
                'parameters' => array_replace($commonCarcass, [
                    'height' => ['default' => 2700, 'min' => 2100, 'max' => 3000, 'unit' => 'mm'],
                    'door_type' => ['default' => 'HINGED', 'type' => 'enum', 'options' => ['HINGED', 'SLIDING']],
                    'layout' => ['type' => 'layout', 'default' => self::defaultWardrobeLayout(true, 'HINGED')],
                ]),
            ],
            'TV_UNIT' => [
                'name' => 'TV Unit',
                'category' => 'TV_UNIT',
                'description' => 'Low media unit with open niches and drawers',
                'parameters' => [
                    'width' => ['default' => 1800, 'min' => 900, 'max' => 3000, 'unit' => 'mm'],
                    'height' => ['default' => 600, 'min' => 400, 'max' => 1200, 'unit' => 'mm'],
                    'depth' => ['default' => 450, 'min' => 350, 'max' => 600, 'unit' => 'mm'],
                    'carcass_thickness' => ['default' => 18, 'min' => 12, 'max' => 25, 'unit' => 'mm'],
                    'back_thickness' => ['default' => 6, 'min' => 3, 'max' => 12, 'unit' => 'mm'],
                    'shutter_count' => ['default' => 2, 'min' => 0, 'max' => 4, 'unit' => 'pcs'],
                    'door_type' => ['default' => 'HINGED', 'type' => 'enum', 'options' => ['HINGED']],
                    'layout' => ['type' => 'layout', 'default' => self::defaultTvLayout()],
                ],
            ],
            'KITCHEN_BASE' => [
                'name' => 'Kitchen Base Unit',
                'category' => 'KITCHEN',
                'description' => 'Floor kitchen cabinet with shelf/drawer options',
                'parameters' => [
                    'width' => ['default' => 600, 'min' => 300, 'max' => 1200, 'unit' => 'mm'],
                    'height' => ['default' => 720, 'min' => 600, 'max' => 900, 'unit' => 'mm'],
                    'depth' => ['default' => 560, 'min' => 450, 'max' => 650, 'unit' => 'mm'],
                    'carcass_thickness' => ['default' => 18, 'min' => 12, 'max' => 25, 'unit' => 'mm'],
                    'back_thickness' => ['default' => 6, 'min' => 3, 'max' => 12, 'unit' => 'mm'],
                    'shutter_count' => ['default' => 1, 'min' => 0, 'max' => 2, 'unit' => 'pcs'],
                    'door_type' => ['default' => 'HINGED', 'type' => 'enum', 'options' => ['HINGED']],
                    'layout' => ['type' => 'layout', 'default' => self::defaultKitchenBaseLayout()],
                ],
            ],
            'KITCHEN_WALL' => [
                'name' => 'Kitchen Wall Unit',
                'category' => 'KITCHEN',
                'description' => 'Wall-hung kitchen cabinet',
                'parameters' => [
                    'width' => ['default' => 600, 'min' => 300, 'max' => 1200, 'unit' => 'mm'],
                    'height' => ['default' => 720, 'min' => 400, 'max' => 900, 'unit' => 'mm'],
                    'depth' => ['default' => 320, 'min' => 280, 'max' => 400, 'unit' => 'mm'],
                    'carcass_thickness' => ['default' => 18, 'min' => 12, 'max' => 25, 'unit' => 'mm'],
                    'back_thickness' => ['default' => 6, 'min' => 3, 'max' => 12, 'unit' => 'mm'],
                    'shutter_count' => ['default' => 1, 'min' => 1, 'max' => 2, 'unit' => 'pcs'],
                    'door_type' => ['default' => 'HINGED', 'type' => 'enum', 'options' => ['HINGED']],
                    'layout' => ['type' => 'layout', 'default' => self::singleBayShelves(2, 0)],
                ],
            ],
            'KITCHEN_TALL' => [
                'name' => 'Kitchen Tall Unit',
                'category' => 'KITCHEN',
                'description' => 'Full-height pantry / appliance housing',
                'parameters' => [
                    'width' => ['default' => 600, 'min' => 450, 'max' => 900, 'unit' => 'mm'],
                    'height' => ['default' => 2100, 'min' => 1800, 'max' => 2400, 'unit' => 'mm'],
                    'depth' => ['default' => 560, 'min' => 450, 'max' => 650, 'unit' => 'mm'],
                    'carcass_thickness' => ['default' => 18, 'min' => 12, 'max' => 25, 'unit' => 'mm'],
                    'back_thickness' => ['default' => 6, 'min' => 3, 'max' => 12, 'unit' => 'mm'],
                    'shutter_count' => ['default' => 2, 'min' => 1, 'max' => 2, 'unit' => 'pcs'],
                    'door_type' => ['default' => 'HINGED', 'type' => 'enum', 'options' => ['HINGED']],
                    'layout' => ['type' => 'layout', 'default' => self::defaultTallLayout()],
                ],
            ],
            'CHEST_DRAWERS' => [
                'name' => 'Chest of Drawers',
                'category' => 'STORAGE',
                'description' => 'Standalone drawer chest',
                'parameters' => [
                    'width' => ['default' => 900, 'min' => 450, 'max' => 1500, 'unit' => 'mm'],
                    'height' => ['default' => 900, 'min' => 600, 'max' => 1200, 'unit' => 'mm'],
                    'depth' => ['default' => 450, 'min' => 350, 'max' => 600, 'unit' => 'mm'],
                    'carcass_thickness' => ['default' => 18, 'min' => 12, 'max' => 25, 'unit' => 'mm'],
                    'back_thickness' => ['default' => 6, 'min' => 3, 'max' => 12, 'unit' => 'mm'],
                    'shutter_count' => ['default' => 0, 'min' => 0, 'max' => 0, 'unit' => 'pcs'],
                    'door_type' => ['default' => 'NONE', 'type' => 'enum', 'options' => ['NONE']],
                    'layout' => ['type' => 'layout', 'default' => self::defaultChestLayout()],
                ],
            ],
            'BOOKCASE' => [
                'name' => 'Bookcase / Display',
                'category' => 'STORAGE',
                'description' => 'Open shelving unit with optional shutters',
                'parameters' => [
                    'width' => ['default' => 900, 'min' => 400, 'max' => 1800, 'unit' => 'mm'],
                    'height' => ['default' => 1800, 'min' => 900, 'max' => 2400, 'unit' => 'mm'],
                    'depth' => ['default' => 350, 'min' => 250, 'max' => 450, 'unit' => 'mm'],
                    'carcass_thickness' => ['default' => 18, 'min' => 12, 'max' => 25, 'unit' => 'mm'],
                    'back_thickness' => ['default' => 6, 'min' => 3, 'max' => 12, 'unit' => 'mm'],
                    'shutter_count' => ['default' => 0, 'min' => 0, 'max' => 2, 'unit' => 'pcs'],
                    'door_type' => ['default' => 'NONE', 'type' => 'enum', 'options' => ['NONE', 'HINGED']],
                    'layout' => ['type' => 'layout', 'default' => self::singleBayShelves(5, 0)],
                ],
            ],
            'CROCKERY' => [
                'name' => 'Crockery Unit',
                'category' => 'STORAGE',
                'description' => 'Display + storage unit for dining',
                'parameters' => [
                    'width' => ['default' => 1200, 'min' => 600, 'max' => 2000, 'unit' => 'mm'],
                    'height' => ['default' => 1800, 'min' => 1200, 'max' => 2400, 'unit' => 'mm'],
                    'depth' => ['default' => 400, 'min' => 300, 'max' => 500, 'unit' => 'mm'],
                    'carcass_thickness' => ['default' => 18, 'min' => 12, 'max' => 25, 'unit' => 'mm'],
                    'back_thickness' => ['default' => 6, 'min' => 3, 'max' => 12, 'unit' => 'mm'],
                    'shutter_count' => ['default' => 2, 'min' => 0, 'max' => 4, 'unit' => 'pcs'],
                    'door_type' => ['default' => 'HINGED', 'type' => 'enum', 'options' => ['HINGED']],
                    'layout' => ['type' => 'layout', 'default' => self::defaultCrockeryLayout()],
                ],
            ],
            'VANITY' => [
                'name' => 'Bathroom Vanity',
                'category' => 'BATHROOM',
                'description' => 'Bathroom cabinet with drawers/shutters',
                'parameters' => [
                    'width' => ['default' => 900, 'min' => 450, 'max' => 1500, 'unit' => 'mm'],
                    'height' => ['default' => 750, 'min' => 600, 'max' => 900, 'unit' => 'mm'],
                    'depth' => ['default' => 450, 'min' => 350, 'max' => 550, 'unit' => 'mm'],
                    'carcass_thickness' => ['default' => 18, 'min' => 12, 'max' => 25, 'unit' => 'mm'],
                    'back_thickness' => ['default' => 6, 'min' => 3, 'max' => 12, 'unit' => 'mm'],
                    'shutter_count' => ['default' => 2, 'min' => 0, 'max' => 2, 'unit' => 'pcs'],
                    'door_type' => ['default' => 'HINGED', 'type' => 'enum', 'options' => ['HINGED']],
                    'layout' => ['type' => 'layout', 'default' => self::defaultVanityLayout()],
                ],
            ],
            'STUDY_TABLE' => [
                'name' => 'Study Table',
                'category' => 'STUDY',
                'description' => 'Desk with optional side storage bay',
                'parameters' => [
                    'width' => ['default' => 1200, 'min' => 900, 'max' => 1800, 'unit' => 'mm'],
                    'height' => ['default' => 750, 'min' => 700, 'max' => 800, 'unit' => 'mm'],
                    'depth' => ['default' => 600, 'min' => 450, 'max' => 750, 'unit' => 'mm'],
                    'carcass_thickness' => ['default' => 18, 'min' => 12, 'max' => 25, 'unit' => 'mm'],
                    'back_thickness' => ['default' => 6, 'min' => 3, 'max' => 12, 'unit' => 'mm'],
                    'shutter_count' => ['default' => 1, 'min' => 0, 'max' => 2, 'unit' => 'pcs'],
                    'door_type' => ['default' => 'HINGED', 'type' => 'enum', 'options' => ['HINGED', 'NONE']],
                    'layout' => ['type' => 'layout', 'default' => self::defaultStudyLayout()],
                ],
            ],
        ];
    }

    /** @return array<string,mixed> */
    public static function defaultWardrobeLayout(bool $withLoft, string $doorType): array
    {
        return [
            'plinth_height_mm' => 110,
            'partition_thickness_mm' => 18,
            'door_type' => $doorType,
            'loft' => [
                'enabled' => $withLoft,
                'height_mm' => 600,
                'shelf_count' => 1,
            ],
            'bays' => [
                [
                    'id' => 'bay-1',
                    'label' => 'Bay 1',
                    'width_mm' => null,
                    'sections' => [
                        ['type' => 'HANGING', 'height_mm' => 1100, 'label' => 'Hanging'],
                        ['type' => 'DRAWERS', 'height_mm' => 750, 'drawer_count' => 3, 'drawer_height_mm' => 200, 'label' => 'Drawers'],
                        ['type' => 'SHELVES', 'height_mm' => null, 'shelf_count' => 1, 'label' => 'Bottom shelf'],
                    ],
                ],
                [
                    'id' => 'bay-2',
                    'label' => 'Bay 2',
                    'width_mm' => null,
                    'sections' => [
                        ['type' => 'SHELVES', 'height_mm' => null, 'shelf_count' => 5, 'label' => 'Shelves'],
                    ],
                ],
            ],
        ];
    }

    /** @return array<string,mixed> */
    public static function defaultTvLayout(): array
    {
        return [
            'plinth_height_mm' => 80,
            'partition_thickness_mm' => 18,
            'door_type' => 'HINGED',
            'loft' => ['enabled' => false, 'height_mm' => 0, 'shelf_count' => 0],
            'bays' => [
                [
                    'id' => 'bay-1',
                    'label' => 'Left storage',
                    'width_mm' => null,
                    'sections' => [
                        ['type' => 'OPEN', 'height_mm' => 250, 'label' => 'Open niche'],
                        ['type' => 'DRAWERS', 'height_mm' => null, 'drawer_count' => 2, 'drawer_height_mm' => 180, 'label' => 'Drawers'],
                    ],
                ],
                [
                    'id' => 'bay-2',
                    'label' => 'Centre open',
                    'width_mm' => null,
                    'sections' => [
                        ['type' => 'OPEN', 'height_mm' => null, 'label' => 'TV niche'],
                    ],
                ],
                [
                    'id' => 'bay-3',
                    'label' => 'Right storage',
                    'width_mm' => null,
                    'sections' => [
                        ['type' => 'OPEN', 'height_mm' => 250, 'label' => 'Open niche'],
                        ['type' => 'SHELVES', 'height_mm' => null, 'shelf_count' => 1, 'label' => 'Shelf'],
                    ],
                ],
            ],
        ];
    }

    /** @return array<string,mixed> */
    public static function defaultKitchenBaseLayout(): array
    {
        return [
            'plinth_height_mm' => 100,
            'partition_thickness_mm' => 18,
            'door_type' => 'HINGED',
            'loft' => ['enabled' => false, 'height_mm' => 0, 'shelf_count' => 0],
            'bays' => [[
                'id' => 'bay-1',
                'label' => 'Cabinet',
                'width_mm' => null,
                'sections' => [
                    ['type' => 'SHELVES', 'height_mm' => null, 'shelf_count' => 1, 'label' => 'Shelf'],
                ],
            ]],
        ];
    }

    /** @return array<string,mixed> */
    public static function defaultTallLayout(): array
    {
        return [
            'plinth_height_mm' => 100,
            'partition_thickness_mm' => 18,
            'door_type' => 'HINGED',
            'loft' => ['enabled' => false, 'height_mm' => 0, 'shelf_count' => 0],
            'bays' => [[
                'id' => 'bay-1',
                'label' => 'Pantry',
                'width_mm' => null,
                'sections' => [
                    ['type' => 'SHELVES', 'height_mm' => null, 'shelf_count' => 5, 'label' => 'Shelves'],
                ],
            ]],
        ];
    }

    /** @return array<string,mixed> */
    public static function defaultChestLayout(): array
    {
        return [
            'plinth_height_mm' => 80,
            'partition_thickness_mm' => 18,
            'door_type' => 'NONE',
            'loft' => ['enabled' => false, 'height_mm' => 0, 'shelf_count' => 0],
            'bays' => [[
                'id' => 'bay-1',
                'label' => 'Drawers',
                'width_mm' => null,
                'sections' => [
                    ['type' => 'DRAWERS', 'height_mm' => null, 'drawer_count' => 4, 'drawer_height_mm' => 180, 'label' => 'Chest drawers'],
                ],
            ]],
        ];
    }

    /** @return array<string,mixed> */
    public static function singleBayShelves(int $shelfCount, int $plinth): array
    {
        return [
            'plinth_height_mm' => $plinth,
            'partition_thickness_mm' => 18,
            'door_type' => 'NONE',
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

    /** @return array<string,mixed> */
    public static function defaultCrockeryLayout(): array
    {
        return [
            'plinth_height_mm' => 100,
            'partition_thickness_mm' => 18,
            'door_type' => 'HINGED',
            'loft' => ['enabled' => false, 'height_mm' => 0, 'shelf_count' => 0],
            'bays' => [
                [
                    'id' => 'bay-1',
                    'label' => 'Display',
                    'width_mm' => null,
                    'sections' => [
                        ['type' => 'SHELVES', 'height_mm' => null, 'shelf_count' => 3, 'label' => 'Display shelves'],
                    ],
                ],
                [
                    'id' => 'bay-2',
                    'label' => 'Storage',
                    'width_mm' => null,
                    'sections' => [
                        ['type' => 'DRAWERS', 'height_mm' => 400, 'drawer_count' => 2, 'drawer_height_mm' => 160, 'label' => 'Drawers'],
                        ['type' => 'SHELVES', 'height_mm' => null, 'shelf_count' => 2, 'label' => 'Shelves'],
                    ],
                ],
            ],
        ];
    }

    /** @return array<string,mixed> */
    public static function defaultVanityLayout(): array
    {
        return [
            'plinth_height_mm' => 100,
            'partition_thickness_mm' => 18,
            'door_type' => 'HINGED',
            'loft' => ['enabled' => false, 'height_mm' => 0, 'shelf_count' => 0],
            'bays' => [[
                'id' => 'bay-1',
                'label' => 'Vanity',
                'width_mm' => null,
                'sections' => [
                    ['type' => 'DRAWERS', 'height_mm' => null, 'drawer_count' => 2, 'drawer_height_mm' => 180, 'label' => 'Drawers'],
                ],
            ]],
        ];
    }

    /** @return array<string,mixed> */
    public static function defaultStudyLayout(): array
    {
        return [
            'plinth_height_mm' => 0,
            'partition_thickness_mm' => 18,
            'door_type' => 'HINGED',
            'loft' => ['enabled' => false, 'height_mm' => 0, 'shelf_count' => 0],
            'bays' => [
                [
                    'id' => 'bay-1',
                    'label' => 'Knee space',
                    'width_mm' => null,
                    'sections' => [
                        ['type' => 'OPEN', 'height_mm' => null, 'label' => 'Open'],
                    ],
                ],
                [
                    'id' => 'bay-2',
                    'label' => 'Pedestal',
                    'width_mm' => 400,
                    'sections' => [
                        ['type' => 'DRAWERS', 'height_mm' => null, 'drawer_count' => 3, 'drawer_height_mm' => 150, 'label' => 'Drawers'],
                    ],
                ],
            ],
        ];
    }
}
