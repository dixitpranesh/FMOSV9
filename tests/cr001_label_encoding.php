<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Fmos\Support\SimplePdf;
use Fmos\Support\TextNormalizer;

$in = 'Kitchen Base — Left Panel · Shelf';
$out = TextNormalizer::ascii($in);
if ($out !== 'Kitchen Base - Left Panel - Shelf') {
    throw new RuntimeException('normalize failed: ' . $out);
}

$pdf = new SimplePdf();
$pdf->addPage();
$pdf->text(50, 500, $in, 12);
$bytes = $pdf->output();
if (!str_contains($bytes, 'Kitchen Base - Left Panel - Shelf')) {
    throw new RuntimeException('PDF missing ascii label');
}
if (str_contains($bytes, '???')) {
    throw new RuntimeException('PDF still contains ???');
}
echo "Label encoding checks passed\n";
