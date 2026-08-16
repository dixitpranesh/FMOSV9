<?php

declare(strict_types=1);

namespace Fmos\Domains\Catalog;

use Fmos\Core\Audit;
use Fmos\Core\Database;

final class CatalogService
{
    public function list(int $tenantId, bool $publishedOnly = false): array
    {
        $pdo = Database::connection();
        $sql = 'SELECT * FROM catalog_products WHERE tenant_id = ? AND deleted_at IS NULL';
        if ($publishedOnly) {
            $sql .= " AND publish_status = 'PUBLISHED'";
        }
        $sql .= ' ORDER BY category, name';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }

    public function create(int $tenantId, array $data): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO catalog_products (tenant_id, sku, name, category, publish_status, availability_status, brand, thickness_mm, length_mm, width_mm, cost, selling_price, uom, attributes_json, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $attrs = $data['attributes'] ?? $data['attributes_json'] ?? null;
        if (is_array($attrs)) {
            $attrs = json_encode($attrs);
        }
        $stmt->execute([
            $tenantId,
            $data['sku'],
            $data['name'],
            $data['category'],
            $data['publish_status'] ?? 'DRAFT',
            $data['availability_status'] ?? 'INACTIVE',
            $data['brand'] ?? null,
            $data['thickness_mm'] ?? null,
            $data['length_mm'] ?? null,
            $data['width_mm'] ?? null,
            $data['cost'] ?? 0,
            $data['selling_price'] ?? 0,
            $data['uom'] ?? 'SQ_FT',
            $attrs,
        ]);
        $id = (int) $pdo->lastInsertId();
        Audit::record('CREATE', 'catalog_product', $id, null, $data);
        return $this->get($tenantId, $id);
    }

    public function publish(int $tenantId, int $id): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare("UPDATE catalog_products SET publish_status='PUBLISHED', availability_status='ACTIVE', updated_at=NOW() WHERE id=? AND tenant_id=?");
        $stmt->execute([$id, $tenantId]);
        Audit::record('PUBLISH', 'catalog_product', $id);
        return $this->get($tenantId, $id);
    }

    public function get(int $tenantId, int $id): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM catalog_products WHERE id=? AND tenant_id=? AND deleted_at IS NULL');
        $stmt->execute([$id, $tenantId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new \RuntimeException('Catalog product not found');
        }
        return $row;
    }

    public function seedDefaults(int $tenantId): void
    {
        $defaults = [
            // sku, name, cat, th, len, wid, cost, sell, uom, hardware_role?
            ['BRD-18-MDF', '18mm MDF Board', 'BOARD', 18, 2440, 1220, 45, 65, 'SQ_FT', null],
            ['BRD-06-MDF', '6mm MDF Board', 'BOARD', 6, 2440, 1220, 28, 40, 'SQ_FT', null],
            ['BRD-12-MDF', '12mm MDF Board', 'BOARD', 12, 2440, 1220, 36, 52, 'SQ_FT', null],
            ['LAM-WH-01', 'White Laminate', 'LAMINATE', 1, 2440, 1220, 12, 22, 'SQ_FT', null],
            ['EDGE-22-WH', 'White Edge Band 22mm', 'EDGE_BAND', 0.8, null, 22, 2.5, 4, 'M', null],
            ['EDGE-22-BK', 'Black Edge Band 22mm', 'EDGE_BAND', 0.8, null, 22, 2.5, 4, 'M', null],
        ];
        foreach (HardwareSkuCatalog::seedRows() as $row) {
            $defaults[] = $row;
        }
        foreach ($defaults as $row) {
            [$sku, $name, $cat, $th, $len, $wid, $cost, $sell, $uom, $hwRole] = array_pad($row, 10, null);
            // Back-compat if old 8-tuple somehow passed
            if (!is_string($uom) || $uom === '') {
                $uom = $cat === 'HARDWARE' ? 'PCS' : ($cat === 'EDGE_BAND' ? 'M' : 'SQ_FT');
            }
            $pdo = Database::connection();
            $exists = $pdo->prepare('SELECT id FROM catalog_products WHERE tenant_id=? AND sku=?');
            $exists->execute([$tenantId, $sku]);
            if ($exists->fetch()) {
                if ($hwRole) {
                    $pdo->prepare(
                        "UPDATE catalog_products SET attributes_json=JSON_SET(COALESCE(attributes_json, '{}'), '$.hardware_role', ?)
                         WHERE tenant_id=? AND sku=? AND deleted_at IS NULL"
                    )->execute([(string) $hwRole, $tenantId, $sku]);
                }
                continue;
            }
            $payload = [
                'sku' => $sku,
                'name' => $name,
                'category' => $cat,
                'publish_status' => 'PUBLISHED',
                'availability_status' => 'ACTIVE',
                'thickness_mm' => $th,
                'length_mm' => $len,
                'width_mm' => $wid,
                'cost' => $cost,
                'selling_price' => $sell,
                'uom' => $uom,
            ];
            if ($hwRole) {
                $payload['attributes'] = ['hardware_role' => (string) $hwRole];
            }
            $this->create($tenantId, $payload);
        }
    }
}
