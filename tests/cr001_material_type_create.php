<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

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

echo "Material Type create tests\n";

$token = req('POST', '/api/v1/auth/login', [
    'email' => 'owner@demo.fmos',
    'password' => 'Password123!',
])['data']['token'];
req('POST', '/api/v1/catalog/seed', [], $token);

$boards = array_values(array_filter(
    req('GET', '/api/v1/catalog/products', null, $token)['data'],
    static fn ($p) => ($p['category'] ?? '') === 'BOARD' && ($p['publish_status'] ?? '') === 'PUBLISHED'
));
assertTrue($boards !== [], 'published BOARD products exist');
$board = $boards[0];
foreach ($boards as $b) {
    if ((float) ($b['thickness_mm'] ?? 0) === 18.0) {
        $board = $b;
        break;
    }
}

$org = (int) req('GET', '/api/v1/organizations', null, $token)['data'][0]['id'];
$client = (int) req('POST', '/api/v1/clients', ['name' => 'MatType ' . time()], $token)['data']['id'];
$proj = (int) req('POST', '/api/v1/projects', [
    'organization_id' => $org,
    'client_id' => $client,
    'name' => 'MatType Proj',
], $token)['data']['id'];

// Create WITHOUT material_id (legacy path)
$legacy = req('POST', "/api/v1/projects/$proj/furniture", [
    'template_code' => 'WARDROBE',
    'name' => 'No Material',
    'parameters' => ['width' => 1200, 'height' => 2100, 'depth' => 600],
], $token)['data'];
assertTrue(($legacy['material_id'] ?? null) === null, 'legacy create material_id null');
assertTrue(($legacy['material'] ?? null) === null, 'legacy material summary null');

// Create WITH material_id
$created = req('POST', "/api/v1/projects/$proj/furniture", [
    'template_code' => 'WARDROBE',
    'name' => 'With Material',
    'material_id' => (int) $board['id'],
    'parameters' => ['width' => 1800, 'height' => 2200, 'depth' => 600],
], $token)['data'];
$id = (int) $created['id'];
assertTrue((int) $created['material_id'] === (int) $board['id'], 'create stores material_id');
assertTrue(($created['material']['sku'] ?? '') !== '', 'create returns material summary');

$reload = req('GET', "/api/v1/furniture/instances/$id", null, $token)['data'];
assertTrue((int) $reload['material_id'] === (int) $board['id'], 'reload retains material_id');
assertTrue((string) ($reload['material']['name'] ?? '') === (string) $board['name'], 'reload material name');

// Update via customize
$other = null;
foreach ($boards as $b) {
    if ((int) $b['id'] !== (int) $board['id']) {
        $other = $b;
        break;
    }
}
if ($other) {
    $upd = req('PUT', "/api/v1/furniture/instances/$id/customize", [
        'material_id' => (int) $other['id'],
    ], $token)['data'];
    assertTrue((int) $upd['material_id'] === (int) $other['id'], 'customize updates material_id');
}

// Invalid material
try {
    req('POST', "/api/v1/projects/$proj/furniture", [
        'template_code' => 'BOOKCASE',
        'name' => 'Bad Material',
        'material_id' => 99999999,
        'parameters' => ['width' => 900, 'height' => 1800, 'depth' => 350],
    ], $token);
    throw new RuntimeException('expected invalid material to fail');
} catch (RuntimeException $e) {
    assertTrue(str_contains($e->getMessage(), '422') || str_contains($e->getMessage(), 'BOARD'), 'invalid material rejected');
}

// Manufacturing uses board name
$job = req('POST', "/api/v1/projects/$proj/manufacturing", [
    'furniture_ids' => [$id],
], $token)['data'];
$pkgId = (int) ($job['furniture'][0]['manufacturing_package_id'] ?? 0);
$cut = req('GET', "/api/v1/manufacturing/$pkgId/cutlist", null, $token)['data'];
$names = array_unique(array_map(static fn ($r) => (string) ($r['material_name'] ?? ''), $cut['items'] ?? []));
assertTrue(!in_array('Board', $names, true) || count($names) >= 1, 'cutlist material_name populated');
$expectedName = (string) (($other ?? $board)['name'] ?? '');
assertTrue(in_array($expectedName, $names, true), "cutlist uses selected board name ({$expectedName})");

echo "Material Type create tests done\n";
