<?php

declare(strict_types=1);

namespace Fmos\Domains\Furniture;

use Fmos\Core\Audit;
use Fmos\Core\Database;

final class FurnitureEngine
{
    public function ensureTemplates(): void
    {
        $pdo = Database::connection();
        $templates = [
            'WARDROBE' => [
                'name' => 'Wardrobe',
                'category' => 'WARDROBE',
                'parameters' => [
                    'width' => ['default' => 2400, 'min' => 600, 'max' => 3600, 'unit' => 'mm'],
                    'height' => ['default' => 2400, 'min' => 1200, 'max' => 2700, 'unit' => 'mm'],
                    'depth' => ['default' => 600, 'min' => 400, 'max' => 700, 'unit' => 'mm'],
                    'carcass_thickness' => ['default' => 18, 'min' => 12, 'max' => 25, 'unit' => 'mm'],
                    'back_thickness' => ['default' => 6, 'min' => 3, 'max' => 12, 'unit' => 'mm'],
                    'shelf_count' => ['default' => 3, 'min' => 0, 'max' => 10, 'unit' => 'pcs'],
                    'shutter_count' => ['default' => 2, 'min' => 1, 'max' => 6, 'unit' => 'pcs'],
                ],
            ],
            'KITCHEN_BASE' => [
                'name' => 'Kitchen Base Unit',
                'category' => 'KITCHEN_BASE',
                'parameters' => [
                    'width' => ['default' => 600, 'min' => 300, 'max' => 1200, 'unit' => 'mm'],
                    'height' => ['default' => 720, 'min' => 600, 'max' => 900, 'unit' => 'mm'],
                    'depth' => ['default' => 560, 'min' => 450, 'max' => 650, 'unit' => 'mm'],
                    'carcass_thickness' => ['default' => 18, 'min' => 12, 'max' => 25, 'unit' => 'mm'],
                    'shelf_count' => ['default' => 1, 'min' => 0, 'max' => 4, 'unit' => 'pcs'],
                    'shutter_count' => ['default' => 1, 'min' => 1, 'max' => 2, 'unit' => 'pcs'],
                ],
            ],
        ];

        foreach ($templates as $code => $tpl) {
            $check = $pdo->prepare('SELECT id FROM furniture_templates WHERE code = ? AND tenant_id IS NULL AND version = 1');
            $check->execute([$code]);
            if ($check->fetch()) {
                continue;
            }
            $stmt = $pdo->prepare('INSERT INTO furniture_templates (tenant_id, code, name, category, version, parameters_json, rules_json, status, created_at, updated_at) VALUES (NULL, ?, ?, ?, 1, ?, ?, ?, NOW(), NOW())');
            $stmt->execute([
                $code,
                $tpl['name'],
                $tpl['category'],
                json_encode($tpl['parameters']),
                json_encode(['engine' => 'deterministic_v1']),
                'PUBLISHED',
            ]);
        }
    }

    public function createInstance(int $tenantId, array $data): array
    {
        $this->ensureTemplates();
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM furniture_templates WHERE code = ? AND status = ? ORDER BY version DESC LIMIT 1');
        $stmt->execute([$data['template_code'], 'PUBLISHED']);
        $template = $stmt->fetch();
        if (!$template) {
            throw new \RuntimeException('Template not found');
        }
        $defs = json_decode($template['parameters_json'], true);
        $values = $data['parameters'] ?? [];
        foreach ($defs as $key => $def) {
            if (!array_key_exists($key, $values)) {
                $values[$key] = $def['default'];
            }
            if ($values[$key] < $def['min'] || $values[$key] > $def['max']) {
                throw new \RuntimeException("Invalid parameter {$key}");
            }
        }
        $components = $this->generateComponents($template['code'], $values);
        $stmt = $pdo->prepare('INSERT INTO furniture_instances (tenant_id, project_id, room_id, template_id, template_version, name, parameter_values_json, position_json, components_json, material_id, revision, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW(), NOW())');
        $stmt->execute([
            $tenantId,
            (int) $data['project_id'],
            (int) $data['room_id'],
            (int) $template['id'],
            (int) $template['version'],
            $data['name'] ?? $template['name'],
            json_encode($values),
            json_encode($data['position'] ?? ['x' => 0, 'y' => 0, 'z' => 0, 'rotation' => 0]),
            json_encode($components),
            $data['material_id'] ?? null,
            'ACTIVE',
        ]);
        $id = (int) $pdo->lastInsertId();
        Audit::record('CREATE', 'furniture_instance', $id, null, $values);
        return $this->get($tenantId, $id);
    }

