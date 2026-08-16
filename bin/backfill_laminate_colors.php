<?php

declare(strict_types=1);

/**
 * Backfill materials.attributes.base_color from albedo average (Pillow).
 *
 *   php bin/backfill_laminate_colors.php --tenant=demo
 */

use Fmos\Core\Database;
use Fmos\Core\Env;

require dirname(__DIR__) . '/vendor/autoload.php';
Env::load(dirname(__DIR__) . '/.env');

$opts = getopt('', ['tenant:']);
$tenantCode = $opts['tenant'] ?? 'demo';
$root = dirname(__DIR__);

$pdo = Database::connection();
$stmt = $pdo->prepare('SELECT id FROM tenants WHERE code = ? LIMIT 1');
$stmt->execute([$tenantCode]);
$tenantId = (int) $stmt->fetchColumn();
if ($tenantId <= 0) {
    fwrite(STDERR, "Tenant not found: {$tenantCode}\n");
    exit(1);
}

$py = <<<'PY'
import json, sys
from pathlib import Path
try:
    from PIL import Image
except ImportError:
    print("ERR|pillow missing", file=sys.stderr)
    sys.exit(2)
path = Path(sys.argv[1])
im = Image.open(path).convert("RGB")
small = im.resize((64, 64))
px = list(small.getdata())
n = len(px)
r = sum(p[0] for p in px) // n
g = sum(p[1] for p in px) // n
b = sum(p[2] for p in px) // n
print(f"{r:02X}{g:02X}{b:02X}")
PY;
$pyFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fmos_avg_color.py';
file_put_contents($pyFile, $py);

$mats = $pdo->prepare('SELECT m.id, m.sku, m.attributes_json, a.public_url FROM materials m
  LEFT JOIN material_assets a ON a.material_id=m.id AND a.asset_type=\'TEXTURE_ALBEDO\'
  WHERE m.tenant_id=? AND m.deleted_at IS NULL');
$mats->execute([$tenantId]);
$updated = 0;
foreach ($mats->fetchAll() as $row) {
    $url = (string) ($row['public_url'] ?? '');
    if ($url === '' || !str_starts_with($url, '/media/') || str_contains($url, '..')) {
        continue;
    }
    $publicRoot = realpath($root . '/public');
    $file = realpath($root . '/public' . str_replace('/', DIRECTORY_SEPARATOR, $url));
    if ($publicRoot === false || $file === false || !str_starts_with($file, $publicRoot) || !is_file($file)) {
        echo "MISS {$row['sku']} {$url}\n";
        continue;
    }
    $out = [];
    $code = 0;
    exec('python ' . escapeshellarg($pyFile) . ' ' . escapeshellarg($file) . ' 2>&1', $out, $code);
    $hex = strtoupper(trim($out[0] ?? ''));
    if ($code !== 0 || !preg_match('/^[0-9A-F]{6}$/', $hex)) {
        echo "FAIL {$row['sku']}: " . implode(' ', $out) . "\n";
        continue;
    }
    $attrs = json_decode((string) ($row['attributes_json'] ?? 'null'), true) ?: [];
    $attrs['base_color'] = '#' . $hex;
    $pdo->prepare('UPDATE materials SET attributes_json=?, updated_at=NOW() WHERE id=? AND tenant_id=?')
        ->execute([json_encode($attrs), (int) $row['id'], $tenantId]);
    echo "OK  {$row['sku']} #{$hex}\n";
    $updated++;
}

@unlink($pyFile);
echo "\nUpdated {$updated} materials with base_color\n";
