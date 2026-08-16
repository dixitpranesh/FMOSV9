<?php

declare(strict_types=1);

/**
 * One-shot importer: copies FMOSV2 rule subsets into config/fmosv2/.
 * Source path can be overridden: php bin/import_fmosv2_rules.php [sourceDir]
 */
$src = $argv[1] ?? 'C:\\xampp\\htdocs\\FMOSV2\\frontend\\data';
$dest = dirname(__DIR__) . '/config/fmosv2';
if (!is_dir($dest)) {
    mkdir($dest, 0777, true);
}

function loadJson(string $path): array
{
    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new RuntimeException("Cannot read {$path}");
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new RuntimeException("Invalid JSON {$path}: " . json_last_error_msg());
    }
    return $data;
}

function writeJson(string $path, array $data): void
{
    file_put_contents(
        $path,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
    );
}

$today = date('Y-m-d');

$cfg = loadJson($src . '/configuration-rules.json');
writeJson($dest . '/thresholds.json', [
    '_meta' => [
        'source' => 'FMOSV2 frontend/data/configuration-rules.json thresholds',
        'imported_at' => $today,
        'rule_source' => 'fmosv2_configuration-rules',
    ],
    'thresholds' => $cfg['thresholds'] ?? [],
]);

$maps = loadJson($src . '/moduleMappings.json');
$wanted = [
    'wardrobe', 'kitchen_base', 'kitchen_wall', 'kitchen_tall',
    'kitchen_corner', 'kitchen_corner_blind', 'tv_unit', 'vanity_unit',
    'bookshelf', 'crockery_unit', 'study_unit', 'shoe_cabinet',
    'storage_unit', 'office_desk',
];
$outMaps = [];
foreach ($wanted as $key) {
    $mod = $maps['module_mappings'][$key] ?? null;
    if (!is_array($mod)) {
        continue;
    }
    $internal = array_values(array_filter(
        $mod['allowed_sections']['internal'] ?? [],
        static fn ($v) => is_string($v) && $v !== ''
    ));
    $door = array_values(array_filter(
        $mod['allowed_sections']['door'] ?? [],
        static fn ($v) => is_string($v) && $v !== ''
    ));
    $outMaps[$key] = [
        'label' => $mod['label'] ?? $key,
        'default_dimensions' => $mod['default_dimensions'] ?? new stdClass(),
        'allowed_sections' => [
            'internal' => $internal,
            'door' => $door,
        ],
    ];
}
writeJson($dest . '/module-mappings.json', [
    '_meta' => [
        'source' => 'FMOSV2 frontend/data/moduleMappings.json',
        'imported_at' => $today,
    ],
    'module_mappings' => $outMaps,
]);

$sections = loadJson($src . '/sections.json');
$ids = [
    'hanging_full', 'hanging_double', 'open_shelf', 'drawer_stack', 'shoe_rack',
    'sink_bay', 'pull_out', 'trouser_pull_out', 'open_niche', 'tandem_drawer',
    'bottle_pullout', 'cutlery_organizer', 'hob_bay', 'wicker_basket',
];
$byId = [];
foreach ($sections['sections'] ?? [] as $sec) {
    if (isset($sec['id'])) {
        $byId[$sec['id']] = $sec;
    }
}
$secOut = [];
foreach ($ids as $id) {
    if (!isset($byId[$id])) {
        continue;
    }
    $sec = $byId[$id];
    $secOut[$id] = [
        'name' => $sec['name'] ?? $id,
        'default_dimensions' => $sec['default_dimensions'] ?? new stdClass(),
        'default_rules' => $sec['default_rules'] ?? new stdClass(),
    ];
}
writeJson($dest . '/section-rules.json', [
    '_meta' => [
        'source' => 'FMOSV2 frontend/data/sections.json (subset)',
        'imported_at' => $today,
    ],
    'sections' => $secOut,
]);

$wardrobePresetsPath = $src . '/WardrobePresets.json';
if (is_file($wardrobePresetsPath)) {
    $wp = loadJson($wardrobePresetsPath);
    writeJson($dest . '/wardrobe-presets.json', [
        '_meta' => [
            'source' => 'FMOSV2 frontend/data/WardrobePresets.json',
            'imported_at' => $today,
        ],
        'presets' => $wp['presets'] ?? [],
    ]);
    echo '  wardrobe_presets=' . count($wp['presets'] ?? []) . "\n";
}

echo "Imported into {$dest}\n";
echo '  modules=' . count($outMaps) . "\n";
echo '  sections=' . count($secOut) . "\n";
echo '  wardrobe_hanging_min_depth_mm=' . ($cfg['thresholds']['structural']['wardrobe_hanging_min_depth_mm'] ?? '?') . "\n";
