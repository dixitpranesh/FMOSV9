<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Fmos\Domains\Furniture\FurnitureExpo;
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

echo "Back panel / face-finish unit tests\n";

$expo = FurnitureExpo::normalize(['RIGHT_PANEL' => true, 'LEFT_PANEL' => false, 'BACK_PANEL' => false]);
$non = PanelFinishResolver::resolve('LEFT_PANEL', $expo, 10, 20);
assertTrue($non['expo'] === false, 'non-expo left');
assertTrue($non['face_exterior']['finish_id'] === 20 && $non['face_interior']['finish_id'] === 20, 'non-expo both interior');

$ex = PanelFinishResolver::resolve('RIGHT_PANEL', $expo, 10, 20);
assertTrue($ex['expo'] === true, 'expo right');
assertTrue($ex['face_exterior']['finish_id'] === 10, 'expo face exterior laminate');
assertTrue($ex['face_interior']['finish_id'] === 20, 'other face interior laminate');

$both = FurnitureExpo::normalize(['LEFT_PANEL' => true, 'RIGHT_PANEL' => true]);
$l = PanelFinishResolver::resolve('LEFT_PANEL', $both, 10, 20);
$r = PanelFinishResolver::resolve('RIGHT_PANEL', $both, 10, 20);
assertTrue($l['face_exterior']['finish_id'] === 10 && $r['face_exterior']['finish_id'] === 10, 'both sides expo exterior');

$legacy = PanelFinishResolver::resolve('BACK_PANEL', FurnitureExpo::normalize(null), null, null);
assertTrue($legacy['face_exterior']['finish_id'] === null, 'legacy no finishes');

assertTrue(PanelFinishResolver::expoFaceIndex('RIGHT_PANEL') === 0, 'right face +X');
assertTrue(PanelFinishResolver::expoFaceIndex('LEFT_PANEL') === 1, 'left face -X');
assertTrue(PanelFinishResolver::expoFaceIndex('BACK_PANEL') === 5, 'back face -Z');

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
        CURLOPT_TIMEOUT => 25,
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

echo "Back panel integration tests\n";
try {
    $token = req('POST', '/api/v1/auth/login', [
        'email' => 'owner@demo.fmos',
        'password' => 'Password123!',
    ])['data']['token'];
} catch (Throwable $e) {
    echo "  SKIP integration: {$e->getMessage()}\n";
    exit(0);
}

req('POST', '/api/v1/catalog/seed', [], $token);
$org = (int) req('GET', '/api/v1/organizations', null, $token)['data'][0]['id'];
$client = (int) req('POST', '/api/v1/clients', ['name' => 'Back ' . time()], $token)['data']['id'];
$proj = (int) req('POST', '/api/v1/projects', [
    'organization_id' => $org,
    'client_id' => $client,
    'name' => 'Back Proj',
], $token)['data']['id'];

$boards = array_values(array_filter(
    req('GET', '/api/v1/catalog/products', null, $token)['data'],
    static fn ($p) => ($p['category'] ?? '') === 'BOARD'
));
assertTrue(count($boards) >= 1, 'board catalog available');
$board18 = null;
$board6 = null;
foreach ($boards as $b) {
    if ((float) ($b['thickness_mm'] ?? 0) == 18.0) {
        $board18 = $b;
    }
    if ((float) ($b['thickness_mm'] ?? 0) == 6.0) {
        $board6 = $b;
    }
}
assertTrue($board18 !== null, '18mm board exists');

$created = req('POST', "/api/v1/projects/$proj/furniture", [
    'template_code' => 'WARDROBE',
    'name' => 'Back Wardrobe',
    'parameters' => ['width' => 2550, 'height' => 2400, 'depth' => 600, 'door_type' => 'HINGED'],
], $token)['data'];
$id = (int) $created['id'];

// New units get recommended 18mm default from template
$got = req('GET', "/api/v1/furniture/instances/$id", null, $token)['data'];
$bt = (float) ($got['parameters']['back_thickness'] ?? 0);
assertTrue($bt === 18.0, "new wardrobe default back_thickness=18 (got {$bt})");

$backRow = null;
foreach ($got['component_rows'] as $row) {
    if (($row['geometry']['role'] ?? '') === 'BACK_PANEL') {
        $backRow = $row;
        break;
    }
}
assertTrue($backRow !== null && (float) $backRow['thickness_mm'] === 18.0, 'back panel component 18mm');

