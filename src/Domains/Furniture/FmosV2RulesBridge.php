<?php

declare(strict_types=1);

namespace Fmos\Domains\Furniture;

/**
 * Loads FMOSV2 rule extracts from config/fmosv2/ and maps them onto
 * this app's template codes + CFG_* internal configs.
 *
 * Source of truth remains parameters.layout + LayoutEngine; this bridge only
 * supplies allow-lists and dimensional thresholds.
 */
final class FmosV2RulesBridge
{
    private static ?array $thresholds = null;
    private static ?array $moduleMappings = null;
    private static ?array $sectionRules = null;

    public static function configDir(): string
    {
        return dirname(__DIR__, 3) . '/config/fmosv2';
    }

    /** @return array<string,mixed> */
    public static function thresholds(): array
    {
        if (self::$thresholds === null) {
            self::$thresholds = self::load('thresholds.json')['thresholds'] ?? [];
        }
        return self::$thresholds;
    }

    /** @return array<string,mixed> */
    public static function moduleMappings(): array
    {
        if (self::$moduleMappings === null) {
            self::$moduleMappings = self::load('module-mappings.json')['module_mappings'] ?? [];
        }
        return self::$moduleMappings;
    }

    /** @return array<string,mixed> */
    public static function sectionRules(): array
    {
        if (self::$sectionRules === null) {
            self::$sectionRules = self::load('section-rules.json')['sections'] ?? [];
        }
        return self::$sectionRules;
    }

    public static function available(): bool
    {
        return is_file(self::configDir() . '/thresholds.json')
            && is_file(self::configDir() . '/module-mappings.json');
    }

    /**
     * Template code → FMOSV2 module_mappings key.
     */
    public static function templateToV2Module(string $templateCode): ?string
    {
        return match (strtoupper($templateCode)) {
            'WARDROBE', 'WARDROBE_SLIDING', 'WARDROBE_LOFT' => 'wardrobe',
            'KITCHEN_BASE' => 'kitchen_base',
            'KITCHEN_WALL' => 'kitchen_wall',
            'KITCHEN_TALL' => 'kitchen_tall',
            'KITCHEN_CORNER' => 'kitchen_corner_blind',
            'TV_UNIT' => 'tv_unit',
            'VANITY' => 'vanity_unit',
            'BOOKCASE' => 'bookshelf',
            'CROCKERY' => 'crockery_unit',
            'STUDY_TABLE' => 'office_desk',
            'CHEST_DRAWERS' => 'storage_unit',
            default => null,
        };
    }

    /**
     * FMOSV2 section id → CFG_* ids used in this app.
     *
     * @return array<string, list<string>>
     */
    public static function sectionToConfigMap(): array
    {
        return [
            'hanging_full' => ['CFG_HANGING', 'CFG_HANGING_LONG'],
            'hanging_double' => ['CFG_HANGING', 'CFG_HANGING_DOUBLE'],
            'open_shelf' => ['CFG_SHELVES', 'CFG_KB_SHELF'],
            'drawer_stack' => ['CFG_DRAWERS', 'CFG_KB_DRAWERS'],
            'tandem_drawer' => ['CFG_DRAWERS', 'CFG_KB_DRAWERS'],
            'accessory_drawer' => ['CFG_DRAWERS'],
            'open_niche' => ['CFG_OPEN'],
            'shoe_rack' => ['CFG_SHOE'],
            'sink_bay' => ['CFG_KB_SINK'],
            'pull_out' => ['CFG_KB_BOTTLE'],
            'bottle_pullout' => ['CFG_KB_BOTTLE'],
            'cutlery_organizer' => ['CFG_KB_CUTLERY'],
            'trouser_pull_out' => ['CFG_TROUSER'],
            'wicker_basket' => ['CFG_WICKER'],
            'hob_bay' => ['CFG_KB_HOB'],
            // door-ish sections are not internal configs in this app
        ];
    }

