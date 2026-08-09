<?php

declare(strict_types=1);

namespace Fmos\Domains\Tenant;

use Fmos\Core\Audit;
use Fmos\Core\Database;
use Fmos\Domains\Identity\RbacSeeder;
use PDO;

final class TenantService
{
    public function createTenant(string $code, string $name, string $ownerEmail, string $ownerName, string $password): array
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO tenants (code, name, currency, measurement_system, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
            $stmt->execute([$code, $name, 'INR', 'metric', 'ACTIVE']);
            $tenantId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare('INSERT INTO organizations (tenant_id, code, name, status, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
            $stmt->execute([$tenantId, 'MAIN', 'Main Organization', 'ACTIVE']);
            $orgId = (int) $pdo->lastInsertId();

            RbacSeeder::seed();

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (tenant_id, email, name, password_hash, is_platform_user, status, created_at, updated_at) VALUES (?, ?, ?, ?, 0, ?, NOW(), NOW())');
            $stmt->execute([$tenantId, $ownerEmail, $ownerName, $hash, 'ACTIVE']);
            $userId = (int) $pdo->lastInsertId();

            $roleId = (int) $pdo->query("SELECT id FROM roles WHERE code = 'TENANT_OWNER' AND tenant_id IS NULL")->fetchColumn();
            $stmt = $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)');
            $stmt->execute([$userId, $roleId]);

            $pdo->commit();
            Audit::record('CREATE', 'tenant', $tenantId, null, ['code' => $code, 'name' => $name]);

            return [
                'tenant_id' => $tenantId,
                'organization_id' => $orgId,
                'user_id' => $userId,
            ];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function listOrganizations(int $tenantId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id, tenant_id, code, name, status, created_at FROM organizations WHERE tenant_id = ? AND deleted_at IS NULL ORDER BY id');
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }

    public function createOrganization(int $tenantId, string $code, string $name): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO organizations (tenant_id, code, name, status, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$tenantId, $code, $name, 'ACTIVE']);
        $id = (int) $pdo->lastInsertId();
        Audit::record('CREATE', 'organization', $id, null, ['code' => $code, 'name' => $name]);
        $stmt = $pdo->prepare('SELECT * FROM organizations WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