    public function updateParameters(int $tenantId, int $id, array $parameters): array
    {
        $instance = $this->get($tenantId, $id);
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM furniture_templates WHERE id = ?');
        $stmt->execute([(int) $instance['template_id']]);
        $template = $stmt->fetch();
        $defs = json_decode($template['parameters_json'], true);
        $values = array_merge($instance['parameters'], $parameters);
        foreach ($defs as $key => $def) {
            if ($values[$key] < $def['min'] || $values[$key] > $def['max']) {
                throw new \RuntimeException("Invalid parameter {$key}");
            }
        }
        $components = $this->generateComponents($template['code'], $values);
        $stmt = $pdo->prepare("UPDATE furniture_instances SET parameter_values_json=?, components_json=?, revision=revision+1, stale_flags_json=?, updated_at=NOW() WHERE id=? AND tenant_id=?");
        $stmt->execute([
            json_encode($values),
            json_encode($components),
            json_encode(['bom' => true, 'boq' => true, 'cutlist' => true, 'nesting' => true]),
            $id,
            $tenantId,
        ]);
        Audit::record('UPDATE', 'furniture_instance', $id, $instance['parameters'], $values);
        return $this->get($tenantId, $id);
    }

    public function listByProject(int $tenantId, int $projectId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM furniture_instances WHERE tenant_id=? AND project_id=? AND deleted_at IS NULL');
        $stmt->execute([$tenantId, $projectId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function get(int $tenantId, int $id): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM furniture_instances WHERE id=? AND tenant_id=? AND deleted_at IS NULL');
        $stmt->execute([$id, $tenantId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new \RuntimeException('Furniture not found');
        }
        return $this->hydrate($row);
    }

    /** @return list<array<string,mixed>> */
    public function generateComponents(string $code, array $p): array
    {
        $t = (float) $p['carcass_thickness'];
        $w = (float) $p['width'];
        $h = (float) $p['height'];
        $d = (float) $p['depth'];
        $internalW = $w - (2 * $t);
        $shelfCount = (int) ($p['shelf_count'] ?? 0);
        $shutterCount = (int) ($p['shutter_count'] ?? 1);
        $components = [
            ['name' => 'Left Side', 'length_mm' => $h, 'width_mm' => $d, 'thickness_mm' => $t, 'qty' => 1],
            ['name' => 'Right Side', 'length_mm' => $h, 'width_mm' => $d, 'thickness_mm' => $t, 'qty' => 1],
            ['name' => 'Top', 'length_mm' => $internalW, 'width_mm' => $d, 'thickness_mm' => $t, 'qty' => 1],
            ['name' => 'Bottom', 'length_mm' => $internalW, 'width_mm' => $d, 'thickness_mm' => $t, 'qty' => 1],
        ];

        // Split back into sheet-fit panels (MVP manufacturable representation)
        $backT = (float) ($p['back_thickness'] ?? 6);
        $backParts = (int) max(1, ceil($w / 1220));
        $backW = round($w / $backParts, 2);
        $components[] = ['name' => 'Back', 'length_mm' => $h, 'width_mm' => $backW, 'thickness_mm' => $backT, 'qty' => $backParts];

        if ($shelfCount > 0) {
            $components[] = ['name' => 'Shelf', 'length_mm' => $internalW, 'width_mm' => $d - $t, 'thickness_mm' => $t, 'qty' => $shelfCount];
        }
        $shutterW = $internalW / max(1, $shutterCount);
        $components[] = ['name' => 'Shutter', 'length_mm' => $h, 'width_mm' => $shutterW, 'thickness_mm' => $t, 'qty' => $shutterCount];
        $components[] = ['name' => 'Hinge', 'length_mm' => 0, 'width_mm' => 0, 'thickness_mm' => 0, 'qty' => $shutterCount * 2, 'type' => 'HARDWARE'];
        return $components;
    }

    private function hydrate(array $row): array
    {
        $row['parameters'] = json_decode($row['parameter_values_json'], true);
        $row['position'] = json_decode($row['position_json'], true);
        $row['components'] = json_decode($row['components_json'], true);
        $row['stale_flags'] = json_decode($row['stale_flags_json'] ?? 'null', true);
        unset($row['parameter_values_json'], $row['position_json'], $row['components_json'], $row['stale_flags_json']);
        return $row;
    }
}
