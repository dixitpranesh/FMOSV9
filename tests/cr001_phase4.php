<?php

declare(strict_types=1);

use Fmos\Core\Database;
use Fmos\Core\Env;
use Fmos\Domains\Catalog\MaterialService;
use Fmos\Domains\Furniture\FurnitureEngine;
use Fmos\Domains\Project\ProjectService;

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
$orgId = (int) $pdo->query("SELECT id FROM organizations WHERE tenant_id={$tenantId} LIMIT 1")->fetchColumn();
$mats = (new MaterialService())->list($tenantId, 'LAMINATE');
$client = (new ProjectService())->createClient($tenantId, ['name' => 'CR001 P4 Client']);
$project = (new ProjectService())->createProject($tenantId, [
    'organization_id' => $orgId,
    'client_id' => (int) $client['id'],
    'name' => 'CR001 Phase4 Project',
]);
$engine = new FurnitureEngine();
$furniture = $engine->createInstance($tenantId, [
    'template_code' => 'WARDROBE',
    'project_id' => (int) $project['id'],
    'name' => 'Comp Wardrobe',
    'parameters' => ['width' => 2100, 'height' => 2400, 'depth' => 600, 'shutter_count' => 2],
]);
$rows = $engine->listComponentRows($tenantId, (int) $furniture['id']);
assertTrue(count($rows) > 0, 'components listed');

$shutter = null;
foreach ($rows as $r) {
    if (stripos((string) $r['name'], 'Shutter') !== false) {
        $shutter = $r;
        break;
    }
}
assertTrue($shutter !== null, 'shutter component found');
$finishId = (int) $mats[0]['id'];
$updated = $engine->updateComponent($tenantId, (int) $furniture['id'], (int) $shutter['id'], [
    'finish_id' => $finishId,
]);
assertTrue((int) $updated['finish_id'] === $finishId, 'per-component finish override saved');

echo "CR-001 Phase 4 checks passed\n";
