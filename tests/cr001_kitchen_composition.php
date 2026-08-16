<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Fmos\Domains\Furniture\FurnitureTemplateCatalog;
use Fmos\Domains\Furniture\KitchenPlacement;

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

echo "Kitchen composition unit tests\n";

$catalog = FurnitureTemplateCatalog::all();
assertTrue(isset($catalog['KITCHEN_BASE']), 'KITCHEN_BASE template exists');
assertTrue(isset($catalog['KITCHEN_CORNER']), 'KITCHEN_CORNER template exists');
assertTrue(($catalog['KITCHEN_CORNER']['category'] ?? '') === 'KITCHEN', 'corner is KITCHEN category');

$widths = KitchenPlacement::splitRun(1800, 600);
assertTrue($widths === [600.0, 600.0, 600.0], '1800 splits into three 600s');
$widths2 = KitchenPlacement::splitRun(1500, 600);
assertTrue(count($widths2) === 3 && abs(array_sum($widths2) - 1500) < 0.1, '1500 splits preserving length');

$placed = KitchenPlacement::placeL([
    ['run' => 'A', 'role' => 'shelf', 'sort' => 0, 'width_mm' => 600, 'depth_mm' => 560, 'height_mm' => 720, 'furniture_id' => 1],
    ['run' => 'A', 'role' => 'shelf', 'sort' => 1, 'width_mm' => 600, 'depth_mm' => 560, 'height_mm' => 720, 'furniture_id' => 2],
    ['run' => 'CORNER', 'role' => 'corner', 'sort' => 0, 'width_mm' => 900, 'depth_mm' => 900, 'height_mm' => 720, 'furniture_id' => 3],
    ['run' => 'B', 'role' => 'drawers', 'sort' => 0, 'width_mm' => 600, 'depth_mm' => 560, 'height_mm' => 720, 'furniture_id' => 4],
], 900, 560);

$byRun = [];
foreach ($placed as $p) {
    $byRun[$p['run']][] = $p;
}
assertTrue(count($byRun['CORNER'] ?? []) === 1, 'one corner placed');
assertTrue(count($byRun['A'] ?? []) === 2, 'two run A modules');
assertTrue(count($byRun['B'] ?? []) === 1, 'one run B module');
assertTrue(($byRun['CORNER'][0]['position']['x'] ?? -1) === 0.0, 'corner at origin X');
assertTrue(($byRun['A'][0]['position']['x'] ?? 0) === 900.0, 'run A starts after corner');
assertTrue(($byRun['B'][0]['position']['rotation'] ?? 0) === 90.0, 'run B rotated 90');

$bounds = KitchenPlacement::bounds($placed);
assertTrue($bounds['width'] >= 900 + 600 + 600 - 1, 'bounds include run A length');
assertTrue($bounds['depth'] >= 900 + 600 - 1, 'bounds include run B length');

echo "Kitchen composition unit tests done\n";
