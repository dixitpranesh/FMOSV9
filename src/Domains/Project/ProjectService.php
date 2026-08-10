<?php

declare(strict_types=1);

namespace Fmos\Domains\Project;

use Fmos\Core\Audit;
use Fmos\Core\Auth;
use Fmos\Core\Database;

final class ProjectService
{
    public function createClient(int $tenantId, array $data): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO clients (tenant_id, name, company, email, phone, address, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([
            $tenantId,
            $data['name'],
            $data['company'] ?? null,
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['address'] ?? null,
            'ACTIVE',
        ]);
        $id = (int) $pdo->lastInsertId();
        Audit::record('CREATE', 'client', $id, null, $data);
        return $this->getClient($tenantId, $id);
    }

    public function listClients(int $tenantId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM clients WHERE tenant_id = ? AND deleted_at IS NULL ORDER BY id DESC');
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }

    public function getClient(int $tenantId, int $id): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM clients WHERE tenant_id = ? AND id = ? AND deleted_at IS NULL');
        $stmt->execute([$tenantId, $id]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new \RuntimeException('Client not found');
        }
        return $row;
    }

    public function createProject(int $tenantId, array $data): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO projects (tenant_id, organization_id, client_id, name, project_type, status, workflow_stage, model_mode, version, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW(), NOW())');
        $stmt->execute([
            $tenantId,
            (int) $data['organization_id'],
            (int) $data['client_id'],
            $data['name'],
            $data['project_type'] ?? 'INTERIOR',
            'DRAFT',
            'DRAFT',
            $data['model_mode'] ?? 'FURNITURE_FIRST',
            Auth::id(),
        ]);
        $projectId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare('INSERT INTO buildings (tenant_id, project_id, name, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())');
        $stmt->execute([$tenantId, $projectId, 'Building 1']);
        $buildingId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare('INSERT INTO floors (tenant_id, building_id, name, level_index, created_at, updated_at) VALUES (?, ?, ?, 0, NOW(), NOW())');
        $stmt->execute([$tenantId, $buildingId, 'Ground Floor']);
        $floorId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare('INSERT INTO rooms (tenant_id, floor_id, name, width_mm, depth_mm, height_mm, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$tenantId, $floorId, 'Living Room', 4000, 3500, 3000, 'ACTIVE']);
        $roomId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare('INSERT INTO project_revisions (tenant_id, project_id, revision_number, label, created_by, created_at) VALUES (?, ?, 1, ?, ?, NOW())');
        $stmt->execute([$tenantId, $projectId, 'Initial', Auth::id()]);

        Audit::record('CREATE', 'project', $projectId, null, $data);
        return $this->getProject($tenantId, $projectId);
    }

    public function listProjects(int $tenantId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM projects WHERE tenant_id = ? AND deleted_at IS NULL ORDER BY id DESC');
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }

    public function getProject(int $tenantId, int $id): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM projects WHERE tenant_id = ? AND id = ? AND deleted_at IS NULL');
        $stmt->execute([$tenantId, $id]);
        $project = $stmt->fetch();
        if (!$project) {
            throw new \RuntimeException('Project not found');
        }

        $stmt = $pdo->prepare('SELECT * FROM buildings WHERE project_id = ?');
        $stmt->execute([$id]);
        $buildings = $stmt->fetchAll();
        foreach ($buildings as &$b) {
            $stmt = $pdo->prepare('SELECT * FROM floors WHERE building_id = ?');
            $stmt->execute([(int) $b['id']]);
            $floors = $stmt->fetchAll();
            foreach ($floors as &$f) {
                $stmt = $pdo->prepare('SELECT * FROM rooms WHERE floor_id = ?');
                $stmt->execute([(int) $f['id']]);
                $f['rooms'] = $stmt->fetchAll();
            }
            $b['floors'] = $floors;
        }
        $project['buildings'] = $buildings;
        return $project;
    }

    public function updateWorkflow(int $tenantId, int $projectId, ?string $status, ?string $workflowStage, int $expectedVersion): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM projects WHERE tenant_id = ? AND id = ?');
        $stmt->execute([$tenantId, $projectId]);
        $project = $stmt->fetch();
        if (!$project) {
            throw new \RuntimeException('Project not found');
        }
        if ((int) $project['version'] !== $expectedVersion) {
            throw new \RuntimeException('STALE_DATA');
        }
        $before = $project;
        $stmt = $pdo->prepare('UPDATE projects SET status = COALESCE(?, status), workflow_stage = COALESCE(?, workflow_stage), version = version + 1, updated_at = NOW() WHERE id = ? AND tenant_id = ? AND version = ?');
        $stmt->execute([$status, $workflowStage, $projectId, $tenantId, $expectedVersion]);
        if ($stmt->rowCount() === 0) {
            throw new \RuntimeException('STALE_DATA');
        }
        $after = $this->getProject($tenantId, $projectId);
        Audit::record('UPDATE', 'project', $projectId, $before, $after);
        return $after;
    }
}
