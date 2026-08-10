<?php

declare(strict_types=1);

/**
 * API smoke checklist for CR-001 + MVP surface.
 * Run with server up: FMOS_BASE_URL=http://127.0.0.1:8088 php tests/smoke_api.php
 */

$base = rtrim(getenv('FMOS_BASE_URL') ?: 'http://127.0.0.1:8088', '/');
$failures = 0;

function hit(string $method, string $path, ?array $body = null, ?string $token = null): array
{
    global $base;
    $ch = curl_init($base . $path);
    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $body !== null ? json_encode($body) : null,
        CURLOPT_TIMEOUT => 30,
    ]);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return ['code' => $code, 'raw' => $raw, 'json' => json_decode($raw ?: 'null', true), 'err' => $err];
}

function ok(string $label, bool $cond, string $detail = ''): void
{
    global $failures;
    if ($cond) {
        echo "  PASS  {$label}\n";
        return;
    }
    $failures++;
    echo "  FAIL  {$label}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
}

echo "Smoke against {$base}\n";

$h = hit('GET', '/api/v1/health');
ok('health', $h['code'] === 200 && ($h['json']['success'] ?? false));

$login = hit('POST', '/api/v1/auth/login', ['email' => 'owner@demo.fmos', 'password' => 'Password123!']);
$token = $login['json']['data']['token'] ?? null;
ok('login', $login['code'] === 200 && is_string($token));

$me = hit('GET', '/api/v1/auth/me', null, $token);
ok('auth me', $me['code'] === 200 && !empty($me['json']['data']['email']));

$orgs = hit('GET', '/api/v1/organizations', null, $token);
ok('organizations', $orgs['code'] === 200 && count($orgs['json']['data'] ?? []) >= 1);

$mats = hit('GET', '/api/v1/materials?category=LAMINATE', null, $token);
ok('laminates >= 45', $mats['code'] === 200 && count($mats['json']['data'] ?? []) >= 45);

$texUrl = $mats['json']['data'][0]['assets'][0]['public_url'] ?? null;
if ($texUrl) {
    $ch = curl_init($base . $texUrl);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_NOBODY => true, CURLOPT_TIMEOUT => 15]);
    curl_exec($ch);
    $texCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    ok('laminate texture HTTP', $texCode === 200, "url={$texUrl} code={$texCode}");
} else {
    ok('laminate texture HTTP', false, 'no asset url');
}

foreach (['/', '/assets/js/app.js', '/assets/js/furniture.js', '/assets/css/app.css'] as $static) {
    $ch = curl_init($base . $static);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_NOBODY => true]);
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    ok("static {$static}", $code === 200, "code={$code}");
}

$clients = hit('GET', '/api/v1/clients', null, $token);
ok('clients list', $clients['code'] === 200);

$projects = hit('GET', '/api/v1/projects', null, $token);
ok('projects list', $projects['code'] === 200);

$tpl = hit('GET', '/api/v1/furniture/templates', null, $token);
ok('furniture templates', $tpl['code'] === 200 && count($tpl['json']['data'] ?? []) >= 1);

// Mini integration: create project → furniture → 2d/3d → validate
$orgId = (int) $orgs['json']['data'][0]['id'];
$client = hit('POST', '/api/v1/clients', ['name' => 'Smoke Client ' . time()], $token);
$clientId = (int) ($client['json']['data']['id'] ?? 0);
ok('create client', $client['code'] === 201 && $clientId > 0);

$project = hit('POST', '/api/v1/projects', [
    'organization_id' => $orgId,
    'client_id' => $clientId,
    'name' => 'Smoke Project ' . time(),
], $token);
$projectId = (int) ($project['json']['data']['id'] ?? 0);
ok('create project FURNITURE_FIRST', $project['code'] === 201 && ($project['json']['data']['model_mode'] ?? '') === 'FURNITURE_FIRST');

$furn = hit('POST', "/api/v1/projects/{$projectId}/furniture", [
    'template_code' => 'WARDROBE',
    'name' => 'Smoke Wardrobe',
    'parameters' => ['width' => 2000, 'height' => 2400, 'depth' => 600],
    'exterior_finish_id' => (int) ($mats['json']['data'][0]['id'] ?? 0),
], $token);
$furnitureId = (int) ($furn['json']['data']['id'] ?? 0);
ok('create furniture no room', in_array($furn['code'], [200, 201], true) && $furnitureId > 0 && ($furn['json']['data']['room_id'] ?? null) === null, 'code=' . $furn['code'] . ' room=' . json_encode($furn['json']['data']['room_id'] ?? null));
ok('components dual-write', count($furn['json']['data']['component_rows'] ?? []) > 0);

