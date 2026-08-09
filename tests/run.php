<?php

declare(strict_types=1);

/**
 * Lightweight unit checks for pricing formulas (DEC-017)
 */

function assertTrue(bool $cond, string $msg): void
{
    if (!$cond) {
        throw new RuntimeException($msg);
    }
}

// Markup: Cost * (1 + m/100)
$cost = 80000.0;
$markup = 25.0;
$gross = $cost * (1 + $markup / 100);
assertTrue(abs($gross - 100000.0) < 0.001, 'markup formula');

// Margin: Cost / (1 - m/100) with m=20 => 0.20
$margin = 20.0;
$sell = $cost / (1 - $margin / 100);
assertTrue(abs($sell - 100000.0) < 0.001, 'margin formula DEC-017');

// Wardrobe shelf width rule
$width = 2400; $t = 18;
$shelf = $width - (2 * $t);
assertTrue($shelf === 2364, 'shelf width rule');

echo "Unit checks passed\n";
