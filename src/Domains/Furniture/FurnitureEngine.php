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
        foreach (FurnitureTemplateCatalog::all() as $code => $tpl) {
            $check = $pdo->prepare('SELECT id FROM furniture_templates WHERE code = ? AND tenant_id IS NULL AND version = 1');
            $check->execute([$code]);
            $existing = $check->fetch();
            if ($existing) {
                $stmt = $pdo->prepare('UPDATE furniture_templates SET name=?, category=?, parameters_json=?, rules_json=?, status=?, updated_at=NOW() WHERE id=?');
                $stmt->execute([
                    $tpl['name'],
                    $tpl['category'],
                    json_encode($tpl['parameters']),
                    json_encode(['engine' => 'layout_v1', 'description' => $tpl['description'] ?? '']),
                    'PUBLISHED',
                    (int) $existing['id'],
                ]);
                continue;
            }
            $stmt = $pdo->prepare('INSERT INTO furniture_templates (tenant_id, code, name, category, version, parameters_json, rules_json, status, created_at, updated_at) VALUES (NULL, ?, ?, ?, 1, ?, ?, ?, NOW(), NOW())');
            $stmt->execute([
                $code,
                $tpl['name'],
                $tpl['category'],
                json_encode($tpl['parameters']),
                json_encode(['engine' => 'layout_v1', 'description' => $tpl['description'] ?? '']),
                'PUBLISHED',
            ]);
        }
    }

    /** @return list<array<string,mixed>> */
    public function listTemplates(): array
    {
        $this->ensureTemplates();
        $pdo = Database::connection();
        $rows = $pdo->query("SELECT id, code, name, category, version, parameters_json, rules_json FROM furniture_templates WHERE status='PUBLISHED' ORDER BY category, name")->fetchAll();
        return array_map(static function (array $row): array {
            $row['parameters'] = json_decode($row['parameters_json'] ?? '{}', true) ?: [];
            $row['rules'] = json_decode($row['rules_json'] ?? '{}', true) ?: [];
            $row['description'] = $row['rules']['description'] ?? '';
            unset($row['parameters_json'], $row['rules_json']);
            return $row;
        }, $rows);
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
        $values = $this->applyParameterDefaults($defs, $values);
        $this->validateParameters($defs, $values);

        $roomId = array_key_exists('room_id', $data) && $data['room_id'] !== null && $data['room_id'] !== ''
            ? (int) $data['room_id']
            : null;

        $components = $this->generateComponents($template['code'], $values, $tenantId);
        $quantity = max(1, (int) ($data['quantity'] ?? 1));
        $category = $data['category'] ?? $template['category'];
        $type = $data['type'] ?? $template['code'];
        $code = $data['code'] ?? null;
        $name = $data['name'] ?? $template['name'];

        $stmt = $pdo->prepare('INSERT INTO furniture_instances (
            tenant_id, project_id, room_id, template_id, template_version, name, code, category, type, quantity,
            width_mm, height_mm, depth_mm, parameter_values_json, position_json, components_json,
            material_id, exterior_finish_id, interior_finish_id, specification_json, revision, status, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW(), NOW())');
        $stmt->execute([
            $tenantId,
            (int) $data['project_id'],
            $roomId,
            (int) $template['id'],
            (int) $template['version'],
            $name,
            $code,
            $category,
            $type,
            $quantity,
            (float) $values['width'],
            (float) $values['height'],
            (float) $values['depth'],
            json_encode($values),
            json_encode($data['position'] ?? ['x' => 0, 'y' => 0, 'z' => 0, 'rotation' => 0]),
            json_encode($components),
            $data['material_id'] ?? null,
            $data['exterior_finish_id'] ?? null,
            $data['interior_finish_id'] ?? null,
            json_encode($data['specification'] ?? new \stdClass()),
            'ACTIVE',
        ]);
        $id = (int) $pdo->lastInsertId();

        if ($code === null || $code === '') {
            $autoCode = sprintf('%s-%d', $template['code'], $id);
            $pdo->prepare('UPDATE furniture_instances SET code = ? WHERE id = ? AND tenant_id = ?')
                ->execute([$autoCode, $id, $tenantId]);
        }

        $this->syncComponentsTable($tenantId, $id, $components);
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
        $values = $this->applyParameterDefaults($defs, $values);
        $this->validateParameters($defs, $values);
        $components = $this->generateComponents($template['code'], $values, $tenantId);
        $stmt = $pdo->prepare("UPDATE furniture_instances SET parameter_values_json=?, components_json=?, width_mm=?, height_mm=?, depth_mm=?, revision=revision+1, stale_flags_json=?, updated_at=NOW() WHERE id=? AND tenant_id=?");
        $stmt->execute([
            json_encode($values),
            json_encode($components),
            (float) $values['width'],
            (float) $values['height'],
            (float) $values['depth'],
            json_encode(['bom' => true, 'boq' => true, 'cutlist' => true, 'nesting' => true]),
            $id,
            $tenantId,
        ]);
        $this->syncComponentsTable($tenantId, $id, $components);
        Audit::record('UPDATE', 'furniture_instance', $id, $instance['parameters'], $values);
        return $this->get($tenantId, $id);
    }

    public function updateMeta(int $tenantId, int $id, array $data): array
    {
        $instance = $this->get($tenantId, $id);
        $pdo = Database::connection();
        $fields = [];
        $params = [];
        foreach (['name', 'code', 'category', 'type'] as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "{$col} = ?";
                $params[] = $data[$col];
            }
        }
        if (array_key_exists('quantity', $data)) {
            $fields[] = 'quantity = ?';
            $params[] = max(1, (int) $data['quantity']);
        }
        if (array_key_exists('room_id', $data)) {
            $fields[] = 'room_id = ?';
            $params[] = $data['room_id'] !== null && $data['room_id'] !== '' ? (int) $data['room_id'] : null;
        }
        foreach (['exterior_finish_id', 'interior_finish_id', 'material_id'] as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "{$col} = ?";
                $params[] = $data[$col] !== null && $data[$col] !== '' ? (int) $data[$col] : null;
            }
        }
        if (array_key_exists('specification', $data)) {
            $fields[] = 'specification_json = ?';
            $params[] = json_encode($data['specification']);
        }
        if ($fields === []) {
            return $instance;
        }
        $fields[] = 'updated_at = NOW()';
        $params[] = $id;
        $params[] = $tenantId;
        $pdo->prepare('UPDATE furniture_instances SET ' . implode(', ', $fields) . ' WHERE id = ? AND tenant_id = ?')
            ->execute($params);
        Audit::record('UPDATE', 'furniture_instance_meta', $id, $instance, $data);
        return $this->get($tenantId, $id);
    }

    public function listByProject(int $tenantId, int $projectId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM furniture_instances WHERE tenant_id=? AND project_id=? AND deleted_at IS NULL ORDER BY id');
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
        $hydrated = $this->hydrate($row);
        $hydrated['component_rows'] = $this->listComponentRows($tenantId, $id);
        return $hydrated;
    }

    /** @return list<array<string,mixed>> */
    public function listComponentRows(int $tenantId, int $furnitureId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM furniture_components WHERE tenant_id=? AND furniture_id=? AND deleted_at IS NULL ORDER BY sort_order, id');
        $stmt->execute([$tenantId, $furnitureId]);
        return array_map(static function (array $row): array {
            $row['geometry'] = json_decode($row['geometry_json'] ?? 'null', true);
            $row['manufacturing_data'] = json_decode($row['manufacturing_data_json'] ?? 'null', true);
            unset($row['geometry_json'], $row['manufacturing_data_json']);
            return $row;
        }, $stmt->fetchAll());
    }

    public function updateLayout(int $tenantId, int $id, array $layout): array
    {
        $instance = $this->get($tenantId, $id);
        $layoutEngine = new FurnitureLayoutEngine();
        $normalized = $layoutEngine->normalizeLayout([
            'layout' => $layout,
            'carcass_thickness' => $instance['parameters']['carcass_thickness'] ?? 18,
            'door_type' => $layout['door_type'] ?? ($instance['parameters']['door_type'] ?? 'HINGED'),
        ]);
        return $this->updateParameters($tenantId, $id, [
            'layout' => $normalized,
            'door_type' => $normalized['door_type'] ?? 'HINGED',
        ]);
    }

    /** @return list<array<string,mixed>> */
    public function generateComponents(string $code, array $p, ?int $tenantId = null): array
    {
        $logical = (new FurnitureLayoutEngine())->generate($code, $p);
        $sheet = $this->resolveDefaultSheet($tenantId);
        return $this->normalizeToSheet($logical, (float) $sheet['length_mm'], (float) $sheet['width_mm']);
    }

    /**
     * @param array<string,mixed> $defs
     * @param array<string,mixed> $values
     * @return array<string,mixed>
     */
    private function applyParameterDefaults(array $defs, array $values): array
    {
        foreach ($defs as $key => $def) {
            if (!array_key_exists($key, $values)) {
                $values[$key] = $def['default'] ?? null;
            }
        }
        if (!isset($values['layout']) || !is_array($values['layout'])) {
            // keep as-is; layout engine will synthesize legacy layout
        }
        return $values;
    }

    /**
     * @param array<string,mixed> $defs
     * @param array<string,mixed> $values
     */
    private function validateParameters(array $defs, array $values): void
    {
        foreach ($defs as $key => $def) {
            if (!array_key_exists($key, $values)) {
                continue;
            }
            $type = $def['type'] ?? 'number';
            if ($type === 'layout' || $type === 'object' || is_array($values[$key])) {
                continue;
            }
            if ($type === 'enum') {
                $opts = $def['options'] ?? [];
                if ($opts !== [] && !in_array($values[$key], $opts, true)) {
                    throw new \RuntimeException("Invalid parameter {$key}");
                }
                continue;
            }
            if (!is_numeric($values[$key])) {
                throw new \RuntimeException("Invalid parameter {$key}");
            }
            if (isset($def['min'], $def['max']) && ($values[$key] < $def['min'] || $values[$key] > $def['max'])) {
                throw new \RuntimeException("Invalid parameter {$key}");
            }
        }
    }

    /**
     * @param list<array<string,mixed>> $components
     * @return list<array<string,mixed>>
     */
    public function normalizeToSheet(array $components, float $sheetL = 2440.0, float $sheetW = 1220.0): array
    {
        $out = [];
        foreach ($components as $c) {
            if (($c['type'] ?? '') === 'HARDWARE') {
                $out[] = $c;
                continue;
            }

            $parts = $this->splitPanelToSheet(
                (string) $c['name'],
                (float) $c['length_mm'],
                (float) $c['width_mm'],
                (float) $c['thickness_mm'],
                (int) $c['qty'],
                $sheetL,
                $sheetW,
                (string) ($c['type'] ?? 'PANEL')
            );
            foreach ($parts as $part) {
                $out[] = $part;
            }
        }
        return $out;
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function splitPanelToSheet(
        string $name,
        float $length,
        float $width,
        float $thickness,
        int $qty,
        float $sheetL,
        float $sheetW,
        string $type = 'PANEL'
    ): array {
        if ($this->fitsSheet($length, $width, $sheetL, $sheetW)) {
            return [[
                'name' => $name,
                'length_mm' => round($length, 2),
                'width_mm' => round($width, 2),
                'thickness_mm' => $thickness,
                'qty' => $qty,
                'type' => $type,
            ]];
        }

        $parts = [];
        if ($length >= $width) {
            $segments = (int) max(1, ceil($length / $sheetL));
            while ($segments < 20 && !$this->fitsSheet($length / $segments, $width, $sheetL, $sheetW)) {
                $segments++;
            }
            if (!$this->fitsSheet($length / $segments, $width, $sheetL, $sheetW)) {
                $wSegments = (int) max(1, ceil($width / $sheetW));
                while ($wSegments < 20 && !$this->fitsSheet($length / $segments, $width / $wSegments, $sheetL, $sheetW)) {
                    $wSegments++;
                }
                $partL = round($length / $segments, 2);
                $partW = round($width / $wSegments, 2);
                $parts[] = [
                    'name' => $name,
                    'length_mm' => $partL,
                    'width_mm' => $partW,
                    'thickness_mm' => $thickness,
                    'qty' => $qty * $segments * $wSegments,
                    'type' => $type,
                    'split_from' => $name,
                    'note' => "Split {$segments}x{$wSegments} for sheet fit",
                ];
                return $parts;
            }
            $partL = round($length / $segments, 2);
            $parts[] = [
                'name' => $name,
                'length_mm' => $partL,
                'width_mm' => round($width, 2),
                'thickness_mm' => $thickness,
                'qty' => $qty * $segments,
                'type' => $type,
                'split_from' => $name,
                'note' => "Split into {$segments} along length for sheet fit",
            ];
            return $parts;
        }

        $segments = (int) max(1, ceil($width / $sheetW));
        while ($segments < 20 && !$this->fitsSheet($length, $width / $segments, $sheetL, $sheetW)) {
            $segments++;
        }
        if (!$this->fitsSheet($length, $width / $segments, $sheetL, $sheetW)) {
            $lSegments = (int) max(1, ceil($length / $sheetL));
            while ($lSegments < 20 && !$this->fitsSheet($length / $lSegments, $width / $segments, $sheetL, $sheetW)) {
                $lSegments++;
            }
            return [[
                'name' => $name,
                'length_mm' => round($length / $lSegments, 2),
                'width_mm' => round($width / $segments, 2),
                'thickness_mm' => $thickness,
                'qty' => $qty * $segments * $lSegments,
                'type' => $type,
                'split_from' => $name,
                'note' => "Split {$lSegments}x{$segments} for sheet fit",
            ]];
        }

        return [[
            'name' => $name,
            'length_mm' => round($length, 2),
            'width_mm' => round($width / $segments, 2),
            'thickness_mm' => $thickness,
            'qty' => $qty * $segments,
            'type' => $type,
            'split_from' => $name,
            'note' => "Split into {$segments} along width for sheet fit",
        ]];
    }

    private function fitsSheet(float $length, float $width, float $sheetL, float $sheetW): bool
    {
        return ($length <= $sheetL && $width <= $sheetW) || ($width <= $sheetL && $length <= $sheetW);
    }

    public function refreshComponents(int $tenantId, int $furnitureId): array
    {
        $instance = $this->get($tenantId, $furnitureId);
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM furniture_templates WHERE id = ?');
        $stmt->execute([(int) $instance['template_id']]);
        $template = $stmt->fetch();
        if (!$template) {
            throw new \RuntimeException('Template not found');
        }
        $components = $this->generateComponents($template['code'], $instance['parameters'], $tenantId);
        $stmt = $pdo->prepare('UPDATE furniture_instances SET components_json = ?, width_mm=?, height_mm=?, depth_mm=?, updated_at = NOW() WHERE id = ? AND tenant_id = ?');
        $stmt->execute([
            json_encode($components),
            (float) $instance['parameters']['width'],
            (float) $instance['parameters']['height'],
            (float) $instance['parameters']['depth'],
            $furnitureId,
            $tenantId,
        ]);
        $this->syncComponentsTable($tenantId, $furnitureId, $components);
        return $this->get($tenantId, $furnitureId);
    }

    public function updateComponent(int $tenantId, int $furnitureId, int $componentId, array $data): array
    {
        $this->get($tenantId, $furnitureId);
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM furniture_components WHERE id=? AND furniture_id=? AND tenant_id=? AND deleted_at IS NULL');
        $stmt->execute([$componentId, $furnitureId, $tenantId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new \RuntimeException('Component not found');
        }

        $fields = [];
        $params = [];
        foreach (['name', 'component_type', 'status'] as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "{$col} = ?";
                $params[] = $data[$col];
            }
        }
        if (array_key_exists('quantity', $data)) {
            $fields[] = 'quantity = ?';
            $params[] = max(1, (int) $data['quantity']);
        }
        foreach (['length_mm', 'width_mm', 'thickness_mm'] as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "{$col} = ?";
                $params[] = (float) $data[$col];
            }
        }
        foreach (['material_id', 'finish_id', 'parent_component_id'] as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "{$col} = ?";
                $params[] = $data[$col] !== null && $data[$col] !== '' ? (int) $data[$col] : null;
            }
        }
        if (array_key_exists('geometry', $data)) {
            $fields[] = 'geometry_json = ?';
            $params[] = json_encode($data['geometry']);
        }
        if (array_key_exists('manufacturing_data', $data)) {
            $fields[] = 'manufacturing_data_json = ?';
            $params[] = json_encode($data['manufacturing_data']);
        }
        if ($fields === []) {
            return $this->getComponent($tenantId, $furnitureId, $componentId);
        }
        $fields[] = 'updated_at = NOW()';
        $params[] = $componentId;
        $params[] = $tenantId;
        $pdo->prepare('UPDATE furniture_components SET ' . implode(', ', $fields) . ' WHERE id = ? AND tenant_id = ?')
            ->execute($params);
        Audit::record('UPDATE', 'furniture_component', $componentId, $row, $data);
        return $this->getComponent($tenantId, $furnitureId, $componentId);
    }

    public function getComponent(int $tenantId, int $furnitureId, int $componentId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM furniture_components WHERE id=? AND furniture_id=? AND tenant_id=? AND deleted_at IS NULL');
        $stmt->execute([$componentId, $furnitureId, $tenantId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new \RuntimeException('Component not found');
        }
        $row['geometry'] = json_decode($row['geometry_json'] ?? 'null', true);
        $row['manufacturing_data'] = json_decode($row['manufacturing_data_json'] ?? 'null', true);
        unset($row['geometry_json'], $row['manufacturing_data_json']);
        return $row;
    }

    public function softDeleteComponent(int $tenantId, int $furnitureId, int $componentId): void
    {
        $this->getComponent($tenantId, $furnitureId, $componentId);
        $pdo = Database::connection();
        $pdo->prepare('UPDATE furniture_components SET deleted_at=NOW(), status=?, updated_at=NOW() WHERE id=? AND furniture_id=? AND tenant_id=?')
            ->execute(['DELETED', $componentId, $furnitureId, $tenantId]);
        Audit::record('DELETE', 'furniture_component', $componentId);
    }

    /**
     * Dual-write: JSON remains cache; table is canonical component store.
     *
     * @param list<array<string,mixed>> $components
     */
    public function syncComponentsTable(int $tenantId, int $furnitureId, array $components): void
    {
        $pdo = Database::connection();
        $pdo->prepare('UPDATE furniture_components SET deleted_at = NOW() WHERE furniture_id = ? AND tenant_id = ? AND deleted_at IS NULL')
            ->execute([$furnitureId, $tenantId]);

        $insert = $pdo->prepare('INSERT INTO furniture_components (
            tenant_id, furniture_id, parent_component_id, component_key, name, component_type, sort_order, quantity,
            length_mm, width_mm, thickness_mm, material_id, finish_id, geometry_json, manufacturing_data_json, status, created_at, updated_at
        ) VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            name=VALUES(name), component_type=VALUES(component_type), sort_order=VALUES(sort_order), quantity=VALUES(quantity),
            length_mm=VALUES(length_mm), width_mm=VALUES(width_mm), thickness_mm=VALUES(thickness_mm),
            geometry_json=VALUES(geometry_json), manufacturing_data_json=VALUES(manufacturing_data_json),
            status=VALUES(status), deleted_at=NULL, updated_at=NOW()');

        foreach ($components as $idx => $c) {
            $key = sprintf('c%02d-%s', $idx + 1, preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower((string) $c['name'])));
            $mfg = [];
            if (isset($c['split_from'])) {
                $mfg['split_from'] = $c['split_from'];
            }
            if (isset($c['note'])) {
                $mfg['note'] = $c['note'];
            }
            $insert->execute([
                $tenantId,
                $furnitureId,
                $key,
                (string) $c['name'],
                (string) ($c['type'] ?? 'PANEL'),
                $idx,
                (int) ($c['qty'] ?? 1),
                (float) ($c['length_mm'] ?? 0),
                (float) ($c['width_mm'] ?? 0),
                (float) ($c['thickness_mm'] ?? 0),
                json_encode(['source' => 'generator_v1']),
                $mfg === [] ? null : json_encode($mfg),
                'ACTIVE',
            ]);
        }
    }

    /** @return array{length_mm:float|int|string,width_mm:float|int|string} */
    private function resolveDefaultSheet(?int $tenantId): array
    {
        if ($tenantId === null) {
            return ['length_mm' => 2440, 'width_mm' => 1220];
        }
        $pdo = Database::connection();
        $stmt = $pdo->prepare("SELECT length_mm, width_mm FROM sheet_definitions WHERE tenant_id=? AND status='ACTIVE' ORDER BY is_default DESC, id ASC LIMIT 1");
        $stmt->execute([$tenantId]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
        return ['length_mm' => 2440, 'width_mm' => 1220];
    }

    private function hydrate(array $row): array
    {
        $row['parameters'] = json_decode($row['parameter_values_json'], true);
        $row['position'] = json_decode($row['position_json'], true);
        $row['components'] = json_decode($row['components_json'], true);
        $row['stale_flags'] = json_decode($row['stale_flags_json'] ?? 'null', true);
        $row['specification'] = json_decode($row['specification_json'] ?? 'null', true) ?: new \stdClass();
        unset($row['parameter_values_json'], $row['position_json'], $row['components_json'], $row['stale_flags_json'], $row['specification_json']);
        return $row;
    }
}
