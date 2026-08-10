<?php

declare(strict_types=1);

use Fmos\Core\Database;
use Fmos\Core\Env;
use Fmos\Domains\Furniture\FurnitureEngine;
use Fmos\Domains\Project\ProjectService;

require dirname(__DIR__) . '/vendor/autoload.php';
Env::load(dirname(__DIR__) . '/.env');

function assertTrue(bool $c, string $m): void
{
    if (!$c) {
        throw new RuntimeException('FAIL: ' . $m);
    }
    echo "  OK  {$m}\n";
}

$pdo = Database::connection();
$tenantId = (int) $pdo->query("SELECT id FROM tenants WHERE code='demo' LIMIT 1")->fetchColumn();
$orgId = (int) $pdo->query("SELECT id FROM organizations WHERE tenant_id={$tenantId} LIMIT 1")->fetchColumn();
$engine = new FurnitureEngine();
$templates = $engine->listTemplates();
assertTrue(count($templates) >= 10, 'at least 10 furniture templates');
$codes = array_column($templates, 'code');
assertTrue(in_array('TV_UNIT', $codes, true), 'TV_UNIT present');
assertTrue(in_array('WARDROBE_LOFT', $codes, true), 'WARDROBE_LOFT present');
assertTrue(in_array('KITCHEN_TALL', $codes, true), 'KITCHEN_TALL present');

$client = (new ProjectService())->createClient($tenantId, ['name' => 'Template Client']);
$project = (new ProjectService())->createProject($tenantId, [
    'organization_id' => $orgId,
    'client_id' => (int) $client['id'],
    'name' => 'Template Project',
]);

$wardrobe = $engine->createInstance($tenantId, [
    'template_code' => 'WARDROBE',
    'project_id' => (int) $project['id'],
    'name' => 'Custom Wardrobe',
    'parameters' => ['width' => 2400, 'height' => 2400, 'depth' => 600],
]);
assertTrue(isset($wardrobe['parameters']['layout']['bays']), 'wardrobe has layout bays');
assertTrue(count($wardrobe['component_rows']) > 8, 'layout generates many components');

$layout = $wardrobe['parameters']['layout'];
$layout['bays'][] = [
    'id' => 'bay-3',
    'label' => 'Utility',
    'width_mm' => 300,
    'sections' => [
        ['type' => 'SHELVES', 'shelf_count' => 4, 'label' => 'Utility shelves'],
    ],
];
$layout['plinth_height_mm'] = 110;
$layout['loft'] = ['enabled' => true, 'height_mm' => 500, 'shelf_count' => 1];
$updated = $engine->updateLayout($tenantId, (int) $wardrobe['id'], $layout);
assertTrue(count($updated['parameters']['layout']['bays']) === 3, 'third bay saved');
assertTrue(!empty($updated['parameters']['layout']['loft']['enabled']), 'loft enabled');
$names = array_column($updated['component_rows'], 'name');
assertTrue((bool) array_filter($names, static fn ($n) => str_contains((string) $n, 'Utility')), 'utility bay parts present');
assertTrue((bool) array_filter($names, static fn ($n) => str_contains((string) $n, 'Loft')), 'loft parts present');
assertTrue((bool) array_filter($names, static fn ($n) => str_contains((string) $n, 'Plinth')), 'plinth parts present');

$tv = $engine->createInstance($tenantId, [
    'template_code' => 'TV_UNIT',
    'project_id' => (int) $project['id'],
    'name' => 'Living TV',
    'parameters' => ['width' => 1800, 'height' => 600, 'depth' => 450],
]);
assertTrue($tv['category'] === 'TV_UNIT' || $tv['type'] === 'TV_UNIT', 'tv category/type');
assertTrue(count($tv['component_rows']) > 5, 'tv components generated');

echo "Template + layout customization checks passed\n";
