<?php

declare(strict_types=1);

/**
 * CR-001 Phase 1 unit checks: furniture without room + component dual-write.
 * Run: php tests/cr001_phase1.php
 */

use Fmos\Core\Database;
use Fmos\Core\Env;
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
assertTrue($tenantId > 0, 'demo tenant exists');

$orgId = (int) $pdo->query("SELECT id FROM organizations WHERE tenant_id={$tenantId} LIMIT 1")->fetchColumn();
$client = (new ProjectService())->createClient($tenantId, [
    'name' => 'CR001 Phase1 Client',
    'company' => 'CR001',
]);
$project = (new ProjectService())->createProject($tenantId, [
    'organization_id' => $orgId,
    'client_id' => (int) $client['id'],
    'name' => 'CR001 Phase1 Project',
]);
assertTrue(($project['model_mode'] ?? '') === 'FURNITURE_FIRST', 'new project is FURNITURE_FIRST');

$engine = new FurnitureEngine();
$furniture = $engine->createInstance($tenantId, [
    'template_code' => 'WARDROBE',
    'project_id' => (int) $project['id'],
    'room_id' => null,
    'name' => 'No-Room Wardrobe',
    'quantity' => 2,
    'parameters' => [
        'width' => 2400,
        'height' => 2400,
        'depth' => 600,
        'carcass_thickness' => 18,
        'back_thickness' => 6,
        'shelf_count' => 3,
        'shutter_count' => 2,
    ],
]);

assertTrue($furniture['room_id'] === null, 'furniture created without room_id');
assertTrue((int) $furniture['quantity'] === 2, 'quantity stored');
assertTrue((float) $furniture['width_mm'] === 2400.0, 'width_mm synced');
assertTrue(!empty($furniture['code']), 'auto code assigned');
assertTrue(count($furniture['component_rows']) > 0, 'component_rows dual-written');
assertTrue(count($furniture['components']) === count($furniture['component_rows']), 'JSON and table component counts match');

$roomId = (int) $project['buildings'][0]['floors'][0]['rooms'][0]['id'];
$legacy = $engine->createInstance($tenantId, [
    'template_code' => 'WARDROBE',
    'project_id' => (int) $project['id'],
    'room_id' => $roomId,
    'name' => 'Legacy Room Wardrobe',
    'parameters' => ['width' => 1800, 'height' => 2100, 'depth' => 550],
]);
assertTrue((int) $legacy['room_id'] === $roomId, 'legacy room_id still works');

$sheets = (int) $pdo->query("SELECT COUNT(*) FROM sheet_definitions WHERE tenant_id={$tenantId}")->fetchColumn();
assertTrue($sheets >= 1, 'sheet_definitions seeded');
$rules = (int) $pdo->query("SELECT COUNT(*) FROM tenant_manufacturing_rules WHERE tenant_id={$tenantId}")->fetchColumn();
assertTrue($rules >= 1, 'tenant_manufacturing_rules seeded');

echo "CR-001 Phase 1 checks passed\n";
