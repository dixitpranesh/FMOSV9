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

echo "Phase 4 internals manufacturing tests\n";

$engine = new FurnitureLayoutEngine();
$rules = new ModuleRulesEngine();

$long = InternalConfigCatalog::get('CFG_HANGING_LONG');
assertTrue(!empty($long['implemented']), 'long hanging implemented');
$dbl = InternalConfigCatalog::get('CFG_HANGING_DOUBLE');
assertTrue(!empty($dbl['implemented']), 'double hanging implemented');
$shoe = InternalConfigCatalog::get('CFG_SHOE');
assertTrue(!empty($shoe['implemented']), 'shoe implemented');
$cut = InternalConfigCatalog::get('CFG_KB_CUTLERY');
assertTrue(!empty($cut['implemented']), 'cutlery implemented');

$longApplied = $rules->apply('CFG_HANGING_LONG', [
    'door_type' => 'HINGED',
    'bays' => [['id' => 'bay-1', 'label' => 'Bay 1', 'width_mm' => null, 'sections' => []]],
], 'bay-1');
assertTrue(($longApplied['layout']['bays'][0]['sections'][0]['hanging_style'] ?? '') === 'long', 'apply sets hanging_style long');

$parts = $engine->generate('WARDROBE', [
    'width' => 1200,
    'height' => 2400,
    'depth' => 600,
    'carcass_thickness' => 18,
    'back_thickness' => 18,
    'shutter_count' => 2,
    'door_type' => 'HINGED',
    'layout' => $longApplied['layout'],
]);
$roles = array_count_values(array_map(static fn ($p) => $p['role'] ?? '', $parts));
assertTrue(($roles['HANGING_ROD'] ?? 0) >= 1, 'long hanging generates rod hardware');
assertTrue(($roles['HANGING_CLEAT'] ?? 0) >= 1, 'long hanging generates cleat');
assertTrue(($roles['SHELF'] ?? 0) >= 1, 'long hanging generates top shelf panel');

$dblApplied = $rules->apply('CFG_HANGING_DOUBLE', [
    'door_type' => 'HINGED',
    'bays' => [['id' => 'bay-1', 'label' => 'Bay 1', 'width_mm' => null, 'sections' => []]],
], 'bay-1');
$partsDbl = $engine->generate('WARDROBE', [
    'width' => 1200,
    'height' => 2400,
    'depth' => 600,
    'carcass_thickness' => 18,
    'back_thickness' => 18,
    'shutter_count' => 0,
    'door_type' => 'NONE',
    'layout' => $dblApplied['layout'],
]);
$rodQty = 0;
foreach ($partsDbl as $p) {
    if (($p['role'] ?? '') === 'HANGING_ROD') {
        $rodQty += (int) ($p['qty'] ?? 0);
    }
}
assertTrue($rodQty === 2, 'double hanging generates 2 rods');

$shoeApplied = $rules->apply('CFG_SHOE', [
    'bays' => [['id' => 'bay-1', 'label' => 'Bay 1', 'width_mm' => null, 'sections' => []]],
], 'bay-1');
assertTrue(($shoeApplied['layout']['bays'][0]['sections'][0]['shelf_style'] ?? '') === 'shoe', 'shoe shelf_style set');
$partsShoe = $engine->generate('WARDROBE', [
    'width' => 900,
    'height' => 2400,
    'depth' => 600,
    'carcass_thickness' => 18,
    'back_thickness' => 18,
    'shutter_count' => 0,
    'door_type' => 'NONE',
    'layout' => $shoeApplied['layout'],
]);
$shoePanels = array_filter($partsShoe, static fn ($p) => ($p['role'] ?? '') === 'SHELF_SHOE');
assertTrue(count($shoePanels) >= 1, 'shoe shelves are real SHELF_SHOE panels');
$shoeHw = array_filter($partsShoe, static fn ($p) => ($p['role'] ?? '') === 'SHELF_PIN');
assertTrue(count($shoeHw) >= 1, 'shoe supports in hardware BOM');

$cutApplied = $rules->apply('CFG_KB_CUTLERY', [
    'door_type' => 'HINGED',
    'bays' => [['id' => 'bay-1', 'label' => 'Cabinet', 'width_mm' => null, 'sections' => [
        ['type' => 'SHELVES', 'shelf_count' => 1, 'height_mm' => null],
    ]]],
], 'bay-1');
assertTrue(($cutApplied['layout']['door_type'] ?? '') === 'NONE', 'cutlery sets door NONE');
assertTrue(!empty($cutApplied['layout']['bays'][0]['sections'][0]['cutlery_organizer']), 'cutlery_organizer flag');
$partsCut = $engine->generate('KITCHEN_BASE', [
    'width' => 600,
    'height' => 720,
    'depth' => 560,
    'carcass_thickness' => 18,
    'back_thickness' => 18,
    'shutter_count' => 0,
    'door_type' => 'NONE',
    'layout' => $cutApplied['layout'],
]);
$org = array_filter($partsCut, static fn ($p) => ($p['role'] ?? '') === 'CUTLERY_ORGANIZER');
assertTrue(count($org) >= 1, 'cutlery organizer hardware present');
$drawerFronts = array_filter($partsCut, static fn ($p) => ($p['role'] ?? '') === 'DRAWER_FRONT');
assertTrue(count($drawerFronts) >= 1, 'cutlery still generates drawer panels');

$v2Presets = InternalConfigCatalog::wardrobePresetsFromV2();
assertTrue(count($v2Presets) >= 1, 'V2 wardrobe presets compiled');
$ids = array_column($v2Presets, 'id');
assertTrue(in_array('preset_all_hanging', $ids, true), 'preset_all_hanging present');

$allWardrobe = InternalConfigCatalog::layoutPresets('WARDROBE');
assertTrue(count($allWardrobe) > 3, 'wardrobe presets include V2 + built-ins');

echo "Phase 4 internals manufacturing tests done\n";
