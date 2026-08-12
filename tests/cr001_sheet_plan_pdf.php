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

$token = req('POST', '/api/v1/auth/login', [
    'email' => 'owner@demo.fmos',
    'password' => 'Password123!',
])['data']['token'];

$org = (int) req('GET', '/api/v1/organizations', null, $token)['data'][0]['id'];
$client = (int) req('POST', '/api/v1/clients', ['name' => 'SheetPlan ' . time()], $token)['data']['id'];
$proj = (int) req('POST', '/api/v1/projects', [
    'organization_id' => $org,
    'client_id' => $client,
    'name' => 'Sheet Plan Proj',
], $token)['data']['id'];

$mats = req('GET', '/api/v1/materials?category=LAMINATE', null, $token)['data'];
$ext = (int) $mats[0]['id'];
$int = (int) ($mats[5]['id'] ?? $mats[1]['id']);

$kitchen = req('POST', "/api/v1/projects/$proj/furniture", [
    'template_code' => 'KITCHEN_BASE',
    'name' => 'Kitchen Base Unit',
], $token)['data'];
$wardrobe = req('POST', "/api/v1/projects/$proj/furniture", [
    'template_code' => 'WARDROBE',
    'name' => 'Wardrobe',
    'parameters' => ['width' => 1800, 'height' => 2100, 'depth' => 600],
], $token)['data'];

req('PUT', "/api/v1/furniture/instances/{$kitchen['id']}/customize", [
    'exterior_finish_id' => $ext,
    'interior_finish_id' => $ext,
], $token);
req('PUT', "/api/v1/furniture/instances/{$wardrobe['id']}/customize", [
    'exterior_finish_id' => $int,
    'interior_finish_id' => $int,
], $token);

$job = req('POST', "/api/v1/projects/$proj/manufacturing", [
    'furniture_ids' => [(int) $kitchen['id'], (int) $wardrobe['id']],
], $token)['data'];
$pkgIds = array_map(static fn ($f) => (int) $f['manufacturing_package_id'], $job['furniture']);
echo 'packages=' . implode(',', $pkgIds) . "\n";

$plan = req('POST', "/api/v1/projects/$proj/nesting/sheet-plan", [
    'package_ids' => $pkgIds,
], $token)['data'];
echo 'groups=' . $plan['totals']['laminate_groups'] . ' sheets=' . $plan['totals']['sheets'] . ' pieces=' . $plan['totals']['panel_pieces'] . "\n";
if (($plan['totals']['laminate_groups'] ?? 0) < 1) {
    throw new RuntimeException('expected laminate groups');
}
if (($plan['totals']['packages'] ?? 0) < 2) {
    throw new RuntimeException('expected both packages');
}

$pdf = req('POST', "/api/v1/projects/$proj/nesting/sheet-plan/pdf", [
    'package_ids' => $pkgIds,
], $token)['data'];
if (empty($pdf['content_base64']) || empty($pdf['filename'])) {
    throw new RuntimeException('pdf missing');
}
$bin = base64_decode($pdf['content_base64'], true);
if ($bin === false || !str_starts_with($bin, '%PDF')) {
    throw new RuntimeException('invalid pdf bytes');
}
echo 'pdf=' . $pdf['filename'] . ' sheets=' . $pdf['sheet_count'] . ' bytes=' . strlen($bin) . "\n";
echo "Project sheet plan PDF checks passed\n";