    /**
     * Build ModuleTypeCatalog meta for a template from V2 mappings.
     *
     * @return array{
     *   allowed_config_ids:list<string>,
     *   recommended_config_ids:list<string>,
     *   optional_config_ids:list<string>,
     *   required_config_ids:list<string>,
     *   structural_notes:string,
     *   fmosv2_module:?string,
     *   fmosv2_sections:list<string>
     * }|null
     */
    public static function moduleMetaForTemplate(string $templateCode): ?array
    {
        $v2Key = self::templateToV2Module($templateCode);
        if ($v2Key === null) {
            return null;
        }
        $mod = self::moduleMappings()[$v2Key] ?? null;
        if (!is_array($mod)) {
            return null;
        }

        $sectionIds = array_values(array_filter(
            $mod['allowed_sections']['internal'] ?? [],
            static fn ($v) => is_string($v) && $v !== ''
        ));
        $map = self::sectionToConfigMap();
        $allowed = [];
        foreach ($sectionIds as $sid) {
            foreach ($map[$sid] ?? [] as $cfgId) {
                $allowed[$cfgId] = true;
            }
        }

        // Internal mirror is not a V2 "section" in the same list; keep as optional for wardrobes/TV.
        if (in_array($v2Key, ['wardrobe', 'tv_unit', 'crockery_unit'], true)) {
            $allowed['CFG_MIRROR'] = true;
        }
        // Plate tray / waste remain kitchen specialty stubs when kitchen modules allow storage internals.
        if (str_starts_with($v2Key, 'kitchen_')) {
            $allowed['CFG_KB_PLATE_TRAY'] = true;
            $allowed['CFG_KB_WASTE'] = true;
            $allowed['CFG_OPEN'] = true;
        }

        $allowedIds = array_keys($allowed);
        $recommended = self::defaultRecommended($v2Key, $allowedIds);
        $optional = array_values(array_diff($allowedIds, $recommended));

        return [
            'allowed_config_ids' => $allowedIds,
            'recommended_config_ids' => $recommended,
            'optional_config_ids' => $optional,
            'required_config_ids' => [],
            'structural_notes' => 'Allow-list derived from FMOSV2 moduleMappings (' . $v2Key . '). Carcass from LayoutEngine.',
            'fmosv2_module' => $v2Key,
            'fmosv2_sections' => $sectionIds,
        ];
    }

