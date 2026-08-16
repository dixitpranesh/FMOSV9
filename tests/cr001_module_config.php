<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Fmos\Domains\Furniture\FmosV2RulesBridge;
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

assertTrue(FmosV2RulesBridge::available(), 'FMOSV2 config extracts present');
assertTrue((FmosV2RulesBridge::thresholds()['structural']['wardrobe_hanging_min_depth_mm'] ?? 0) == 550, 'V2 hanging min depth 550');

$modules = ModuleTypeCatalog::all();
assertTrue(isset($modules['WARDROBE']), 'WARDROBE module type exists');
assertTrue(isset($modules['KITCHEN_BASE']), 'KITCHEN_BASE module type exists');
assertTrue(($modules['WARDROBE']['fmosv2_module'] ?? '') === 'wardrobe', 'wardrobe mapped from V2');
assertTrue(in_array('CFG_HANGING', $modules['WARDROBE']['recommended_config_ids'], true), 'wardrobe recommends hanging');
assertTrue(in_array('CFG_SHOE', $modules['WARDROBE']['allowed_config_ids'], true), 'wardrobe allows shoe from V2 shoe_rack');
assertTrue(in_array('CFG_TROUSER', $modules['WARDROBE']['allowed_config_ids'], true), 'wardrobe allows trouser from V2');
assertTrue(in_array('CFG_KB_SHELF', $modules['KITCHEN_BASE']['recommended_config_ids'], true), 'kitchen recommends shelf');
assertTrue(in_array('CFG_KB_BOTTLE', $modules['KITCHEN_BASE']['allowed_config_ids'], true), 'kitchen allows bottle from V2');

$configs = InternalConfigCatalog::all();
assertTrue(isset($configs['CFG_HANGING']), 'CFG_HANGING exists');
assertTrue(!empty($configs['CFG_HANGING']['implemented']), 'hanging is implemented');
assertTrue(($configs['CFG_HANGING']['eligibility']['min_depth_mm'] ?? 0) == 550, 'hanging min depth from V2');
assertTrue(($configs['CFG_HANGING']['eligibility']['min_width_mm'] ?? 0) == 450, 'hanging min width from V2');
assertTrue(($configs['CFG_HANGING']['eligibility']['rule_source'] ?? '') === 'fmosv2_configuration-rules', 'eligibility tagged fmosv2');
assertTrue(!empty($configs['CFG_KB_PLATE_TRAY']['implemented']), 'plate tray implemented');
assertTrue(!empty($configs['CFG_KB_BOTTLE']['implemented']), 'bottle implemented');
assertTrue(!empty($configs['CFG_KB_WASTE']['implemented']), 'waste implemented');
assertTrue(!empty($configs['CFG_TROUSER']['implemented']), 'trouser implemented');
assertTrue(!empty($configs['CFG_WICKER']['implemented']), 'wicker implemented');
assertTrue(!empty($configs['CFG_KB_HOB']['implemented']), 'hob implemented');
assertTrue(!empty($configs['CFG_HANGING_LONG']['implemented']), 'long hanging implemented in phase 4');
assertTrue(!empty($configs['CFG_SHOE']['implemented']), 'shoe implemented in phase 4');
assertTrue(!empty($configs['CFG_KB_CUTLERY']['implemented']), 'cutlery implemented in phase 4');
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

$short = $engine->recommend('WARDROBE', ['width' => 1200, 'height' => 600, 'depth' => 600], [
    'bays' => [['id' => 'bay-1', 'sections' => []]],
]);
$unavail = array_column($short['unavailable'], 'id');
assertTrue(in_array('CFG_HANGING', $unavail, true), 'hanging unavailable when height too low');

$shallow = $engine->recommend('WARDROBE', ['width' => 1200, 'height' => 2400, 'depth' => 400], [
    'bays' => [['id' => 'bay-1', 'sections' => []]],
]);
$unavailShallow = array_column($shallow['unavailable'], 'id');
assertTrue(in_array('CFG_HANGING', $unavailShallow, true), 'hanging unavailable when depth < 550 (CFG071)');

$depthIssues = $engine->validate('WARDROBE', ['width' => 1200, 'height' => 2400, 'depth' => 400], [
    'bays' => [['id' => 'bay-1', 'sections' => []]],
]);
assertTrue($depthIssues['ok'] === false, 'shallow wardrobe fails module validation');
assertTrue(in_array('CFG071', array_column($depthIssues['issues'], 'code'), true), 'CFG071 reported');

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
foreach (array_merge($kitRec['optional'], $kitRec['recommended'], $kitRec['unavailable']) as $row) {
    if ($row['id'] === 'CFG_KB_PLATE_TRAY') {
        $plate = $row;
        break;
    }
}
assertTrue($plate !== null, 'plate tray listed for kitchen');
assertTrue($plate['status'] !== 'unavailable' || !empty($plate['reasons']), 'plate tray no longer stub-only');

$plateApplied = $engine->apply('CFG_KB_PLATE_TRAY', [
    'door_type' => 'HINGED',
    'bays' => [['id' => 'bay-1', 'sections' => [['type' => 'SHELVES', 'shelf_count' => 1]]]],
], 'bay-1');
assertTrue(($plateApplied['layout']['bays'][0]['sections'][0]['shelf_style'] ?? '') === 'plate_tray', 'apply plate tray sets shelf_style');
assertTrue(in_array('CFG_KB_PLATE_TRAY', $engine->detectPresentConfigIds($plateApplied['layout']), true), 'detect plate tray present');

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
