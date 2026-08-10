<?php

declare(strict_types=1);

namespace Fmos\Domains\Catalog;

use Fmos\Core\Audit;
use Fmos\Core\Database;

final class MaterialService
{
    public function list(int $tenantId, ?string $category = null, ?string $seriesCode = null): array
    {
        $pdo = Database::connection();
        $sql = 'SELECT m.* FROM materials m WHERE m.tenant_id = ? AND m.deleted_at IS NULL';
        $params = [$tenantId];
        if ($category) {
            $sql .= ' AND m.category = ?';
            $params[] = $category;
        }
        if ($seriesCode) {
            $sql .= ' AND m.series_code = ?';
            $params[] = $seriesCode;
        }
        $sql .= ' ORDER BY m.series_code, m.sku';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['assets'] = $this->listAssets($tenantId, (int) $row['id']);
            $row['attributes'] = json_decode($row['attributes_json'] ?? 'null', true);
            unset($row['attributes_json']);
        }
        return $rows;
    }

    public function get(int $tenantId, int $id): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM materials WHERE id=? AND tenant_id=? AND deleted_at IS NULL');
        $stmt->execute([$id, $tenantId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new \RuntimeException('Material not found');
        }
        $row['assets'] = $this->listAssets($tenantId, $id);
        $row['attributes'] = json_decode($row['attributes_json'] ?? 'null', true);
        unset($row['attributes_json']);
        return $row;
    }

    public function upsert(int $tenantId, array $data): array
    {
        $pdo = Database::connection();
        $sku = (string) $data['sku'];
        $exists = $pdo->prepare('SELECT id FROM materials WHERE tenant_id=? AND sku=? AND deleted_at IS NULL');
        $exists->execute([$tenantId, $sku]);
        $existing = $exists->fetch();

        if ($existing) {
            $id = (int) $existing['id'];
            $stmt = $pdo->prepare('UPDATE materials SET name=?, category=?, series_code=?, series_name=?, supplier_code=?, design_index=?, colorway_index=?, default_roughness=?, default_metalness=?, status=?, attributes_json=?, updated_at=NOW() WHERE id=? AND tenant_id=?');
            $stmt->execute([
                $data['name'] ?? $sku,
                $data['category'] ?? 'LAMINATE',
                $data['series_code'] ?? null,
                $data['series_name'] ?? null,
                $data['supplier_code'] ?? null,
                $data['design_index'] ?? null,
                $data['colorway_index'] ?? null,
                $data['default_roughness'] ?? 0.55,
                $data['default_metalness'] ?? 0.0,
                $data['status'] ?? 'ACTIVE',
                isset($data['attributes']) ? json_encode($data['attributes']) : null,
                $id,
                $tenantId,
            ]);
            Audit::record('UPDATE', 'material', $id, null, $data);
            return $this->get($tenantId, $id);
        }

        $stmt = $pdo->prepare('INSERT INTO materials (tenant_id, sku, name, category, series_code, series_name, supplier_code, design_index, colorway_index, default_roughness, default_metalness, status, attributes_json, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([
            $tenantId,
            $sku,
            $data['name'] ?? $sku,
            $data['category'] ?? 'LAMINATE',
            $data['series_code'] ?? null,
            $data['series_name'] ?? null,
            $data['supplier_code'] ?? null,
            $data['design_index'] ?? null,
            $data['colorway_index'] ?? null,
            $data['default_roughness'] ?? 0.55,
            $data['default_metalness'] ?? 0.0,
            $data['status'] ?? 'ACTIVE',
            isset($data['attributes']) ? json_encode($data['attributes']) : null,
        ]);
        $id = (int) $pdo->lastInsertId();
        Audit::record('CREATE', 'material', $id, null, $data);
        return $this->get($tenantId, $id);
    }

    public function addAsset(int $tenantId, int $materialId, array $data): array
    {
        $this->get($tenantId, $materialId);
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO material_assets (tenant_id, material_id, asset_type, storage_path, public_url, mime, width_px, height_px, is_primary, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([
            $tenantId,
            $materialId,
            $data['asset_type'] ?? 'TEXTURE_ALBEDO',
            $data['storage_path'],
            $data['public_url'],
            $data['mime'] ?? 'image/webp',
            $data['width_px'] ?? null,
            $data['height_px'] ?? null,
            !empty($data['is_primary']) ? 1 : 0,
            (int) ($data['sort_order'] ?? 0),
        ]);
        $id = (int) $pdo->lastInsertId();
        Audit::record('CREATE', 'material_asset', $id, null, $data);
        return $this->getAsset($tenantId, $id);
    }

    public function getAsset(int $tenantId, int $id): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM material_assets WHERE id=? AND tenant_id=?');
        $stmt->execute([$id, $tenantId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new \RuntimeException('Material asset not found');
        }
        return $row;
    }

    /** @return list<array<string,mixed>> */
    public function listAssets(int $tenantId, int $materialId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM material_assets WHERE tenant_id=? AND material_id=? ORDER BY is_primary DESC, sort_order, id');
        $stmt->execute([$tenantId, $materialId]);
        return $stmt->fetchAll();
    }

    public static function seriesName(string $code): string
    {
        return match (strtoupper($code)) {
            'ECO' => 'Echoe',
            'SHR' => 'Shore',
            'STR' => 'Strand',
            default => $code,
        };
    }
}
