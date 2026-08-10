<?php

declare(strict_types=1);

/**
 * CR-001 Phase 2b checks: laminate materials imported and textured.
 * Run: php tests/cr001_phase2b.php
 */

use Fmos\Core\Database;
use Fmos\Core\Env;
use Fmos\Domains\Catalog\MaterialService;

require dirname(__DIR__) . '/vendor/autoload.php';
Env::load(dirname(__DIR__) . '/.env');

function assertTrue(bool $cond, string $msg): void
{
    if (!$cond) {
        throw new RuntimeException('FAIL: ' . $msg);
    }
    echo "  OK  {$msg}\n";
}

$pdo = Database::connection();
$tenantId = (int) $pdo->query("SELECT id FROM tenants WHERE code='demo' LIMIT 1")->fetchColumn();
$svc = new MaterialService();
$list = $svc->list($tenantId, 'LAMINATE');
assertTrue(count($list) >= 45, 'at least 45 laminate SKUs');

$sample = null;
foreach ($list as $row) {
    if ($row['sku'] === '20107_SHR_13_1') {
        $sample = $row;
        break;
    }
}
assertTrue($sample !== null, 'sample SKU 20107_SHR_13_1 present');
assertTrue(($sample['series_name'] ?? '') === 'Shore', 'SHR maps to Shore');
assertTrue(($sample['name'] ?? '') === '20107_SHR_13_1', 'display name equals SKU');
assertTrue(!empty($sample['assets']), 'texture asset attached');

$url = $sample['assets'][0]['public_url'] ?? '';
$abs = dirname(__DIR__) . '/public' . $url;
assertTrue(is_file($abs), 'public texture file exists at ' . $url);
assertTrue(!str_contains($url, 'C:\\laminates') && !str_contains($url, '/laminates/'), 'runtime URL not coupled to C:\\laminates');

echo "CR-001 Phase 2b checks passed\n";
