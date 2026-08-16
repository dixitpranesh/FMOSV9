<?php

declare(strict_types=1);

namespace Fmos\Domains\Furniture;

/**
 * Module-type metadata layered on FurnitureTemplateCatalog codes.
 * Prefer FMOSV2 moduleMappings allow-lists when config/fmosv2 extracts are present.
 */
final class ModuleTypeCatalog
{
    /**
     * @return array<string, array<string,mixed>>
     */
    public static function all(): array
    {
        $templates = FurnitureTemplateCatalog::all();
        $out = [];
        foreach ($templates as $code => $tpl) {
            $m = FmosV2RulesBridge::moduleMetaForTemplate($code) ?? self::fallbackMeta($code);
            $out[$code] = [
                'id' => $code,
                'template_code' => $code,
                'name' => $tpl['name'],
                'category' => $tpl['category'],
                'description' => $tpl['description'],
                'parameters' => $tpl['parameters'],
                'allowed_config_ids' => $m['allowed_config_ids'],
                'recommended_config_ids' => $m['recommended_config_ids'],
                'optional_config_ids' => $m['optional_config_ids'],
                'required_config_ids' => $m['required_config_ids'],
                'structural_notes' => $m['structural_notes'],
                'fmosv2_module' => $m['fmosv2_module'] ?? null,
                'fmosv2_sections' => $m['fmosv2_sections'] ?? [],
            ];
        }
        return $out;
    }

    /** @return array<string,mixed>|null */
    public static function get(string $templateCode): ?array
    {
        $all = self::all();
        return $all[$templateCode] ?? null;
    }

