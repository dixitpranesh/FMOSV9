<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Fmos\Domains\Furniture\FurnitureLayoutEngine;
use Fmos\Domains\Furniture\InternalConfigCatalog;
use Fmos\Domains\Furniture\ModuleRulesEngine;

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

function rolesOf(array $parts): array
{
    return array_count_values(array_map(static fn ($p) => $p['role'] ?? '', $parts));
}

echo "Phase 5 specialty stub implementations\n";

$engine = new FurnitureLayoutEngine();
$rules = new ModuleRulesEngine();

foreach (['CFG_KB_PLATE_TRAY', 'CFG_KB_BOTTLE', 'CFG_KB_WASTE', 'CFG_TROUSER', 'CFG_WICKER', 'CFG_KB_HOB'] as $id) {
    assertTrue(!empty(InternalConfigCatalog::get($id)['implemented']), "{$id} implemented");
}

$plate = $rules->apply('CFG_KB_PLATE_TRAY', [
    'door_type' => 'HINGED',
    'bays' => [['id' => 'bay-1', 'label' => 'Bay 1', 'width_mm' => null, 'sections' => []]],
], 'bay-1');
$partsPlate = $engine->generate('KITCHEN_BASE', [
    'width' => 600, 'height' => 720, 'depth' => 560,
    'carcass_thickness' => 18, 'back_thickness' => 18, 'shutter_count' => 0, 'door_type' => 'NONE',
    'layout' => $plate['layout'],
]);
$r = rolesOf($partsPlate);
assertTrue(($r['SHELF_PLATE_TRAY'] ?? 0) >= 1, 'plate trays are cut panels');
assertTrue(($r['PULL_OUT_SLIDE'] ?? 0) >= 1, 'plate trays include pull-out slides');

$bottle = $rules->apply('CFG_KB_BOTTLE', [
    'door_type' => 'HINGED',
    'bays' => [['id' => 'bay-1', 'label' => 'Bay 1', 'width_mm' => 200, 'sections' => []]],
], 'bay-1');
$partsBottle = $engine->generate('KITCHEN_BASE', [
    'width' => 200, 'height' => 720, 'depth' => 560,
    'carcass_thickness' => 18, 'back_thickness' => 18, 'shutter_count' => 0, 'door_type' => 'NONE',
    'layout' => $bottle['layout'],
]);
$rb = rolesOf($partsBottle);
assertTrue(($rb['SHELF_BOTTLE'] ?? 0) >= 1, 'bottle rack shelves generated');
assertTrue(($rb['BOTTLE_PULLOUT'] ?? 0) >= 1, 'bottle pull-out hardware generated');

$waste = $rules->apply('CFG_KB_WASTE', [
    'bays' => [['id' => 'bay-1', 'label' => 'Bay 1', 'width_mm' => null, 'sections' => []]],
], 'bay-1');
assertTrue(!empty($waste['layout']['bays'][0]['sections'][0]['waste_bin']), 'waste_bin flag set');
$partsWaste = $engine->generate('KITCHEN_BASE', [
    'width' => 450, 'height' => 720, 'depth' => 560,
    'carcass_thickness' => 18, 'back_thickness' => 18, 'shutter_count' => 0, 'door_type' => 'NONE',
    'layout' => $waste['layout'],
]);
$rw = rolesOf($partsWaste);
assertTrue(($rw['WASTE_BIN'] ?? 0) >= 1, 'waste bin hardware present');
assertTrue(($rw['NICHE_BACK'] ?? 0) >= 1, 'waste bay still has niche liners');

$trouser = $rules->apply('CFG_TROUSER', [
    'bays' => [['id' => 'bay-1', 'label' => 'Bay 1', 'width_mm' => null, 'sections' => []]],
], 'bay-1');
$partsTrouser = $engine->generate('WARDROBE', [
    'width' => 600, 'height' => 2400, 'depth' => 600,
    'carcass_thickness' => 18, 'back_thickness' => 18, 'shutter_count' => 0, 'door_type' => 'NONE',
    'layout' => $trouser['layout'],
]);
$rt = rolesOf($partsTrouser);
assertTrue(($rt['TROUSER_RACK'] ?? 0) >= 1, 'trouser rack hardware present');
assertTrue(($rt['TROUSER_ARM'] ?? 0) >= 1, 'trouser arms in BOM');

$wicker = $rules->apply('CFG_WICKER', [
    'door_type' => 'HINGED',
    'bays' => [['id' => 'bay-1', 'label' => 'Bay 1', 'width_mm' => null, 'sections' => []]],
], 'bay-1');
$partsWicker = $engine->generate('WARDROBE', [
    'width' => 600, 'height' => 2400, 'depth' => 600,
    'carcass_thickness' => 18, 'back_thickness' => 18, 'shutter_count' => 0, 'door_type' => 'NONE',
    'layout' => $wicker['layout'],
]);
$rwi = rolesOf($partsWicker);
assertTrue(($rwi['WICKER_FRONT'] ?? 0) >= 1, 'wicker fronts are panels');
assertTrue(($rwi['WICKER_BASKET'] ?? 0) >= 1, 'wicker basket hardware present');
assertTrue(($rwi['DRAWER_SIDE'] ?? 0) === 0, 'wicker does not cut wood drawer boxes');

$hob = $rules->apply('CFG_KB_HOB', [
    'bays' => [['id' => 'bay-1', 'label' => 'Bay 1', 'width_mm' => null, 'sections' => []]],
], 'bay-1');
assertTrue(!empty($hob['layout']['bays'][0]['sections'][0]['hob_bay']), 'hob_bay flag set');
$partsHob = $engine->generate('KITCHEN_BASE', [
    'width' => 600, 'height' => 720, 'depth' => 560,
    'carcass_thickness' => 18, 'back_thickness' => 18, 'shutter_count' => 0, 'door_type' => 'NONE',
    'layout' => $hob['layout'],
]);
$rh = rolesOf($partsHob);
assertTrue(($rh['HOB_CLEARANCE'] ?? 0) >= 1, 'hob clearance note in hardware');
assertTrue(($rh['SHELF'] ?? 0) === 0, 'hob bay has no shelves');

$present = $rules->detectPresentConfigIds($wicker['layout']);
assertTrue(in_array('CFG_WICKER', $present, true), 'detect wicker present');

echo "Phase 5 specialty stub implementations done\n";
