<?php

declare(strict_types=1);

/**
 * CR-001 furniture-centric E2E (Phases 5-15 vertical slice)
 * Run: FMOS_BASE_URL=http://127.0.0.1:8088 php tests/e2e_furniture_centric.php
 */

$base = getenv('FMOS_BASE_URL') ?: 'http://127.0.0.1:8088';

function req(string $method, string $path, ?array $body = null, ?string $token = null): array
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
    ]);
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $json = json_decode($raw ?: 'null', true);
    if ($code >= 400 || !($json['success'] ?? false)) {
        throw new RuntimeException("{$method} {$path} failed ({$code}): " . ($raw ?: 'empty'));
    }
    return $json;
}

function step(string $label, callable $fn): void
{
    echo "[ ] {$label}... ";
    $fn();
    echo "OK\n";
}

try {
    $token = null;
    $orgId = $clientId = $projectId = $furnitureId = $pkgId = $nestId = null;

    step('Login', function () use (&$token) {
        $res = req('POST', '/api/v1/auth/login', ['email' => 'owner@demo.fmos', 'password' => 'Password123!']);
        $token = $res['data']['token'];
    });
    step('Org+client+project', function () use ($token, &$orgId, &$clientId, &$projectId) {
        $orgId = (int) req('GET', '/api/v1/organizations', null, $token)['data'][0]['id'];
        $clientId = (int) req('POST', '/api/v1/clients', ['name' => 'CR E2E Client'], $token)['data']['id'];
        $project = req('POST', '/api/v1/projects', [
            'organization_id' => $orgId,
            'client_id' => $clientId,
            'name' => 'CR Furniture-Centric E2E',
        ], $token)['data'];
        $projectId = (int) $project['id'];
        assert(($project['model_mode'] ?? '') === 'FURNITURE_FIRST');
    });
    step('Create furniture without room + finish', function () use ($token, $projectId, &$furnitureId) {
        $mats = req('GET', '/api/v1/materials?category=LAMINATE', null, $token)['data'];
        assert(count($mats) >= 1);
        $f = req('POST', '/api/v1/projects/' . $projectId . '/furniture', [
            'template_code' => 'WARDROBE',
            'name' => 'E2E Sliding Wardrobe',
            'parameters' => ['width' => 2100, 'height' => 2400, 'depth' => 600, 'shelf_count' => 2, 'shutter_count' => 2],
            'exterior_finish_id' => (int) $mats[0]['id'],
            'interior_finish_id' => (int) ($mats[1]['id'] ?? $mats[0]['id']),
        ], $token)['data'];
        $furnitureId = (int) $f['id'];
        assert($f['room_id'] === null);
        assert(count($f['component_rows']) > 0);
    });
    step('2D FRONT dims follow width', function () use ($token, $furnitureId) {
        $d = req('GET', '/api/v1/furniture/instances/' . $furnitureId . '/2d?view=FRONT', null, $token)['data'];
        assert((float) $d['bounds']['width'] === 2100.0);
        assert(count($d['dimensions']) >= 2);
        req('PUT', '/api/v1/furniture/instances/' . $furnitureId . '/parameters', ['parameters' => ['width' => 2000]], $token);
        $d2 = req('GET', '/api/v1/furniture/instances/' . $furnitureId . '/2d?view=FRONT', null, $token)['data'];
        assert((float) $d2['bounds']['width'] === 2000.0);
    });
    step('3D model textured meshes', function () use ($token, $furnitureId) {
        $m = req('GET', '/api/v1/furniture/instances/' . $furnitureId . '/3d-model', null, $token)['data'];
        assert(count($m['meshes']) >= 5);
        $hasTex = false;
        foreach ($m['meshes'] as $mesh) {
            if (!empty($mesh['finish']['texture_url'])) {
                $hasTex = true;
                break;
            }
        }
        assert($hasTex);
    });
    step('Validate + multi manufacturing job', function () use ($token, $projectId, $furnitureId, &$pkgId) {
        $v = req('POST', '/api/v1/furniture/instances/' . $furnitureId . '/validate', [], $token)['data'];
        assert($v['ok'] === true);
        $job = req('POST', '/api/v1/projects/' . $projectId . '/manufacturing', [
            'furniture_ids' => [$furnitureId],
        ], $token)['data'];
        assert(($job['status'] ?? '') === 'READY');
        $pkgId = (int) $job['furniture'][0]['manufacturing_package_id'];
    });
    step('Panels finishing vs cutting + cutlist', function () use ($token, $pkgId) {
        $pkg = req('GET', '/api/v1/manufacturing/' . $pkgId, null, $token)['data'];
        assert(count($pkg['panels']) > 0);
        $p = $pkg['panels'][0];
        assert(isset($p['finishing_length_mm'], $p['cutting_length_mm']));
        assert((float) $p['cutting_length_mm'] <= (float) $p['finishing_length_mm']);
        assert(!empty($pkg['bom_revision_id']));
        $cut = req('GET', '/api/v1/manufacturing/' . $pkgId . '/cutlist', null, $token)['data'];
        assert(count($cut['items']) > 0);
        assert(isset($cut['items'][0]['finishing_length_mm']));
    });
    step('Unified commercial from BOM', function () use ($token, $projectId, $furnitureId) {
        $c = req('POST', '/api/v1/commercial/generate', [
            'project_id' => $projectId,
            'furniture_id' => $furnitureId,
        ], $token)['data'];
        assert(($c['breakdown']['source'] ?? '') === 'unified_bom');
        assert($c['breakdown']['final_price'] > 0);
    });
    step('Nest visual + lock + reoptimize', function () use ($token, $pkgId, &$nestId) {
        req('POST', '/api/v1/manufacturing/' . $pkgId . '/release', [], $token);
        $nest = req('POST', '/api/v1/manufacturing/' . $pkgId . '/nest', [], $token)['data'];
        $nestId = (int) $nest['id'];
        assert($nest['sheet_count'] >= 1);
        assert(!empty($nest['layout']['sheets'][0]['placements']));
        $p = $nest['layout']['sheets'][0]['placements'][0];
        $updated = req('PUT', '/api/v1/nesting/' . $nestId . '/placement', [
            'panel_id' => $p['panel_id'],
            'instance' => $p['instance'] ?? 0,
            'sheet_index' => 0,
            'x' => $p['x'],
            'y' => $p['y'],
            'length_mm' => $p['length_mm'],
            'width_mm' => $p['width_mm'],
            'locked' => true,
        ], $token)['data'];
        assert(($updated['status'] ?? '') === 'MANUAL');
        $re = req('POST', '/api/v1/nesting/' . $nestId . '/reoptimize', [], $token)['data'];
        assert($re['sheet_count'] >= 1);
    });
    step('Exports', function () use ($token, $pkgId, $furnitureId) {
        $csv = req('POST', '/api/v1/manufacturing/' . $pkgId . '/cutlist/export', [], $token)['data'];
        assert(str_contains($csv['content'], 'FINISH_L'));
        $html = req('POST', '/api/v1/furniture/instances/' . $furnitureId . '/export/design', ['view' => 'FRONT'], $token)['data'];
        assert(str_contains($html['content'], '<svg'));
    });

    echo "CR-001 furniture-centric E2E passed\n";
} catch (Throwable $e) {
    fwrite(STDERR, "FAILED: " . $e->getMessage() . "\n");
    exit(1);
}
