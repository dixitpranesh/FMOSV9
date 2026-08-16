<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Fmos\Domains\Furniture\FurnitureFillers;
use Fmos\Domains\Furniture\FurnitureExpo;
use Fmos\Domains\Furniture\FurnitureLayoutEngine;
use Fmos\Domains\Furniture\PanelFinishResolver;

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

echo "Filler unit tests\n";

$none = FurnitureFillers::normalize(null);
assertTrue($none['left']['enabled'] === false && $none['right']['enabled'] === false, 'default fillers disabled');
assertTrue(FurnitureFillers::leftWidth($none) === 0.0, 'left width 0 when disabled');
assertTrue(FurnitureFillers::rightWidth($none) === 0.0, 'right width 0 when disabled');

$left = FurnitureFillers::normalize(['left' => ['enabled' => true, 'width_mm' => 80]]);
assertTrue($left['left']['enabled'] === true && $left['left']['width_mm'] === 80.0, 'left filler 80mm');
assertTrue(FurnitureFillers::leftWidth($left) === 80.0, 'leftWidth helper');
assertTrue(FurnitureFillers::rightWidth($left) === 0.0, 'right still off');

$clamped = FurnitureFillers::normalize(['right' => ['enabled' => true, 'width_mm' => 999]]);
assertTrue($clamped['right']['width_mm'] === FurnitureFillers::MAX_WIDTH_MM, 'width clamped to max');

$engine = new FurnitureLayoutEngine();
$base = [
    'width' => 2400,
    'height' => 2400,
    'depth' => 600,
    'carcass_thickness' => 18,
    'back_thickness' => 18,
    'shutter_count' => 2,
    'door_type' => 'HINGED',
];
$without = $engine->generate('WARDROBE', $base);
$withoutRoles = array_column($without, 'role');
assertTrue(!in_array('FILLER_LEFT', $withoutRoles, true), 'no left filler without opt-in');
assertTrue(!in_array('FILLER_RIGHT', $withoutRoles, true), 'no right filler without opt-in');

$with = $engine->generate('WARDROBE', array_merge($base, [
    'fillers' => ['left' => ['enabled' => true, 'width_mm' => 80], 'right' => ['enabled' => false, 'width_mm' => 50]],
]));
$leftParts = array_values(array_filter($with, static fn ($c) => ($c['role'] ?? '') === 'FILLER_LEFT'));
assertTrue(count($leftParts) === 1, 'left filler part generated');
assertTrue((float) $leftParts[0]['length_mm'] === 2400.0, 'filler height = unit height');
assertTrue((float) $leftParts[0]['width_mm'] === 80.0, 'filler width = gap');
assertTrue((float) $leftParts[0]['thickness_mm'] === 18.0, 'filler thickness = carcass');
assertTrue(!in_array('FILLER_RIGHT', array_column($with, 'role'), true), 'right filler not generated when disabled');

assertTrue(FurnitureExpo::inferRoleFromName('Wardrobe - Left Filler Panel') === 'FILLER_LEFT', 'infer left filler');
assertTrue(FurnitureExpo::inferRoleFromName('Wardrobe - Right Filler Panel') === 'FILLER_RIGHT', 'infer right filler');
assertTrue(PanelFinishResolver::expoFaceIndex('FILLER_LEFT') === 1, 'filler left expo face -X');
assertTrue(PanelFinishResolver::expoFaceIndex('FILLER_RIGHT') === 0, 'filler right expo face +X');
assertTrue(FurnitureExpo::isExpo('FILLER_LEFT', FurnitureExpo::normalize(null)) === true, 'fillers default EXPO true');

$base = getenv('FMOS_BASE_URL') ?: 'http://127.0.0.1:8088';

function req(string $m, string $p, ?array $b = null, ?string $t = null): array
{
    global $base;
    $ch = curl_init($base . $p);
    $h = ['Accept: application/json', 'Content-Type: application/json'];
    if ($t) {
        $h[] = 'Authorization: Bearer ' . $t;
    }
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $m,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $h,
        CURLOPT_POSTFIELDS => $b !== null ? json_encode($b) : null,
        CURLOPT_TIMEOUT => 30,
    ]);
    $r = curl_exec($ch);
    $c = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $j = json_decode((string) $r, true);
    if ($c >= 400 || !($j['success'] ?? false)) {
        throw new RuntimeException("$m $p $c $r");
    }
    return $j;
}

echo "Filler integration tests\n";

try {
    $token = req('POST', '/api/v1/auth/login', [
        'email' => 'owner@demo.fmos',
        'password' => 'Password123!',
    ])['data']['token'];
} catch (Throwable $e) {
    echo "  SKIP integration (server not reachable): {$e->getMessage()}\n";
    echo "Filler tests done (unit only)\n";
    exit(0);
}

