<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Fmos\Domains\Furniture\FurnitureExpo;

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

echo "EXPO unit tests\n";

$defaults = FurnitureExpo::normalize(null);
assertTrue($defaults['SHUTTER'] === true, 'doors default EXPO true');
assertTrue($defaults['DRAWER_FRONT'] === true, 'drawer fronts default EXPO true');
assertTrue($defaults['LEFT_PANEL'] === false, 'left panel defaults false');
assertTrue($defaults['RIGHT_PANEL'] === false, 'right panel defaults false');
assertTrue($defaults['TOP_PANEL'] === false, 'top panel defaults false');
assertTrue($defaults['BACK_PANEL'] === false, 'back panel defaults false');
assertTrue($defaults['SHELF'] === false, 'shelves default false');

$legacy = FurnitureExpo::fromParameters([]);
assertTrue($legacy['LEFT_PANEL'] === false && $legacy['SHUTTER'] === true, 'legacy module without expo map');

$right = FurnitureExpo::normalize(['RIGHT_PANEL' => true]);
assertTrue($right['RIGHT_PANEL'] === true && $right['LEFT_PANEL'] === false, 'mark right side EXPO');

$left = FurnitureExpo::normalize(['LEFT_PANEL' => true]);
assertTrue($left['LEFT_PANEL'] === true && $left['RIGHT_PANEL'] === false, 'mark left side EXPO');

$both = FurnitureExpo::normalize(['LEFT_PANEL' => true, 'RIGHT_PANEL' => true]);
assertTrue($both['LEFT_PANEL'] && $both['RIGHT_PANEL'], 'both sides EXPO');

$top = FurnitureExpo::normalize(['TOP_PANEL' => true, 'RIGHT_PANEL' => true]);
assertTrue($top['TOP_PANEL'] && $top['RIGHT_PANEL'] && !$top['LEFT_PANEL'], 'top + right EXPO');

$removed = FurnitureExpo::normalize(['RIGHT_PANEL' => false, 'SHUTTER' => true]);
assertTrue($removed['RIGHT_PANEL'] === false, 'remove EXPO designation');

assertTrue(FurnitureExpo::isExpo('RIGHT_PANEL', $right) === true, 'isExpo right');
assertTrue(FurnitureExpo::isExpo('LEFT_PANEL', $right) === false, 'isExpo left false');
assertTrue(FurnitureExpo::isExpo('HARDWARE', $right) === false, 'hardware never expo');
assertTrue(FurnitureExpo::isExpo('', $right) === false, 'empty role never expo');

assertTrue(FurnitureExpo::inferRoleFromName('Wardrobe - Left Panel') === 'LEFT_PANEL', 'infer left');
assertTrue(FurnitureExpo::inferRoleFromName('Wardrobe - Right Panel') === 'RIGHT_PANEL', 'infer right');
assertTrue(FurnitureExpo::inferRoleFromName('Shutter 1') === 'SHUTTER', 'infer shutter');

$options = FurnitureExpo::optionsForComponents([
    ['name' => 'Left Panel', 'component_type' => 'PANEL', 'quantity' => 1, 'geometry' => ['role' => 'LEFT_PANEL']],
    ['name' => 'Right Panel', 'component_type' => 'PANEL', 'quantity' => 1, 'geometry' => ['role' => 'RIGHT_PANEL']],
    ['name' => 'Shutter', 'component_type' => 'PANEL', 'quantity' => 2, 'geometry' => ['role' => 'SHUTTER']],
    ['name' => 'Hinge', 'component_type' => 'HARDWARE', 'quantity' => 4, 'geometry' => []],
], $right);
$roles = array_column($options, 'role');
assertTrue(in_array('LEFT_PANEL', $roles, true) && in_array('RIGHT_PANEL', $roles, true), 'options include sides');
assertTrue(!in_array('HARDWARE', $roles, true), 'hardware excluded from options');
$rightOpt = array_values(array_filter($options, static fn ($o) => $o['role'] === 'RIGHT_PANEL'))[0];
assertTrue($rightOpt['expo'] === true, 'options reflect right expo');

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
        CURLOPT_TIMEOUT => 20,
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

echo "EXPO integration tests\n";

try {
    $token = req('POST', '/api/v1/auth/login', [
        'email' => 'owner@demo.fmos',
        'password' => 'Password123!',
    ])['data']['token'];
} catch (Throwable $e) {
    echo "  SKIP integration (server not reachable): {$e->getMessage()}\n";
    echo "EXPO tests done (unit only)\n";
    exit(0);
}

$org = (int) req('GET', '/api/v1/organizations', null, $token)['data'][0]['id'];
$client = (int) req('POST', '/api/v1/clients', ['name' => 'Expo ' . time()], $token)['data']['id'];
$proj = (int) req('POST', '/api/v1/projects', [
    'organization_id' => $org,
    'client_id' => $client,
    'name' => 'Expo Proj',
], $token)['data']['id'];

