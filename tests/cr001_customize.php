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

function ok(string $msg): void
{
    echo "  OK  $msg\n";
}

$token = req('POST', '/api/v1/auth/login', [
    'email' => 'owner@demo.fmos',
    'password' => 'Password123!',
])['data']['token'];
$org = (int) req('GET', '/api/v1/organizations', null, $token)['data'][0]['id'];
$client = (int) req('POST', '/api/v1/clients', ['name' => 'Cust ' . time()], $token)['data']['id'];
$proj = (int) req('POST', "/api/v1/projects", [
    'organization_id' => $org,
    'client_id' => $client,
    'name' => 'Cust Proj',
], $token)['data']['id'];

$specs = [
    'TV_UNIT' => ['width' => 2000, 'height' => 650, 'depth' => 480, 'door_type' => 'HINGED'],
    'WARDROBE' => ['width' => 2100, 'height' => 2200, 'depth' => 600, 'door_type' => 'HINGED'],
    'KITCHEN_BASE' => ['width' => 900, 'height' => 720, 'depth' => 560, 'door_type' => 'HINGED'],
    'CHEST_DRAWERS' => ['width' => 1000, 'height' => 900, 'depth' => 450, 'door_type' => 'NONE'],
    'BOOKCASE' => ['width' => 1200, 'height' => 1800, 'depth' => 350, 'door_type' => 'NONE'],
];
foreach ($specs as $code => $dims) {
    $created = req('POST', "/api/v1/projects/$proj/furniture", [
        'template_code' => $code,
        'name' => $code . ' item',
        'parameters' => $dims,
    ], $token)['data'];
    $id = (int) $created['id'];

    $newW = (float) $dims['width'] + 100;
    $out = req('PUT', "/api/v1/furniture/instances/$id/customize", [
        'name' => $code . ' Custom',
        'parameters' => [
            'width' => $newW,
            'height' => $dims['height'],
            'depth' => $dims['depth'],
            'door_type' => $dims['door_type'],
        ],
        'layout' => [
            'plinth_height_mm' => 80,
            'partition_thickness_mm' => 18,
            'door_type' => $dims['door_type'],
            'loft' => ['enabled' => false, 'height_mm' => 0, 'shelf_count' => 0],
            'bays' => [[
                'id' => 'b1',
                'label' => 'Main',
                'width_mm' => null,
                'sections' => [[
                    'type' => 'SHELVES',
                    'shelf_count' => 3,
                    'height_mm' => null,
                    'label' => 'Shelves',
                ]],
            ]],
        ],
    ], $token)['data'];

    if (($out['name'] ?? '') !== $code . ' Custom') {
        throw new RuntimeException("$code name mismatch");
    }
    if ((float) ($out['width_mm'] ?? 0) !== $newW) {
        throw new RuntimeException("$code width mismatch");
    }
    $bays = $out['parameters']['layout']['bays'] ?? [];
    if (count($bays) !== 1) {
        throw new RuntimeException("$code bay count");
    }
    ok("$code customize + layout");
}

// layout-only route still works
$one = req('POST', "/api/v1/projects/$proj/furniture", [
    'template_code' => 'WARDROBE_SLIDING',
    'name' => 'Sliding',
], $token)['data'];
$layoutOnly = req('PUT', "/api/v1/furniture/instances/{$one['id']}/layout", [
    'layout' => [
        'plinth_height_mm' => 100,
        'partition_thickness_mm' => 18,
        'door_type' => 'SLIDING',
        'loft' => ['enabled' => true, 'height_mm' => 450, 'shelf_count' => 1],
        'bays' => [
            ['id' => 'L', 'label' => 'Left', 'width_mm' => null, 'sections' => [['type' => 'HANGING', 'height_mm' => null, 'label' => 'Hang']]],
            ['id' => 'R', 'label' => 'Right', 'width_mm' => null, 'sections' => [['type' => 'DRAWERS', 'drawer_count' => 4, 'drawer_height_mm' => 150, 'height_mm' => null, 'label' => 'Drawers']]],
        ],
    ],
], $token)['data'];
if (count($layoutOnly['parameters']['layout']['bays'] ?? []) !== 2) {
    throw new RuntimeException('layout route bays');
}
ok('layout-only route still works');

echo "Per-furniture customize checks passed\n";