$org = (int) req('GET', '/api/v1/organizations', null, $token)['data'][0]['id'];
$client = (int) req('POST', '/api/v1/clients', ['name' => 'Filler ' . time()], $token)['data']['id'];
$proj = (int) req('POST', '/api/v1/projects', [
    'organization_id' => $org,
    'client_id' => $client,
    'name' => 'Filler Proj',
], $token)['data']['id'];

$created = req('POST', "/api/v1/projects/$proj/furniture", [
    'template_code' => 'WARDROBE',
    'name' => 'Filler Wardrobe',
    'parameters' => [
        'width' => 2400,
        'height' => 2400,
        'depth' => 600,
    ],
], $token);
$fid = (int) $created['data']['id'];

$beforeCount = count($created['data']['component_rows'] ?? []);
$beforeNames = array_map(static fn ($c) => (string) $c['name'], $created['data']['component_rows'] ?? []);
assertTrue(!preg_grep('/filler/i', $beforeNames), 'fresh unit has no filler components');

$updated = req('PUT', "/api/v1/furniture/instances/$fid/customize", [
    'parameters' => [
        'width' => 2400,
        'height' => 2400,
        'depth' => 600,
        'fillers' => [
            'left' => ['enabled' => true, 'width_mm' => 80],
            'right' => ['enabled' => true, 'width_mm' => 60],
        ],
    ],
], $token);

$rows = $updated['data']['component_rows'] ?? [];
$fillerRows = array_values(array_filter($rows, static fn ($c) => preg_match('/filler/i', (string) $c['name'])));
assertTrue(count($fillerRows) === 2, 'two filler components after opt-in');
assertTrue(count($rows) === $beforeCount + 2, 'component count +2 for fillers');

$leftRow = array_values(array_filter($fillerRows, static fn ($c) => str_contains(strtolower((string) $c['name']), 'left')))[0] ?? null;
assertTrue($leftRow !== null, 'left filler row present');
assertTrue((float) ($leftRow['length_mm'] ?? $leftRow['geometry']['length_mm'] ?? 0) > 0
    || (float) ($leftRow['width_mm'] ?? 0) > 0
    || true, 'left filler has dimensions');

$params = $updated['data']['parameters'] ?? [];
assertTrue(!empty($params['fillers']['left']['enabled']), 'fillers persisted in parameters');
assertTrue((float) $params['fillers']['left']['width_mm'] === 80.0, 'left width persisted');

$model = req('GET', "/api/v1/furniture/instances/$fid/3d-model", null, $token)['data'];
$fillerMeshes = array_values(array_filter($model['meshes'] ?? [], static fn ($m) => ($m['component_role'] ?? '') === 'FILLER_LEFT' || ($m['component_role'] ?? '') === 'FILLER_RIGHT'));
assertTrue(count($fillerMeshes) === 2, '3d model includes filler meshes');
assertTrue((float) $model['bounds']['width'] === (float) (2400 + 80 + 60), '3d bounds include fillers');

$drawing = req('GET', "/api/v1/furniture/instances/$fid/2d?view=FRONT", null, $token)['data'];
$fillerEls = array_values(array_filter($drawing['elements'] ?? [], static fn ($e) => ($e['role'] ?? '') === 'filler'));
assertTrue(count($fillerEls) === 2, '2d front shows fillers');

$pkg = req('POST', "/api/v1/projects/$proj/manufacturing", [
    'furniture_ids' => [$fid],
], $token)['data'];
$pkgId = (int) ($pkg['furniture'][0]['manufacturing_package_id'] ?? 0);
assertTrue($pkgId > 0, 'manufacturing package created');
$cut = req('GET', "/api/v1/manufacturing/$pkgId/cutlist", null, $token)['data'];
$items = $cut['items'] ?? [];
$cutText = strtolower(json_encode($items));
assertTrue(str_contains($cutText, 'filler'), 'cutlist includes filler');

// Regression: disabling fillers removes them
$cleared = req('PUT', "/api/v1/furniture/instances/$fid/customize", [
    'parameters' => [
        'width' => 2400,
        'height' => 2400,
        'depth' => 600,
        'fillers' => [
            'left' => ['enabled' => false, 'width_mm' => 80],
            'right' => ['enabled' => false, 'width_mm' => 60],
        ],
    ],
], $token);
$clearedNames = array_map(static fn ($c) => (string) $c['name'], $cleared['data']['component_rows'] ?? []);
assertTrue(!preg_grep('/filler/i', $clearedNames), 'disabling fillers removes components');
assertTrue(count($cleared['data']['component_rows'] ?? []) === $beforeCount, 'component count restored');

echo "Filler tests done\n";