    /**
     * Eligibility overlay for a CFG id from V2 section rules + thresholds.
     *
     * @return array<string,mixed>
     */
    public static function eligibilityForConfig(string $configId): array
    {
        $t = self::thresholds();
        $structural = $t['structural'] ?? [];
        $hardware = $t['hardware'] ?? [];
        $sections = self::sectionRules();
        $src = 'fmosv2_configuration-rules';

        return match ($configId) {
            'CFG_HANGING' => [
                'min_width_mm' => (float) ($sections['hanging_full']['default_rules']['min_width']
                    ?? $sections['hanging_double']['default_rules']['min_width']
                    ?? 450),
                'max_width_mm' => (float) ($sections['hanging_full']['default_rules']['max_width'] ?? 1200),
                'min_height_mm' => (float) ($sections['hanging_double']['default_rules']['hanging_clearance_min'] ?? 700),
                'min_depth_mm' => (float) ($structural['wardrobe_hanging_min_depth_mm'] ?? 550),
                'rule_source' => $src,
                'fmosv2_refs' => ['sections.hanging_full/hanging_double', 'thresholds.structural.wardrobe_hanging_min_depth_mm', 'CFG071'],
            ],
            'CFG_HANGING_LONG' => [
                'min_width_mm' => (float) ($sections['hanging_full']['default_rules']['min_width'] ?? 450),
                'max_width_mm' => (float) ($sections['hanging_full']['default_rules']['max_width'] ?? 1200),
                'min_height_mm' => (float) ($sections['hanging_full']['default_rules']['hanging_clearance_min'] ?? 1400),
                'min_depth_mm' => (float) ($structural['wardrobe_hanging_min_depth_mm'] ?? 550),
                'rule_source' => $src,
                'fmosv2_refs' => ['sections.hanging_full', 'CFG071'],
            ],
            'CFG_HANGING_DOUBLE' => [
                'min_width_mm' => (float) ($sections['hanging_double']['default_rules']['min_width'] ?? 450),
                'max_width_mm' => (float) ($sections['hanging_double']['default_rules']['max_width'] ?? 1200),
                'min_height_mm' => (float) (($sections['hanging_double']['default_rules']['hanging_clearance_min'] ?? 700) * 2),
                'min_depth_mm' => (float) ($structural['wardrobe_hanging_min_depth_mm'] ?? 550),
                'rule_source' => $src,
                'fmosv2_refs' => ['sections.hanging_double', 'CFG071'],
            ],
            'CFG_SHELVES', 'CFG_KB_SHELF' => [
                'min_width_mm' => (float) ($sections['open_shelf']['default_rules']['min_width'] ?? 250),
                'max_width_mm' => (float) ($sections['open_shelf']['default_rules']['max_width'] ?? 1200),
                'min_height_mm' => (float) ($sections['open_shelf']['default_rules']['shelf_spacing_min'] ?? 200),
                'min_depth_mm' => 250.0,
                'rule_source' => $src,
                'fmosv2_refs' => ['sections.open_shelf', 'CFG072'],
            ],
            'CFG_DRAWERS', 'CFG_KB_DRAWERS' => [
                'min_width_mm' => (float) ($sections['drawer_stack']['default_rules']['min_width'] ?? 250),
                'max_width_mm' => (float) ($sections['drawer_stack']['default_rules']['max_width'] ?? 900),
                'min_height_mm' => max(
                    350.0,
                    ((float) ($hardware['drawer_min_height_mm'] ?? 80)) * 3
                ),
                'min_depth_mm' => 350.0,
                'rule_source' => $src,
                'fmosv2_refs' => ['sections.drawer_stack', 'thresholds.hardware.drawer_min_height_mm'],
            ],
            'CFG_OPEN' => [
                'min_width_mm' => (float) ($sections['open_niche']['default_rules']['min_width'] ?? 250),
                'max_width_mm' => (float) ($sections['open_niche']['default_rules']['max_width'] ?? 1200),
                'min_height_mm' => 150.0,
                'min_depth_mm' => 250.0,
                'rule_source' => $src,
                'fmosv2_refs' => ['sections.open_niche'],
            ],
            'CFG_MIRROR' => [
                'min_width_mm' => 400.0,
                'min_height_mm' => 400.0,
                'min_depth_mm' => 300.0,
                'rule_source' => $src,
                'fmosv2_refs' => ['thresholds.door_section.mirror_door_frame_height_mm (adapted for internal niche)'],
            ],
            'CFG_SHOE' => [
                'min_width_mm' => (float) ($sections['shoe_rack']['default_rules']['min_width'] ?? 400),
                'max_width_mm' => (float) ($sections['shoe_rack']['default_rules']['max_width'] ?? 1200),
                'min_height_mm' => (float) (($sections['shoe_rack']['default_rules']['tier_height'] ?? 180) * 2),
                'min_depth_mm' => 350.0,
                'rule_source' => $src,
                'fmosv2_refs' => ['sections.shoe_rack'],
            ],
            'CFG_KB_SINK' => [
                'min_width_mm' => (float) ($sections['sink_bay']['default_rules']['min_width'] ?? 450),
                'max_width_mm' => (float) ($sections['sink_bay']['default_rules']['max_width'] ?? 900),
                'min_height_mm' => (float) ($structural['kitchen_base_height_min_mm'] ?? 500),
                'min_depth_mm' => 450.0,
                'rule_source' => $src,
                'fmosv2_refs' => ['sections.sink_bay'],
            ],
            'CFG_KB_BOTTLE' => [
                'min_width_mm' => (float) ($sections['pull_out']['default_rules']['min_width'] ?? 150),
                'max_width_mm' => (float) ($hardware['pull_out_pantry_max_width_mm']
                    ?? $sections['pull_out']['default_rules']['max_width']
                    ?? 300),
                'min_height_mm' => 500.0,
                'min_depth_mm' => (float) ($hardware['pull_out_shelf_min_depth_mm'] ?? 400),
                'rule_source' => $src,
                'fmosv2_refs' => ['sections.pull_out', 'thresholds.hardware.pull_out_pantry_max_width_mm'],
            ],
            'CFG_KB_CUTLERY' => [
                'min_width_mm' => (float) ($sections['drawer_stack']['default_rules']['min_width'] ?? 250),
                'max_width_mm' => (float) ($sections['drawer_stack']['default_rules']['max_width'] ?? 900),
                'min_height_mm' => (float) ($hardware['drawer_min_height_mm'] ?? 80),
                'min_depth_mm' => 450.0,
                'rule_source' => $src,
                'fmosv2_refs' => ['sections.drawer_stack / cutlery_organizer'],
            ],
            'CFG_TROUSER' => [
                'min_width_mm' => (float) ($hardware['trouser_rack_min_width_mm']
                    ?? $sections['trouser_pull_out']['default_rules']['min_width']
                    ?? 450),
                'max_width_mm' => (float) ($sections['trouser_pull_out']['default_rules']['max_width'] ?? 650),
                'min_height_mm' => 400.0,
                'min_depth_mm' => 450.0,
                'rule_source' => $src,
                'fmosv2_refs' => ['sections.trouser_pull_out', 'thresholds.hardware.trouser_rack_min_width_mm'],
            ],
            'CFG_WICKER' => [
                'min_width_mm' => (float) ($hardware['wicker_basket_min_width_mm'] ?? 350),
                'min_height_mm' => 300.0,
                'min_depth_mm' => 400.0,
                'rule_source' => $src,
                'fmosv2_refs' => ['thresholds.hardware.wicker_basket_min_width_mm'],
            ],
            'CFG_KB_HOB' => [
                'min_width_mm' => 600.0,
                'min_height_mm' => (float) ($structural['kitchen_base_height_min_mm'] ?? 720),
                'min_depth_mm' => 560.0,
                'rule_source' => $src,
                'fmosv2_refs' => ['sections.hob_bay'],
            ],
            'CFG_KB_PLATE_TRAY' => [
                'min_width_mm' => (float) ($sections['pull_out']['default_rules']['min_width'] ?? 500),
                'max_width_mm' => (float) ($sections['pull_out']['default_rules']['max_width'] ?? 900),
                'min_height_mm' => 500.0,
                'min_depth_mm' => 500.0,
                'rule_source' => $src,
                'fmosv2_refs' => ['sections.pull_out'],
            ],
            'CFG_KB_WASTE' => [
                'min_width_mm' => 400.0,
                'min_height_mm' => 500.0,
                'min_depth_mm' => 450.0,
                'rule_source' => $src,
                'fmosv2_refs' => ['adapted kitchen storage threshold'],
            ],
            default => [],
        };
    }

