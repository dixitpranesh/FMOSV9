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

        $logical = [
            ['name' => 'Left Side', 'length_mm' => $h, 'width_mm' => $d, 'thickness_mm' => $t, 'qty' => 1],
            ['name' => 'Right Side', 'length_mm' => $h, 'width_mm' => $d, 'thickness_mm' => $t, 'qty' => 1],
            ['name' => 'Top', 'length_mm' => $internalW, 'width_mm' => $d, 'thickness_mm' => $t, 'qty' => 1],
            ['name' => 'Bottom', 'length_mm' => $internalW, 'width_mm' => $d, 'thickness_mm' => $t, 'qty' => 1],
            ['name' => 'Back', 'length_mm' => $h, 'width_mm' => $w, 'thickness_mm' => (float) ($p['back_thickness'] ?? 6), 'qty' => 1],
        ];

        if ($shelfCount > 0) {
            $logical[] = ['name' => 'Shelf', 'length_mm' => $internalW, 'width_mm' => max(1, $d - $t), 'thickness_mm' => $t, 'qty' => $shelfCount];
        }

        $shutterW = $internalW / max(1, $shutterCount);
        $logical[] = ['name' => 'Shutter', 'length_mm' => $h, 'width_mm' => $shutterW, 'thickness_mm' => $t, 'qty' => $shutterCount];
        $logical[] = ['name' => 'Hinge', 'length_mm' => 0, 'width_mm' => 0, 'thickness_mm' => 0, 'qty' => $shutterCount * 2, 'type' => 'HARDWARE'];

        return $this->normalizeToSheet($logical);
    }

    /**
     * Split oversized panels so each part fits a standard 2440 x 1220 sheet (with rotation).
     *
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
                $sheetW
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
        float $sheetW
    ): array {
        if ($this->fitsSheet($length, $width, $sheetL, $sheetW)) {
            return [[
                'name' => $name,
                'length_mm' => round($length, 2),
                'width_mm' => round($width, 2),
                'thickness_mm' => $thickness,
                'qty' => $qty,
            ]];
        }

        // Prefer splitting the longer edge into equal parts that fit.
        $parts = [];
        if ($length >= $width) {
            $segments = (int) max(1, ceil($length / $sheetL));
            // Also ensure each segment rotated/unrotated fits width constraint
            while ($segments < 20 && !$this->fitsSheet($length / $segments, $width, $sheetL, $sheetW)) {
                $segments++;
            }
            // If still can't fit due to secondary dim > both sheet dims, split secondary too
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
            'split_from' => $name,
            'note' => "Split into {$segments} along width for sheet fit",
        ]];
    }

    private function fitsSheet(float $length, float $width, float $sheetL, float $sheetW): bool
    {
        return ($length <= $sheetL && $width <= $sheetW) || ($width <= $sheetL && $length <= $sheetW);
    }

    /**
     * Rebuild manufacturable components from current parameters (used by manufacturing generate).
     */
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
        $components = $this->generateComponents($template['code'], $instance['parameters']);
        $stmt = $pdo->prepare('UPDATE furniture_instances SET components_json = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?');
        $stmt->execute([json_encode($components), $furnitureId, $tenantId]);
        return $this->get($tenantId, $furnitureId);
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
