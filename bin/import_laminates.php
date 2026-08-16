<?php

declare(strict_types=1);

/**
 * Import laminate textures from a local folder into tenant materials.
 *
 * Usage:
 *   php bin/import_laminates.php --path=C:\laminates --tenant=demo
 */

use Fmos\Core\Database;
use Fmos\Core\Env;
use Fmos\Domains\Catalog\MaterialService;

require dirname(__DIR__) . '/vendor/autoload.php';
Env::load(dirname(__DIR__) . '/.env');

$opts = getopt('', ['path:', 'tenant:']);
$path = $opts['path'] ?? 'C:\\laminates';
$tenantCode = $opts['tenant'] ?? 'demo';

if (!is_dir($path)) {
    fwrite(STDERR, "Path not found: {$path}\n");
    exit(1);
}

$pdo = Database::connection();
$stmt = $pdo->prepare('SELECT id FROM tenants WHERE code = ? LIMIT 1');
$stmt->execute([$tenantCode]);
$tenantId = (int) $stmt->fetchColumn();
if ($tenantId <= 0) {
    fwrite(STDERR, "Tenant not found: {$tenantCode}\n");
    exit(1);
}

$root = dirname(__DIR__);
$svc = new MaterialService();
$files = glob(rtrim($path, '\\/') . DIRECTORY_SEPARATOR . '*.webp') ?: [];
sort($files);

$created = 0;
$updated = 0;
$assets = 0;
$skipped = 0;

foreach ($files as $file) {
    $base = pathinfo($file, PATHINFO_FILENAME);
    if (!preg_match('/^(\d+)_(ECO|SHR|STR)_(\d+)_(\d+)$/i', $base, $m)) {
        echo "SKIP (pattern): {$base}\n";
        $skipped++;
        continue;
    }
    $supplier = $m[1];
    $series = strtoupper($m[2]);
    $design = (int) $m[3];
    $colorway = (int) $m[4];
    $sku = $base; // CRD-011: display name = code

    $material = $svc->upsert($tenantId, [
        'sku' => $sku,
        'name' => $sku,
        'category' => 'LAMINATE',
        'series_code' => $series,
        'series_name' => MaterialService::seriesName($series),
        'supplier_code' => $supplier,
        'design_index' => $design,
        'colorway_index' => $colorway,
        'default_roughness' => $series === 'ECO' ? 0.68 : 0.55,
        'default_metalness' => 0.0,
        'status' => 'ACTIVE',
        'attributes' => [
            'source_file' => basename($file),
            'texture_family' => $series,
        ],
    ]);
    $materialId = (int) $material['id'];
    if (!empty($material['assets'])) {
        $updated++;
    } else {
        $created++;
    }

    $relDir = "tenants/{$tenantId}/materials/{$sku}";
    $publicDir = $root . '/public/media/' . $relDir;
    $storageDir = $root . '/storage/' . $relDir;
    foreach ([$publicDir, $storageDir] as $dir) {
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("Cannot create {$dir}");
        }
    }

    $destName = 'albedo.webp';
    $publicFile = $publicDir . DIRECTORY_SEPARATOR . $destName;
    $storageFile = $storageDir . DIRECTORY_SEPARATOR . $destName;
    if (!copy($file, $publicFile)) {
        throw new RuntimeException("Copy failed to {$publicFile}");
    }
    copy($publicFile, $storageFile);

    $width = null;
    $height = null;
    // Optional size probe via PHP if available later; keep null for now.

    $publicUrl = '/media/' . $relDir . '/' . $destName;
    $existingPrimary = false;
    foreach ($material['assets'] as $asset) {
        if (($asset['asset_type'] ?? '') === 'TEXTURE_ALBEDO' && (int) ($asset['is_primary'] ?? 0) === 1) {
            $existingPrimary = true;
            // Refresh path/url
            $pdo->prepare('UPDATE material_assets SET storage_path=?, public_url=?, mime=?, updated_at=NOW() WHERE id=? AND tenant_id=?')
                ->execute([
                    'storage/' . $relDir . '/' . $destName,
                    $publicUrl,
                    'image/webp',
                    (int) $asset['id'],
                    $tenantId,
                ]);
            break;
        }
    }
    if (!$existingPrimary) {
        $svc->addAsset($tenantId, $materialId, [
            'asset_type' => 'TEXTURE_ALBEDO',
            'storage_path' => 'storage/' . $relDir . '/' . $destName,
            'public_url' => $publicUrl,
            'mime' => 'image/webp',
            'width_px' => $width,
            'height_px' => $height,
            'is_primary' => true,
            'sort_order' => 0,
        ]);
        $assets++;
    }

    echo "OK  {$sku} ({$series}/" . MaterialService::seriesName($series) . ")\n";
}

echo "\nImport complete for tenant {$tenantCode} (#{$tenantId})\n";
echo "  materials touched: " . ($created + $updated) . " (new≈{$created}, existing≈{$updated})\n";
echo "  new assets: {$assets}\n";
echo "  skipped: {$skipped}\n";