$d2 = hit('GET', "/api/v1/furniture/instances/{$furnitureId}/2d?view=FRONT", null, $token);
ok('2d drawing', $d2['code'] === 200 && ($d2['json']['data']['bounds']['width'] ?? 0) == 2000);

$d3 = hit('GET', "/api/v1/furniture/instances/{$furnitureId}/3d-model", null, $token);
ok('3d model', $d3['code'] === 200 && count($d3['json']['data']['meshes'] ?? []) >= 5);

$val = hit('POST', "/api/v1/furniture/instances/{$furnitureId}/validate", [], $token);
ok('validate furniture', $val['code'] === 200 && ($val['json']['data']['ok'] ?? false) === true);

$job = hit('POST', "/api/v1/projects/{$projectId}/manufacturing", ['furniture_ids' => [$furnitureId]], $token);
$pkgId = (int) ($job['json']['data']['furniture'][0]['manufacturing_package_id'] ?? 0);
ok('manufacturing job', $job['code'] === 201 && $pkgId > 0 && ($job['json']['data']['status'] ?? '') === 'READY');

$pkg = hit('GET', "/api/v1/manufacturing/{$pkgId}", null, $token);
ok('package panels+cutlist', $pkg['code'] === 200 && count($pkg['json']['data']['panels'] ?? []) > 0 && count($pkg['json']['data']['cutlist'] ?? []) > 0);
$panel = $pkg['json']['data']['panels'][0] ?? [];
ok('finishing/cutting fields', isset($panel['finishing_length_mm'], $panel['cutting_length_mm']));

$cut = hit('GET', "/api/v1/manufacturing/{$pkgId}/cutlist", null, $token);
ok('cutlist endpoint', $cut['code'] === 200 && count($cut['json']['data']['items'] ?? []) > 0);

$rel = hit('POST', "/api/v1/manufacturing/{$pkgId}/release", [], $token);
ok('release package', $rel['code'] === 200 && ($rel['json']['data']['status'] ?? '') === 'RELEASED');

$nest = hit('POST', "/api/v1/manufacturing/{$pkgId}/nest", [], $token);
ok('nesting', $nest['code'] === 201 && ($nest['json']['data']['sheet_count'] ?? 0) >= 1);

$csv = hit('POST', "/api/v1/manufacturing/{$pkgId}/cutlist/export", [], $token);
ok('cutlist csv export', $csv['code'] === 200 && str_contains((string) ($csv['json']['data']['content'] ?? ''), 'FINISH_L'));

$html = hit('POST', "/api/v1/furniture/instances/{$furnitureId}/export/design", ['view' => 'FRONT'], $token);
ok('design html export', $html['code'] === 200 && str_contains((string) ($html['json']['data']['content'] ?? ''), '<svg'));

$commercial = hit('POST', '/api/v1/commercial/generate', [
    'project_id' => $projectId,
    'furniture_id' => $furnitureId,
], $token);
ok('unified commercial BOM', $commercial['code'] === 201 && ($commercial['json']['data']['breakdown']['source'] ?? '') === 'unified_bom');

// Floor designer regression path
$detail = hit('GET', "/api/v1/projects/{$projectId}", null, $token);
$roomId = (int) ($detail['json']['data']['buildings'][0]['floors'][0]['rooms'][0]['id'] ?? 0);
$wall = hit('POST', '/api/v1/design/objects', [
    'project_id' => $projectId,
    'room_id' => $roomId,
    'object_type' => 'WALL',
    'geometry' => ['x1' => 0, 'y1' => 0, 'x2' => 3000, 'y2' => 0, 'thickness' => 100, 'height' => 3000],
], $token);
ok('floor wall still works', $wall['code'] === 201 && $roomId > 0);

echo $failures === 0 ? "\nSMOKE PASSED ({$base})\n" : "\nSMOKE FAILED: {$failures} check(s)\n";
exit($failures === 0 ? 0 : 1);