// Legacy thickness still allowed
$legacy = req('PUT', "/api/v1/furniture/instances/$id/customize", [
    'parameters' => [
        'width' => 2550,
        'height' => 2400,
        'depth' => 600,
        'back_thickness' => 6,
        'carcass_thickness' => 18,
        'door_type' => 'HINGED',
    ],
], $token)['data'];
assertTrue((float) $legacy['parameters']['back_thickness'] === 6.0, 'legacy 6mm still valid');

// 18mm + board material
$withBoard = req('PUT', "/api/v1/furniture/instances/$id/customize", [
    'parameters' => [
        'width' => 2550,
        'height' => 2400,
        'depth' => 600,
        'back_thickness' => 18,
        'back_material_id' => (int) $board18['id'],
        'carcass_thickness' => 18,
        'door_type' => 'HINGED',
    ],
    'expo' => ['RIGHT_PANEL' => true, 'BACK_PANEL' => false],
], $token)['data'];
assertTrue((float) $withBoard['parameters']['back_thickness'] === 18.0, 'set 18mm');
assertTrue((int) $withBoard['parameters']['back_material_id'] === (int) $board18['id'], 'back material persisted');

// Invalid board
try {
    req('PUT', "/api/v1/furniture/instances/$id/customize", [
        'parameters' => [
            'width' => 2550, 'height' => 2400, 'depth' => 600,
            'back_thickness' => 18,
            'back_material_id' => 99999999,
            'door_type' => 'HINGED',
        ],
    ], $token);
    throw new RuntimeException('expected invalid board fail');
} catch (RuntimeException $e) {
    assertTrue(str_contains($e->getMessage(), '422') || str_contains($e->getMessage(), 'not found'), 'invalid board rejected');
}

// Invalid thickness > max
try {
    req('PUT', "/api/v1/furniture/instances/$id/customize", [
        'parameters' => [
            'width' => 2550, 'height' => 2400, 'depth' => 600,
            'back_thickness' => 40,
            'door_type' => 'HINGED',
        ],
    ], $token);
    throw new RuntimeException('expected invalid thickness fail');
} catch (RuntimeException $e) {
    assertTrue(str_contains($e->getMessage(), '422') || str_contains($e->getMessage(), 'maximum'), 'invalid thickness rejected');
}

$mats = req('GET', '/api/v1/materials?category=LAMINATE', null, $token)['data'];
$extId = !empty($mats[0]['id']) ? (int) $mats[0]['id'] : null;
$intId = !empty($mats[1]['id']) ? (int) $mats[1]['id'] : $extId;
if ($extId) {
    req('PUT', "/api/v1/furniture/instances/$id/customize", [
        'exterior_finish_id' => $extId,
        'interior_finish_id' => $intId,
        'expo' => ['RIGHT_PANEL' => true],
    ], $token);
}

$model = req('GET', "/api/v1/furniture/instances/$id/3d-model", null, $token)['data'];
assertTrue((float) ($model['back_thickness'] ?? 0) === 18.0, '3d reports back_thickness 18');
$right = null;
$left = null;
foreach ($model['meshes'] as $m) {
    if (($m['component_role'] ?? '') === 'RIGHT_PANEL') {
        $right = $m;
    }
    if (($m['component_role'] ?? '') === 'LEFT_PANEL') {
        $left = $m;
    }
}
assertTrue($right && !empty($right['expo']) && !empty($right['face_finishes']), '3d right has face_finishes');
assertTrue($left && empty($left['expo']), '3d left non-expo');
if ($extId && $intId && $extId !== $intId) {
    assertTrue(
        ($right['face_finishes']['exterior']['id'] ?? null) == $extId,
        '3d expo face uses exterior laminate'
    );
}

$job = req('POST', "/api/v1/projects/$proj/manufacturing", [
    'furniture_ids' => [$id],
], $token)['data'];
$pkgId = (int) ($job['furniture'][0]['manufacturing_package_id'] ?? 0);
$cut = req('GET', "/api/v1/manufacturing/$pkgId/cutlist", null, $token)['data'];
assertTrue(in_array('face_exterior_finish', $cut['columns'] ?? [], true), 'cutlist has face columns');
$csv = req('POST', "/api/v1/manufacturing/$pkgId/cutlist/export", [], $token)['data'];
assertTrue(str_contains((string) ($csv['content'] ?? ''), 'FACE_EXT'), 'csv has FACE_EXT');

// Reload persistence
$reload = req('GET', "/api/v1/furniture/instances/$id", null, $token)['data'];
assertTrue((float) $reload['parameters']['back_thickness'] === 18.0, 'reload thickness');
assertTrue((int) $reload['parameters']['back_material_id'] === (int) $board18['id'], 'reload material');

echo "Back panel / face-finish tests done\n";