    /**
     * Module-level dimensional checks from V2 thresholds (wardrobe depth, kitchen height band).
     *
     * @param array{width?:float|int,height?:float|int,depth?:float|int} $dims
     * @return list<array{code:string,message:string}>
     */
    public static function moduleDimensionIssues(string $templateCode, array $dims): array
    {
        $issues = [];
        $v2Key = self::templateToV2Module($templateCode);
        $t = self::thresholds();
        $structural = $t['structural'] ?? [];
        $w = (float) ($dims['width'] ?? 0);
        $h = (float) ($dims['height'] ?? 0);
        $d = (float) ($dims['depth'] ?? 0);

        if ($v2Key === 'wardrobe' && $d > 0) {
            $minD = (float) ($structural['wardrobe_hanging_min_depth_mm'] ?? 550);
            if ($d < $minD) {
                $issues[] = [
                    'code' => 'CFG071',
                    'message' => "Wardrobe depth {$d}mm is below the {$minD}mm minimum for clothing storage (FMOSV2 CFG071).",
                ];
            }
        }
        if ($v2Key === 'kitchen_base' && $h > 0) {
            $minH = (float) ($structural['kitchen_base_height_min_mm'] ?? 720);
            $maxH = (float) ($structural['kitchen_base_height_max_mm'] ?? 780);
            // Advisory band from V2; do not hard-block shorter custom bases already in use (500–1200 template range).
            if ($h < $minH || $h > $maxH) {
                $issues[] = [
                    'code' => 'KITCHEN_BASE_HEIGHT_BAND',
                    'message' => "Kitchen base height {$h}mm is outside the FMOSV2 ergonomic band {$minH}–{$maxH}mm (advisory).",
                ];
            }
        }
        if ($w > 0 && $w < 250) {
            $issues[] = [
                'code' => 'CFG072',
                'message' => "Width {$w}mm is below the 250mm absolute minimum section width (FMOSV2 CFG072).",
            ];
        }
        if ($d > 0 && $d < 150) {
            $issues[] = [
                'code' => 'CFG069',
                'message' => "Depth {$d}mm is below the 150mm absolute minimum (FMOSV2 CFG069).",
            ];
        }
        if ($d > 800) {
            $issues[] = [
                'code' => 'CFG068',
                'message' => "Depth {$d}mm exceeds the 800mm ergonomic maximum (FMOSV2 CFG068).",
            ];
        }
        return $issues;
    }

    /**
     * @param list<string> $allowedIds
     * @return list<string>
     */
    private static function defaultRecommended(string $v2Key, array $allowedIds): array
    {
        $prefs = match ($v2Key) {
            'wardrobe' => ['CFG_HANGING', 'CFG_SHELVES'],
            'kitchen_base', 'kitchen_corner', 'kitchen_corner_blind' => ['CFG_KB_SHELF'],
            'kitchen_wall', 'kitchen_tall', 'bookshelf' => ['CFG_SHELVES'],
            'tv_unit' => ['CFG_OPEN'],
            'vanity_unit', 'storage_unit', 'office_desk', 'study_unit' => ['CFG_DRAWERS'],
            'crockery_unit' => ['CFG_SHELVES'],
            'shoe_cabinet' => ['CFG_SHOE'],
            default => ['CFG_SHELVES'],
        };
        return array_values(array_filter($prefs, static fn ($id) => in_array($id, $allowedIds, true)));
    }

    /** @return array<string,mixed> */
    private static function load(string $file): array
    {
        $path = self::configDir() . '/' . $file;
        if (!is_file($path)) {
            return [];
        }
        $raw = file_get_contents($path);
        if ($raw === false || $raw === '') {
            return [];
        }
        // Strip UTF-8 BOM if present.
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}
