<?php

declare(strict_types=1);

/**
 * FMOS MVP End-to-End test (Phases 1-10 journey)
 * Run: php tests/e2e_mvp.php
 */

$base = getenv('FMOS_BASE_URL') ?: 'http://127.0.0.1:8080';

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

$failures = 0;
try {
    step('Health', function () {
        $h = req('GET', '/api/v1/health');
        assert(($h['data']['status'] ?? '') === 'ok');
    });

    $token = null;
    step('Login', function () use (&$token) {
        $res = req('POST', '/api/v1/auth/login', [
            'email' => 'owner@demo.fmos',
            'password' => 'Password123!',
        ]);
        $token = $res['data']['token'];
        assert(in_array('TENANT_OWNER', $res['data']['user']['roles'], true));
    });

    $orgId = null;
    $clientId = null;
    $projectId = null;
    $roomId = null;
    $furnitureId = null;
    $pricingId = null;
    $mfgId = null;

    step('List organizations', function () use ($token, &$orgId) {
        $res = req('GET', '/api/v1/organizations', null, $token);
        $orgId = (int) $res['data'][0]['id'];
    });

    step('Create client', function () use ($token, &$clientId) {
        $res = req('POST', '/api/v1/clients', [
            'name' => 'E2E Client',
            'company' => 'E2E Homes',
            'email' => 'client@example.com',
        ], $token);
        $clientId = (int) $res['data']['id'];
    });

    step('Create project hierarchy', function () use ($token, $orgId, $clientId, &$projectId, &$roomId) {
        $res = req('POST', '/api/v1/projects', [
            'organization_id' => $orgId,
            'client_id' => $clientId,
            'name' => 'E2E Villa Project',
        ], $token);
        $projectId = (int) $res['data']['id'];
        assert($res['data']['status'] === 'DRAFT');
        assert($res['data']['workflow_stage'] === 'DRAFT');
        $detail = req('GET', '/api/v1/projects/' . $projectId, null, $token);
        $roomId = (int) $detail['data']['buildings'][0]['floors'][0]['rooms'][0]['id'];
    });

    step('Create 2D wall + door', function () use ($token, $projectId, $roomId) {
        req('POST', '/api/v1/design/objects', [
            'project_id' => $projectId,
            'room_id' => $roomId,
            'object_type' => 'WALL',
            'geometry' => ['x1' => 0, 'y1' => 0, 'x2' => 4000, 'y2' => 0, 'thickness' => 100, 'height' => 3000],
        ], $token);
        req('POST', '/api/v1/design/objects', [
            'project_id' => $projectId,
            'room_id' => $roomId,
            'object_type' => 'DOOR',
            'geometry' => ['x1' => 1000, 'y1' => 0, 'x2' => 1900, 'y2' => 0, 'thickness' => 40, 'height' => 2100],
        ], $token);
        $list = req('GET', '/api/v1/rooms/' . $roomId . '/design', null, $token);
        assert(count($list['data']) >= 2);
    });

    step('Seed catalog', function () use ($token) {
        req('POST', '/api/v1/catalog/seed', [], $token);
        $list = req('GET', '/api/v1/catalog/products?published=1', null, $token);
        assert(count($list['data']) >= 1);
    });

    step('Create parametric wardrobe', function () use ($token, $projectId, &$furnitureId) {
        $res = req('POST', '/api/v1/projects/' . $projectId . '/furniture', [
            'template_code' => 'WARDROBE',
            'name' => 'E2E Wardrobe',
            'parameters' => [
                'width' => 2400,
                'height' => 2400,
                'depth' => 600,
                'carcass_thickness' => 18,
                'back_thickness' => 6,
                'shelf_count' => 3,
                'shutter_count' => 2,
            ],
        ], $token);
        $furnitureId = (int) $res['data']['id'];
        assert($res['data']['room_id'] === null);
        assert(count($res['data']['components']) >= 5);
        assert(count($res['data']['component_rows'] ?? []) >= 5);
        // Keep manufacturable sheet size for MVP E2E (avoid BLOCKER on 18mm carcass internals)
        $resized = req('PUT', '/api/v1/furniture/instances/' . $furnitureId . '/parameters', [
            'parameters' => ['width' => 2100],
        ], $token);
        assert((float) $resized['data']['parameters']['width'] === 2100.0);
    });

    step('Generate BOM/BOQ/Price', function () use ($token, $projectId, $furnitureId, &$pricingId) {
        $res = req('POST', '/api/v1/commercial/generate', [
            'project_id' => $projectId,
            'furniture_id' => $furnitureId,
        ], $token);
        $pricingId = (int) $res['data']['pricing_calculation_id'];
        assert($res['data']['breakdown']['final_price'] > 0);
        assert($res['data']['breakdown']['waterfall'][0] === 'Cost');
    });

    step('Quotation approve+accept', function () use ($token, $projectId, $clientId, $pricingId) {
        $q = req('POST', '/api/v1/quotations', [
            'project_id' => $projectId,
            'client_id' => $clientId,
            'pricing_calculation_id' => $pricingId,
        ], $token);
        $a = req('POST', '/api/v1/quotations/' . $q['data']['id'] . '/status', ['status' => 'APPROVED'], $token);
        $b = req('POST', '/api/v1/quotations/' . $q['data']['id'] . '/status', ['status' => 'ACCEPTED'], $token);
        assert($b['data']['status'] === 'ACCEPTED');
        assert(!empty($b['data']['pricing_snapshot']));
    });

    step('Manufacturing generate + release', function () use ($token, $projectId, $furnitureId, &$mfgId) {
        $res = req('POST', '/api/v1/manufacturing/generate', [
            'project_id' => $projectId,
            'furniture_id' => $furnitureId,
        ], $token);
        $mfgId = (int) $res['data']['id'];
        assert(count($res['data']['cutlist']) > 0);
        $rel = req('POST', '/api/v1/manufacturing/' . $mfgId . '/release', [], $token);
        assert($rel['data']['status'] === 'RELEASED');
    });

    step('Nesting + labels', function () use ($token, $mfgId) {
        $nest = req('POST', '/api/v1/manufacturing/' . $mfgId . '/nest', [], $token);
        assert($nest['data']['sheet_count'] >= 1);
        $labels = req('GET', '/api/v1/manufacturing/' . $mfgId . '/labels', null, $token);
        assert(count($labels['data']) >= 1);
        assert($labels['data'][0]['label_payload'] === $labels['data'][0]['public_id']);
    });

    echo "\nE2E MVP PASSED\n";
    exit(0);
} catch (Throwable $e) {
    echo "FAIL\n";
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