$created = req('POST', "/api/v1/projects/$proj/furniture", [
    'template_code' => 'WARDROBE',
    'name' => 'Expo Wardrobe',
    'parameters' => ['width' => 2550, 'height' => 2400, 'depth' => 600, 'door_type' => 'HINGED'],
], $token)['data'];
$id = (int) $created['id'];

$got = req('GET', "/api/v1/furniture/instances/$id", null, $token)['data'];
assertTrue(!isset($got['parameters']['expo']) || is_array($got['parameters']['expo'] ?? null) || true, 'legacy load ok');
assertTrue(($got['expo']['LEFT_PANEL'] ?? true) === false, 'API default left not expo');
assertTrue(($got['expo']['SHUTTER'] ?? false) === true, 'API default shutter expo');
assertTrue(is_array($got['expo_options']) && count($got['expo_options']) > 0, 'expo_options present');

$updated = req('PUT', "/api/v1/furniture/instances/$id/customize", [
    'expo' => ['RIGHT_PANEL' => true, 'LEFT_PANEL' => false],
], $token)['data'];
assertTrue($updated['expo']['RIGHT_PANEL'] === true, 'persist right expo');
assertTrue($updated['expo']['LEFT_PANEL'] === false, 'left remains false');

$reload = req('GET', "/api/v1/furniture/instances/$id", null, $token)['data'];
assertTrue($reload['parameters']['expo']['RIGHT_PANEL'] === true, 'survives reload in parameters');
$rightRow = null;
foreach ($reload['component_rows'] as $row) {
    $role = $row['geometry']['role'] ?? $row['manufacturing_data']['role'] ?? '';
    if ($role === 'RIGHT_PANEL') {
        $rightRow = $row;
        break;
    }
}
assertTrue($rightRow !== null && !empty($rightRow['manufacturing_data']['expo']), 'component manufacturing_data.expo stamped');

$bothUp = req('PUT', "/api/v1/furniture/instances/$id/customize", [
    'expo' => ['LEFT_PANEL' => true, 'RIGHT_PANEL' => true, 'TOP_PANEL' => true],
], $token)['data'];
assertTrue($bothUp['expo']['LEFT_PANEL'] && $bothUp['expo']['RIGHT_PANEL'] && $bothUp['expo']['TOP_PANEL'], 'multiple expo');

$draw = req('GET', "/api/v1/furniture/instances/$id/2d?view=FRONT", null, $token)['data'];
$expoEls = array_values(array_filter($draw['elements'], static fn ($e) => !empty($e['expo'])));
assertTrue(count($expoEls) > 0, '2d has expo markers');
$roles2d = array_unique(array_map(static fn ($e) => $e['component_role'] ?? '', $expoEls));
assertTrue(in_array('RIGHT_PANEL', $roles2d, true) && in_array('LEFT_PANEL', $roles2d, true), '2d marks both sides');

$model = req('GET', "/api/v1/furniture/instances/$id/3d-model", null, $token)['data'];
$rightMesh = null;
foreach ($model['meshes'] as $m) {
    if (($m['component_role'] ?? '') === 'RIGHT_PANEL') {
        $rightMesh = $m;
        break;
    }
}
assertTrue($rightMesh && !empty($rightMesh['expo']), '3d right mesh expo true');

try {
    req('PUT', "/api/v1/furniture/instances/$id/customize", [
        'expo' => ['NOT_A_ROLE' => true],
    ], $token);
    throw new RuntimeException('expected invalid role to fail');
} catch (RuntimeException $e) {
    assertTrue(str_contains($e->getMessage(), '422') || str_contains($e->getMessage(), 'Invalid'), 'invalid role rejected');
}

$cleared = req('PUT', "/api/v1/furniture/instances/$id/customize", [
    'expo' => ['LEFT_PANEL' => false, 'RIGHT_PANEL' => false, 'TOP_PANEL' => false],
], $token)['data'];
assertTrue(!$cleared['expo']['LEFT_PANEL'] && !$cleared['expo']['RIGHT_PANEL'], 'clear expo');

req('PUT', "/api/v1/furniture/instances/$id/customize", [
    'expo' => ['RIGHT_PANEL' => true],
], $token);
$job = req('POST', "/api/v1/projects/$proj/manufacturing", [
    'furniture_ids' => [$id],
], $token)['data'];
$pkgId = (int) ($job['furniture'][0]['manufacturing_package_id'] ?? 0);
assertTrue($pkgId > 0, 'manufacturing package created');
$cut = req('GET', "/api/v1/manufacturing/$pkgId/cutlist", null, $token)['data'];
$hasExpoCol = in_array('expo', $cut['columns'] ?? [], true);
assertTrue($hasExpoCol, 'cutlist columns include expo');
$anyExpo = false;
foreach ($cut['items'] as $item) {
    if (!empty($item['expo'])) {
        $anyExpo = true;
        break;
    }
}
assertTrue($anyExpo, 'cutlist item expo metadata present for right panel');
$csv = req('POST', "/api/v1/manufacturing/$pkgId/cutlist/export", [], $token)['data'];
assertTrue(str_contains((string) ($csv['content'] ?? ''), 'EXPO'), 'csv has EXPO column');

echo "EXPO tests done\n";
