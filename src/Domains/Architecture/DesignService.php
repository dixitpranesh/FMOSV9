<?php

declare(strict_types=1);

namespace Fmos\Domains\Architecture;

use Fmos\Core\Audit;
use Fmos\Core\Auth;
use Fmos\Core\Database;
use Fmos\Core\TenantGuard;

final class DesignService
{
    public function listByRoom(int $tenantId, int $roomId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM design_objects WHERE tenant_id = ? AND room_id = ? AND deleted_at IS NULL');
        $stmt->execute([$tenantId, $roomId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function upsert(int $tenantId, array $data): array
    {
        $pdo = Database::connection();
        if (!empty($data['id'])) {
            $stmt = $pdo->prepare('UPDATE design_objects SET geometry_json=?, parameters_json=?, materials_json=?, name=?, revision=revision+1, updated_by=?, updated_at=NOW() WHERE id=? AND tenant_id=? AND deleted_at IS NULL');
            $stmt->execute([
                json_encode($data['geometry']),
                json_encode($data['parameters'] ?? new \stdClass()),
                json_encode($data['materials'] ?? new \stdClass()),
                $data['name'] ?? null,
                Auth::id(),
                (int) $data['id'],
                $tenantId,
            ]);
            $id = (int) $data['id'];
            Audit::record('UPDATE', 'design_object', $id);
        } else {
            TenantGuard::assertOwned('projects', (int) $data['project_id'], $tenantId);
            TenantGuard::assertRoom((int) $data['room_id'], $tenantId, (int) $data['project_id']);
            $stmt = $pdo->prepare('INSERT INTO design_objects (tenant_id, project_id, room_id, parent_id, object_type, name, geometry_json, parameters_json, materials_json, revision, status, created_by, updated_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, NOW(), NOW())');
            $stmt->execute([
                $tenantId,
                (int) $data['project_id'],
                (int) $data['room_id'],
                $data['parent_id'] ?? null,
                $data['object_type'],
                $data['name'] ?? null,
                json_encode($data['geometry']),
                json_encode($data['parameters'] ?? new \stdClass()),
                json_encode($data['materials'] ?? new \stdClass()),
                'ACTIVE',
                Auth::id(),
                Auth::id(),
            ]);
            $id = (int) $pdo->lastInsertId();
            Audit::record('CREATE', 'design_object', $id, null, $data);
        }
        return $this->get($tenantId, $id);
    }

    public function delete(int $tenantId, int $id): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE design_objects SET deleted_at = NOW() WHERE id = ? AND tenant_id = ?');
        $stmt->execute([$id, $tenantId]);
        Audit::record('DELETE', 'design_object', $id);
    }

    public function get(int $tenantId, int $id): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM design_objects WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL');
        $stmt->execute([$id, $tenantId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new \RuntimeException('Design object not found');
        }
        return $this->hydrate($row);
    }

    private function hydrate(array $row): array
    {
        $row['geometry'] = json_decode($row['geometry_json'] ?? '{}', true);
        $row['parameters'] = json_decode($row['parameters_json'] ?? '{}', true);
        $row['materials'] = json_decode($row['materials_json'] ?? '{}', true);
        unset($row['geometry_json'], $row['parameters_json'], $row['materials_json'], $row['metadata_json']);
        return $row;
    }
}
