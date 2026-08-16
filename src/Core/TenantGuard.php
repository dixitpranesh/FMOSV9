<?php

declare(strict_types=1);

namespace Fmos\Core;

/**
 * Ensures foreign keys referenced in writes belong to the caller's tenant.
 */
final class TenantGuard
{
    /**
     * @throws \RuntimeException when not found / not owned (message RESOURCE_NOT_FOUND)
     */
    public static function assertOwned(string $table, int $id, int $tenantId, ?string $extraWhere = null, array $extraParams = []): void
    {
        if ($id <= 0) {
            throw new \RuntimeException('RESOURCE_NOT_FOUND');
        }
        $allowed = [
            'organizations', 'clients', 'projects', 'buildings', 'floors', 'rooms',
            'furniture_instances', 'design_objects', 'manufacturing_packages',
            'manufacturing_jobs', 'kitchen_compositions', 'materials', 'quotations',
            'pricing_calculations', 'catalog_products',
        ];
        if (!in_array($table, $allowed, true)) {
            throw new \InvalidArgumentException('Unsupported tenant table: ' . $table);
        }
        $pdo = Database::connection();
        $sql = "SELECT id FROM {$table} WHERE id = ? AND tenant_id = ?";
        $params = [$id, $tenantId];
        if ($extraWhere) {
            $sql .= ' AND ' . $extraWhere;
            foreach ($extraParams as $p) {
                $params[] = $p;
            }
        }
        $sql .= ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetchColumn()) {
            throw new \RuntimeException('RESOURCE_NOT_FOUND');
        }
    }

    public static function assertOwnedOrFail(string $table, int $id, int $tenantId): void
    {
        try {
            self::assertOwned($table, $id, $tenantId);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'RESOURCE_NOT_FOUND') {
                Response::error('RESOURCE_NOT_FOUND', 'Resource not found', 404);
                exit;
            }
            throw $e;
        }
    }

    /** Room must belong to tenant; optionally also to a project. */
    public static function assertRoom(int $roomId, int $tenantId, ?int $projectId = null): void
    {
        if ($projectId === null) {
            self::assertOwned('rooms', $roomId, $tenantId);
            return;
        }
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT r.id FROM rooms r
             INNER JOIN floors f ON f.id = r.floor_id
             INNER JOIN buildings b ON b.id = f.building_id
             WHERE r.id = ? AND r.tenant_id = ? AND b.project_id = ?
             LIMIT 1'
        );
        $stmt->execute([$roomId, $tenantId, $projectId]);
        if (!$stmt->fetchColumn()) {
            throw new \RuntimeException('RESOURCE_NOT_FOUND');
        }
    }
}