    /**
     * @return array{
     *   allowed_config_ids:list<string>,
     *   recommended_config_ids:list<string>,
     *   optional_config_ids:list<string>,
     *   required_config_ids:list<string>,
     *   structural_notes:string,
     *   fmosv2_module?:?string,
     *   fmosv2_sections?:list<string>
     * }
     */
    private static function fallbackMeta(string $code): array
    {
        $wardrobe = [
            'allowed_config_ids' => [
                'CFG_HANGING', 'CFG_SHELVES', 'CFG_DRAWERS', 'CFG_OPEN', 'CFG_MIRROR',
                'CFG_HANGING_LONG', 'CFG_HANGING_DOUBLE', 'CFG_SHOE', 'CFG_TROUSER', 'CFG_WICKER',
            ],
            'recommended_config_ids' => ['CFG_HANGING', 'CFG_SHELVES'],
            'optional_config_ids' => [
                'CFG_DRAWERS', 'CFG_MIRROR', 'CFG_OPEN',
                'CFG_HANGING_LONG', 'CFG_HANGING_DOUBLE', 'CFG_SHOE', 'CFG_TROUSER', 'CFG_WICKER',
            ],
            'required_config_ids' => [],
            'structural_notes' => 'Fallback allow-list (FMOSV2 extracts missing).',
        ];
        $kitchenBase = [
            'allowed_config_ids' => [
                'CFG_KB_SHELF', 'CFG_KB_DRAWERS', 'CFG_KB_SINK', 'CFG_SHELVES', 'CFG_DRAWERS', 'CFG_OPEN',
                'CFG_KB_PLATE_TRAY', 'CFG_KB_CUTLERY', 'CFG_KB_BOTTLE', 'CFG_KB_WASTE', 'CFG_KB_HOB',
            ],
            'recommended_config_ids' => ['CFG_KB_SHELF'],
            'optional_config_ids' => [
                'CFG_KB_DRAWERS', 'CFG_KB_SINK', 'CFG_KB_PLATE_TRAY', 'CFG_KB_CUTLERY',
                'CFG_KB_BOTTLE', 'CFG_KB_WASTE', 'CFG_KB_HOB',
            ],
            'required_config_ids' => [],
            'structural_notes' => 'Fallback allow-list (FMOSV2 extracts missing).',
        ];

        return match (strtoupper($code)) {
            'WARDROBE', 'WARDROBE_SLIDING', 'WARDROBE_LOFT' => $wardrobe,
            'KITCHEN_BASE' => $kitchenBase,
            'KITCHEN_CORNER' => [
                'allowed_config_ids' => ['CFG_KB_SHELF', 'CFG_SHELVES', 'CFG_OPEN', 'CFG_KB_PLATE_TRAY'],
                'recommended_config_ids' => ['CFG_KB_SHELF'],
                'optional_config_ids' => ['CFG_OPEN'],
                'required_config_ids' => [],
                'structural_notes' => 'Fallback corner allow-list.',
            ],
            'KITCHEN_WALL' => [
                'allowed_config_ids' => ['CFG_SHELVES', 'CFG_OPEN', 'CFG_DRAWERS'],
                'recommended_config_ids' => ['CFG_SHELVES'],
                'optional_config_ids' => ['CFG_OPEN', 'CFG_DRAWERS'],
                'required_config_ids' => [],
                'structural_notes' => 'Fallback wall allow-list.',
            ],
            'KITCHEN_TALL' => [
                'allowed_config_ids' => ['CFG_SHELVES', 'CFG_DRAWERS', 'CFG_OPEN', 'CFG_HANGING', 'CFG_KB_BOTTLE'],
                'recommended_config_ids' => ['CFG_SHELVES'],
                'optional_config_ids' => ['CFG_DRAWERS', 'CFG_OPEN', 'CFG_KB_BOTTLE'],
                'required_config_ids' => [],
                'structural_notes' => 'Fallback tall allow-list.',
            ],
            'TV_UNIT' => [
                'allowed_config_ids' => ['CFG_OPEN', 'CFG_DRAWERS', 'CFG_SHELVES', 'CFG_MIRROR'],
                'recommended_config_ids' => ['CFG_OPEN'],
                'optional_config_ids' => ['CFG_DRAWERS', 'CFG_SHELVES', 'CFG_MIRROR'],
                'required_config_ids' => [],
                'structural_notes' => 'Fallback TV allow-list.',
            ],
            'CHEST_DRAWERS' => [
                'allowed_config_ids' => ['CFG_DRAWERS'],
                'recommended_config_ids' => ['CFG_DRAWERS'],
                'optional_config_ids' => [],
                'required_config_ids' => [],
                'structural_notes' => 'Fallback chest allow-list.',
            ],
            'BOOKCASE' => [
                'allowed_config_ids' => ['CFG_SHELVES', 'CFG_OPEN'],
                'recommended_config_ids' => ['CFG_SHELVES'],
                'optional_config_ids' => ['CFG_OPEN'],
                'required_config_ids' => [],
                'structural_notes' => 'Fallback bookcase allow-list.',
            ],
            'CROCKERY' => [
                'allowed_config_ids' => ['CFG_SHELVES', 'CFG_DRAWERS', 'CFG_OPEN', 'CFG_MIRROR'],
                'recommended_config_ids' => ['CFG_SHELVES'],
                'optional_config_ids' => ['CFG_DRAWERS', 'CFG_OPEN', 'CFG_MIRROR'],
                'required_config_ids' => [],
                'structural_notes' => 'Fallback crockery allow-list.',
            ],
            'VANITY' => [
                'allowed_config_ids' => ['CFG_DRAWERS', 'CFG_SHELVES', 'CFG_OPEN', 'CFG_KB_SINK'],
                'recommended_config_ids' => ['CFG_DRAWERS'],
                'optional_config_ids' => ['CFG_SHELVES', 'CFG_OPEN', 'CFG_KB_SINK'],
                'required_config_ids' => [],
                'structural_notes' => 'Fallback vanity allow-list.',
            ],
            'STUDY_TABLE' => [
                'allowed_config_ids' => ['CFG_DRAWERS', 'CFG_SHELVES', 'CFG_OPEN'],
                'recommended_config_ids' => ['CFG_DRAWERS'],
                'optional_config_ids' => ['CFG_SHELVES', 'CFG_OPEN'],
                'required_config_ids' => [],
                'structural_notes' => 'Fallback study allow-list.',
            ],
            default => [
                'allowed_config_ids' => ['CFG_SHELVES', 'CFG_DRAWERS', 'CFG_OPEN'],
                'recommended_config_ids' => ['CFG_SHELVES'],
                'optional_config_ids' => ['CFG_DRAWERS', 'CFG_OPEN'],
                'required_config_ids' => [],
                'structural_notes' => 'Generic fallback allow-list.',
            ],
        };
    }
}
