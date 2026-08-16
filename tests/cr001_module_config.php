<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Fmos\Domains\Furniture\InternalConfigCatalog;
use Fmos\Domains\Furniture\ModuleRulesEngine;
use Fmos\Domains\Furniture\ModuleTypeCatalog;

function ok(string $msg): void
{
    echo "  OK  $msg\n";
}

function assertTrue(bool $cond, string $msg): void
{
    if (!$cond) {
        throw new RuntimeException('ASSERT: ' . $msg);
    }
    ok($msg);
}

echo "Module configuration unit tests\n";

$modules = ModuleTypeCatalog::all();
assertTrue(isset($modules['WARDROBE']), 'WARDROBE module type exists');
assertTrue(isset($modules['KITCHEN_BASE']), 'KITCHEN_BASE module type exists');
assertTrue(in_array('CFG_HANGING', $modules['WARDROBE']['recommended_config_ids'], true), 'wardrobe recommends hanging');
assertTrue(in_array('CFG_KB_SHELF', $modules['KITCHEN_BASE']['recommended_config_ids'], true), 'kitchen recommends shelf');

$configs = InternalConfigCatalog::all();
assertTrue(isset($configs['CFG_HANGING']), 'CFG_HANGING exists');
assertTrue(!empty($configs['CFG_HANGING']['implemented']), 'hanging is implemented');
assertTrue(empty($configs['CFG_KB_PLATE_TRAY']['implemented']), 'plate tray is stub');
assertTrue(InternalConfigCatalog::configIdForKitchenPreset('drawers') === 'CFG_KB_DRAWERS', 'kitchen preset map drawers');
assertTrue(InternalConfigCatalog::configIdForKitchenPreset('sink') === 'CFG_KB_SINK', 'kitchen preset map sink');

$presets = InternalConfigCatalog::layoutPresets('KITCHEN');
assertTrue(count($presets) >= 3, 'kitchen layout presets from catalog');
assertTrue(($presets[0]['layout']['bays'][0]['sections'][0]['type'] ?? '') !== '', 'preset has sections');

$engine = new ModuleRulesEngine();

$rec = $engine->recommend('WARDROBE', ['width' => 1200, 'height' => 2400, 'depth' => 600], [
    'door_type' => 'HINGED',
    'bays' => [['id' => 'bay-1', 'sections' => []]],
]);
assertTrue(count($rec['recommended']) >= 1, 'wardrobe has recommended configs');
$hangIds = array_column($rec['recommended'], 'id');
assertTrue(in_array('CFG_HANGING', $hangIds, true), 'hanging in recommended for tall wardrobe');

$short = $engine->recommend('WARDROBE', ['width' => 1200, 'height' => 700, 'depth' => 600], [
    'bays' => [['id' => 'bay-1', 'sections' => []]],
]);
$unavail = array_column($short['unavailable'], 'id');
assertTrue(in_array('CFG_HANGING', $unavail, true), 'hanging unavailable when height too low');

$applied = $engine->apply('CFG_HANGING', [
    'door_type' => 'HINGED',
    'bays' => [['id' => 'bay-1', 'label' => 'Bay 1', 'width_mm' => null, 'sections' => []]],
], 'bay-1');
assertTrue(($applied['layout']['bays'][0]['sections'][0]['type'] ?? '') === 'HANGING', 'apply adds HANGING section');

$drawerPack = $engine->apply('CFG_KB_DRAWERS', [
    'door_type' => 'HINGED',
    'bays' => [['id' => 'bay-1', 'label' => 'Cabinet', 'width_mm' => null, 'sections' => [
        ['type' => 'SHELVES', 'shelf_count' => 1, 'height_mm' => null, 'label' => 'Shelf'],
    ]]],
], 'bay-1');
assertTrue(($drawerPack['layout']['door_type'] ?? '') === 'NONE', 'drawer pack sets door NONE');
assertTrue(($drawerPack['layout']['bays'][0]['sections'][0]['type'] ?? '') === 'DRAWERS', 'drawer pack replaces bay');
assertTrue(($drawerPack['shutter_count'] ?? -1) === 0, 'drawer pack suggests shutter_count 0');

$present = $engine->detectPresentConfigIds($drawerPack['layout']);
assertTrue(in_array('CFG_DRAWERS', $present, true) || in_array('CFG_KB_DRAWERS', $present, true), 'detect drawers present');

$kitRec = $engine->recommend('KITCHEN_BASE', ['width' => 600, 'height' => 720, 'depth' => 560], $drawerPack['layout']);
$unavailKit = array_column($kitRec['unavailable'], 'id');
assertTrue(in_array('CFG_KB_SHELF', $unavailKit, true) || in_array('CFG_KB_SINK', $unavailKit, true), 'incompatible kitchen configs marked unavailable');

$plate = null;
foreach (array_merge($kitRec['optional'], $kitRec['unavailable']) as $row) {
    if ($row['id'] === 'CFG_KB_PLATE_TRAY') {
        $plate = $row;
        break;
    }
}
assertTrue($plate !== null && $plate['status'] === 'unavailable', 'plate tray unavailable stub');

try {
    $engine->apply('CFG_KB_PLATE_TRAY', ['bays' => [['id' => 'bay-1', 'sections' => []]]]);
    assertTrue(false, 'applying stub should throw');
} catch (InvalidArgumentException $e) {
    assertTrue(str_contains($e->getMessage(), 'not implemented') || str_contains($e->getMessage(), 'Plate'), 'stub apply rejected');
}

$removed = $engine->remove('CFG_HANGING', $applied['layout'], 'bay-1');
$types = array_map(static fn ($s) => $s['type'] ?? '', $removed['bays'][0]['sections']);
assertTrue(!in_array('HANGING', $types, true), 'remove clears hanging');

$valid = $engine->validate('KITCHEN_BASE', ['width' => 600, 'height' => 720, 'depth' => 560], [
    'bays' => [[
        'id' => 'bay-1',
        'sections' => [
            ['type' => 'OPEN', 'label' => 'Plumbing', 'height_mm' => null],
            ['type' => 'SHELVES', 'shelf_count' => 1, 'height_mm' => null, 'label' => 'Shelf'],
        ],
    ]],
]);
assertTrue($valid['ok'] === false, 'plumbing+shelves fails validation');
assertTrue(count($valid['issues']) >= 1, 'validation reports issues');

echo "Module configuration unit tests done\n";
