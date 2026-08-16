<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Fmos\Domains\Catalog\HardwareSkuCatalog;
use Fmos\Domains\Furniture\FurnitureLayoutEngine;
use Fmos\Domains\Manufacturing\EdgeBandBom;

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

echo "Hardware SKU + edge-band BOM tests\n";

assertTrue(HardwareSkuCatalog::skuForRole('HINGE') === 'HW-HINGE-01', 'hinge SKU');
assertTrue(HardwareSkuCatalog::skuForRole('CUTLERY_ORGANIZER') === 'HW-CUTLERY-01', 'cutlery SKU');
assertTrue(HardwareSkuCatalog::skuForRole('BOTTLE_PULLOUT') === 'HW-BOTTLE-01', 'bottle SKU');
assertTrue(HardwareSkuCatalog::skuForRole('WICKER_BASKET') === 'HW-WICKER-01', 'wicker SKU');
assertTrue(HardwareSkuCatalog::skuForRole('UNKNOWN_ROLE') === 'HW-GENERIC', 'unknown falls back');

$defs = HardwareSkuCatalog::definitions();
assertTrue(isset($defs['DRAWER_SLIDE']), 'drawer slide defined');
assertTrue(count(HardwareSkuCatalog::seedRows()) >= 10, 'seed rows cover specialty hardware');

// 600×400 panel, all 4 sides edged, qty 2 → (600+600+400+400)*2/1000 = 4.0 m
$m = EdgeBandBom::metersForPanel(600, 400, 2, [
    'edge_1' => 0.8, 'edge_2' => 0.8, 'edge_3' => 0.8, 'edge_4' => 0.8,
]);
assertTrue(abs($m - 4.0) < 0.0001, 'four-side edge meters');

$m2 = EdgeBandBom::metersForPanel(1000, 500, 1, [
    'edge_1' => 0.8, 'edge_2' => 0, 'edge_3' => 0.8, 'edge_4' => 0,
]);
assertTrue(abs($m2 - 1.5) < 0.0001, 'partial edge meters');

$m0 = EdgeBandBom::metersForPanel(1000, 500, 1, [
    'edge_1' => 0, 'edge_2' => 0, 'edge_3' => 0, 'edge_4' => 0,
]);
assertTrue($m0 === 0.0, 'no edges → 0 meters');

$agg = EdgeBandBom::aggregateFromPanels(0, [
    [
        'finishing_length_mm' => 600,
        'finishing_width_mm' => 400,
        'quantity' => 1,
        'edge_1' => 0.8, 'edge_2' => 0.8, 'edge_3' => 0.8, 'edge_4' => 0.8,
    ],
    [
        'finishing_length_mm' => 800,
        'finishing_width_mm' => 200,
        'quantity' => 1,
        'edge_1' => 0, 'edge_2' => 0, 'edge_3' => 0, 'edge_4' => 0,
    ],
]);
assertTrue(abs($agg['meters'] - 2.0) < 0.0001, 'aggregate skips unedged panels');
assertTrue($agg['panel_count'] === 1, 'panel_count counts edged only');
assertTrue($agg['uom'] === 'M', 'edge UOM is meters');

$engine = new FurnitureLayoutEngine();
$parts = $engine->generate('WARDROBE', [
    'width' => 1200,
    'height' => 2400,
    'depth' => 600,
    'carcass_thickness' => 18,
    'back_thickness' => 18,
    'shutter_count' => 2,
    'door_type' => 'HINGED',
    'layout' => [
        'door_type' => 'HINGED',
        'bays' => [[
            'id' => 'bay-1',
            'label' => 'Bay 1',
            'sections' => [
                ['type' => 'HANGING', 'height_mm' => 1100, 'hanging_style' => 'standard', 'label' => 'Hang'],
                ['type' => 'DRAWERS', 'height_mm' => 600, 'drawer_count' => 2, 'drawer_height_mm' => 180, 'cutlery_organizer' => true, 'label' => 'Drawers'],
            ],
        ]],
    ],
]);
$hwRoles = [];
foreach ($parts as $p) {
    if (($p['type'] ?? '') === 'HARDWARE') {
        $role = (string) ($p['role'] ?? 'HARDWARE');
        $hwRoles[$role] = HardwareSkuCatalog::skuForRole($role);
    }
}
assertTrue(($hwRoles['HINGE'] ?? '') === 'HW-HINGE-01', 'wardrobe hinge maps to SKU');
assertTrue(($hwRoles['HANGING_ROD'] ?? '') === 'HW-ROD-OVAL', 'rod maps to SKU');
assertTrue(($hwRoles['DRAWER_SLIDE'] ?? '') === 'HW-SLIDE-450', 'slide maps to SKU');
assertTrue(($hwRoles['CUTLERY_ORGANIZER'] ?? '') === 'HW-CUTLERY-01', 'cutlery maps to SKU');

$edgeFromComponents = EdgeBandBom::aggregateFromComponents(0, array_map(static function ($p) {
    return [
        'type' => $p['type'],
        'length_mm' => $p['length_mm'],
        'width_mm' => $p['width_mm'],
        'thickness_mm' => $p['thickness_mm'],
        'qty' => $p['qty'],
    ];
}, $parts), [
    'edge_1' => 0.8, 'edge_2' => 0.8, 'edge_3' => 0.8, 'edge_4' => 0.8, 'apply_to_thickness_gte_mm' => 12,
]);
assertTrue($edgeFromComponents['meters'] > 0, 'component estimate yields edge meters');
assertTrue($edgeFromComponents['panel_count'] >= 1, 'edged panel components counted');

echo "Hardware SKU + edge-band BOM tests done\n";
