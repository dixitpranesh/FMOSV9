<?php

declare(strict_types=1);

/**
 * CR-001 Phase 3: specification + exterior/interior laminate assignment.
 * Run: php tests/cr001_phase3.php
 */

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
assertTrue(count($mats) >= 2, 'laminates available');

$client = (new ProjectService())->createClient($tenantId, ['name' => 'CR001 P3 Client']);
$project = (new ProjectService())->createProject($tenantId, [
    'organization_id' => $orgId,
    'client_id' => (int) $client['id'],
    'name' => 'CR001 Phase3 Project',
]);
$engine = new FurnitureEngine();
$furniture = $engine->createInstance($tenantId, [
    'template_code' => 'WARDROBE',
    'project_id' => (int) $project['id'],
    'name' => 'Spec Wardrobe',
    'parameters' => ['width' => 2100, 'height' => 2400, 'depth' => 600],
]);

$ext = (int) $mats[0]['id'];
$int = (int) $mats[1]['id'];
$updated = $engine->updateMeta($tenantId, (int) $furniture['id'], [
    'exterior_finish_id' => $ext,
    'interior_finish_id' => $int,
    'specification' => ['notes' => 'multi-finish test'],
]);

assertTrue((int) $updated['exterior_finish_id'] === $ext, 'exterior finish saved');
assertTrue((int) $updated['interior_finish_id'] === $int, 'interior finish saved');
assertTrue(($updated['specification']['notes'] ?? '') === 'multi-finish test', 'specification notes saved');

echo "CR-001 Phase 3 checks passed\n";
