<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Fmos\Domains\Furniture\FurnitureEngine;

$engine = new FurnitureEngine();
$components = $engine->generateComponents('WARDROBE', [
    'width' => 2700,
    'height' => 2400,
    'depth' => 600,
    'carcass_thickness' => 18,
    'back_thickness' => 6,
    'shelf_count' => 3,
    'shutter_count' => 2,
]);

$sheetL = 2440;
$sheetW = 1220;
$ok = true;
foreach ($components as $c) {
    if (($c['type'] ?? '') === 'HARDWARE') {
        continue;
    }
    $l = $c['length_mm'];
    $w = $c['width_mm'];
    $fits = ($l <= $sheetL && $w <= $sheetW) || ($w <= $sheetL && $l <= $sheetW);
    echo sprintf("%-10s %7.2f x %7.2f qty=%d %s %s\n", $c['name'], $l, $w, $c['qty'], $fits ? 'OK' : 'FAIL', $c['note'] ?? '');
    if (!$fits) {
        $ok = false;
    }
}
echo $ok ? "ALL_FIT\n" : "SOME_FAIL\n";
exit($ok ? 0 : 1);
