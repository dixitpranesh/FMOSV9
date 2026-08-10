<?php

declare(strict_types=1);

use Fmos\Core\Database;
use Fmos\Core\Env;
use Fmos\Domains\Manufacturing\ManufacturingService;

require dirname(__DIR__) . '/vendor/autoload.php';
Env::load(dirname(__DIR__) . '/.env');

function assertTrue(bool $c, string $m): void
{
    if (!$c) {
        throw new RuntimeException('FAIL: ' . $m);
    }
    echo "  OK  {$m}\n";
}

$svc = new ManufacturingService();
[$cutL, $cutW] = $svc->computeCutting(232, 280, 0.8, 0.8, 0.8, 0.8, [
    'mode' => 'sum_opposite_edges',
    'rounding_decimals' => 1,
]);
assertTrue($cutL === 230.4 || abs($cutL - 230.4) < 0.01, 'cutting length 232-1.6');
assertTrue(abs($cutW - 278.4) < 0.01, 'cutting width 280-1.6');

// Integer-ish sample from TV unit (-2mm): if edges 1.0+1.0
[$cutL2, $cutW2] = $svc->computeCutting(232, 280, 1.0, 1.0, 1.0, 1.0, [
    'mode' => 'sum_opposite_edges',
    'rounding_decimals' => 0,
]);
assertTrue($cutL2 === 230.0, 'integer cutting length');
assertTrue($cutW2 === 278.0, 'integer cutting width');

echo "Cutting-size rule checks passed\n";
