<?php

declare(strict_types=1);

namespace Fmos\Domains\Furniture;

use Fmos\Core\Audit;
use Fmos\Core\Database;

/**
 * Composes multiple kitchen furniture instances into an L-shaped (or straight) layout.
 * Manufacturing stays per-instance; this owns placement + aggregated views only.
 */
final class KitchenCompositionService
{
    public function __construct(
        private readonly FurnitureEngine $engine = new FurnitureEngine(),
        private readonly FurnitureViewService $views = new FurnitureViewService()
    ) {
    }

    /** @return list<array<string,mixed>> */
    public function listByProject(int $tenantId, int $projectId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT * FROM kitchen_compositions WHERE tenant_id=? AND project_id=? AND deleted_at IS NULL ORDER BY id DESC'
        );
        $stmt->execute([$tenantId, $projectId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function get(int $tenantId, int $id): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT * FROM kitchen_compositions WHERE id=? AND tenant_id=? AND deleted_at IS NULL'
        );
        $stmt->execute([$id, $tenantId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new \RuntimeException('Kitchen composition not found');
        }
        return $this->hydrate($row);
    }

    /**
     * Create L-shape by spawning kitchen module instances + corner, then placing them.
     *
     * @param array<string,mixed> $data
     */
    public function createLShape(int $tenantId, int $projectId, array $data): array
    {
        $name = trim((string) ($data['name'] ?? 'L Kitchen Base'));
        $height = (float) ($data['height_mm'] ?? 720);
        $depth = (float) ($data['depth_mm'] ?? 560);
        $cornerSize = (float) ($data['corner_size_mm'] ?? 900);
        $preferred = (float) ($data['module_width_mm'] ?? 600);
        $finishes = [
            'exterior_finish_id' => $data['exterior_finish_id'] ?? null,
            'interior_finish_id' => $data['interior_finish_id'] ?? null,
            'material_id' => $data['material_id'] ?? null,
        ];

        $defaultPreset = strtolower((string) ($data['default_module_preset'] ?? 'shelf'));
        if (!in_array($defaultPreset, ['shelf', 'drawers', 'sink', 'open'], true)) {
            $defaultPreset = 'shelf';
        }

        $runASpecs = $data['run_a_modules'] ?? null;
        $runBSpecs = $data['run_b_modules'] ?? null;
        if (!is_array($runASpecs) || $runASpecs === []) {
            $lenA = (float) ($data['run_a_length_mm'] ?? 1800);
            $runASpecs = array_map(
                static fn (float $w) => ['width_mm' => $w, 'preset' => $defaultPreset],
                KitchenPlacement::splitRun($lenA, $preferred)
            );
        }
        if (!is_array($runBSpecs) || $runBSpecs === []) {
            $lenB = (float) ($data['run_b_length_mm'] ?? 1200);
            $runBSpecs = array_map(
                static fn (float $w) => ['width_mm' => $w, 'preset' => $defaultPreset],
                KitchenPlacement::splitRun($lenB, $preferred)
            );
        }

        $modulesMeta = [];
        $sort = 0;
        foreach ($runASpecs as $spec) {
            $inst = $this->spawnModule($tenantId, $projectId, $spec, $height, $depth, $finishes, 'A', $sort);
            $modulesMeta[] = [
                'furniture_id' => (int) $inst['id'],
                'run' => 'A',
                'role' => (string) ($spec['preset'] ?? 'base'),
                'sort' => $sort,
                'width_mm' => (float) $inst['width_mm'],
                'depth_mm' => (float) $inst['depth_mm'],
                'height_mm' => (float) $inst['height_mm'],
            ];
            $sort++;
        }

        $corner = $this->spawnCorner($tenantId, $projectId, $cornerSize, $height, $finishes);
        $modulesMeta[] = [
            'furniture_id' => (int) $corner['id'],
            'run' => 'CORNER',
            'role' => 'corner',
            'sort' => 0,
            'width_mm' => $cornerSize,
            'depth_mm' => $cornerSize,
            'height_mm' => (float) $corner['height_mm'],
        ];

        $sort = 0;
        foreach ($runBSpecs as $spec) {
            $inst = $this->spawnModule($tenantId, $projectId, $spec, $height, $depth, $finishes, 'B', $sort);
            $modulesMeta[] = [
                'furniture_id' => (int) $inst['id'],
                'run' => 'B',
                'role' => (string) ($spec['preset'] ?? 'base'),
                'sort' => $sort,
                'width_mm' => (float) $inst['width_mm'],
                'depth_mm' => (float) $inst['depth_mm'],
                'height_mm' => (float) $inst['height_mm'],
            ];
            $sort++;
        }

        $placed = KitchenPlacement::placeL($modulesMeta, $cornerSize, $depth);
        foreach ($placed as $p) {
            if (!empty($p['furniture_id'])) {
                $this->engine->updatePosition($tenantId, (int) $p['furniture_id'], $p['position']);
            }
        }

        $pdo = Database::connection();
        $pdo->prepare(
            'INSERT INTO kitchen_compositions
            (tenant_id, project_id, name, shape, height_mm, depth_mm, corner_size_mm, modules_json, meta_json, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        )->execute([
            $tenantId,
            $projectId,
            $name,
            'L',
            $height,
            $depth,
            $cornerSize,
            json_encode(array_map(static function (array $p): array {
                return [
                    'furniture_id' => $p['furniture_id'] ?? null,
                    'run' => $p['run'],
                    'role' => $p['role'],
                    'sort' => $p['sort'],
                    'width_mm' => $p['width_mm'],
                    'depth_mm' => $p['depth_mm'],
                    'height_mm' => $p['height_mm'],
                    'position' => $p['position'],
                ];
            }, $placed)),
            json_encode(['source' => 'createLShape']),
        ]);
        $id = (int) $pdo->lastInsertId();
        Audit::record('CREATE', 'kitchen_composition', $id, null, ['project_id' => $projectId, 'shape' => 'L']);
        return $this->get($tenantId, $id);
    }

    public function softDelete(int $tenantId, int $id): void
    {
        $comp = $this->get($tenantId, $id);
        $pdo = Database::connection();
        $pdo->prepare('UPDATE kitchen_compositions SET deleted_at=NOW(), updated_at=NOW() WHERE id=? AND tenant_id=?')
            ->execute([$id, $tenantId]);
        Audit::record('DELETE', 'kitchen_composition', $id, $comp, ['deleted' => true]);
    }

    /**
     * Aggregated PLAN drawing for the composition (modules as placed rectangles).
     *
     * @return array<string,mixed>
     */
    public function drawingPlan(int $tenantId, int $compositionId): array
    {
        $comp = $this->get($tenantId, $compositionId);
        $modules = $comp['modules'] ?? [];
        $placed = [];
        foreach ($modules as $m) {
            if (empty($m['position'])) {
                continue;
            }
            $placed[] = $m;
        }
        $bounds = KitchenPlacement::bounds($placed);
        $elements = [];
        foreach ($placed as $m) {
            $fid = (int) ($m['furniture_id'] ?? 0);
            $label = strtoupper((string) ($m['run'] ?? '')) . ' ' . (string) ($m['role'] ?? 'module');
            if ($fid > 0) {
                try {
                    $f = $this->engine->get($tenantId, $fid);
                    $label = (string) ($f['code'] ?: $f['name'] ?: $label);
                } catch (\Throwable) {
                    // keep fallback label
                }
            }
            $rot = (float) ($m['position']['rotation'] ?? 0);
            $w = (float) $m['width_mm'];
            $d = (float) $m['depth_mm'];
            $x = (float) $m['position']['x'];
            $z = (float) $m['position']['z'];
            if (abs($rot) > 45) {
                $elements[] = [
                    'type' => 'rect',
                    'x' => $x - $d,
                    'y' => $z,
                    'w' => $d,
                    'h' => $w,
                    'label' => $label,
                    'role' => strtolower((string) ($m['role'] ?? 'base')) === 'corner' ? 'corner' : 'bay',
                    'furniture_id' => $fid,
                    'run' => $m['run'] ?? null,
                ];
            } else {
                $elements[] = [
                    'type' => 'rect',
                    'x' => $x,
                    'y' => $z,
                    'w' => $w,
                    'h' => $d,
                    'label' => $label,
                    'role' => strtolower((string) ($m['role'] ?? 'base')) === 'corner' ? 'corner' : 'bay',
                    'furniture_id' => $fid,
                    'run' => $m['run'] ?? null,
                ];
            }
        }

        return [
            'view' => 'PLAN',
            'unit' => 'mm',
            'composition_id' => $compositionId,
            'shape' => $comp['shape'],
            'bounds' => [
                'width' => $bounds['width'],
                'height' => $comp['height_mm'],
                'depth' => $bounds['depth'],
                'min_x' => $bounds['min_x'],
                'max_x' => $bounds['max_x'],
                'min_z' => $bounds['min_z'],
                'max_z' => $bounds['max_z'],
            ],
            'elements' => $elements,
            'dimensions' => [
                [
                    'axis' => 'H',
                    'value' => $bounds['width'],
                    'from' => [$bounds['min_x'], $bounds['min_z'] - 120],
                    'to' => [$bounds['max_x'], $bounds['min_z'] - 120],
                    'label' => 'Overall ' . (string) (int) round($bounds['width']),
                ],
                [
                    'axis' => 'V',
                    'value' => $bounds['depth'],
                    'from' => [$bounds['min_x'] - 120, $bounds['min_z']],
                    'to' => [$bounds['min_x'] - 120, $bounds['max_z']],
                    'label' => (string) (int) round($bounds['depth']),
                ],
            ],
            'furniture_ids' => array_values(array_filter(array_map(
                static fn ($m) => (int) ($m['furniture_id'] ?? 0),
                $modules
            ))),
            'title_block' => [
                'project' => '',
                'client' => '',
                'furniture' => $comp['name'],
                'code' => 'KITCHEN-L-' . $compositionId,
                'revision' => 1,
                'view' => 'PLAN',
            ],
        ];
    }

    /**
     * Aggregated 3D meshes with world transforms applied.
     *
     * @return array<string,mixed>
     */
    public function model3d(int $tenantId, int $compositionId): array
    {
        $comp = $this->get($tenantId, $compositionId);
        $meshes = [];
        $maxH = (float) $comp['height_mm'];
        foreach ($comp['modules'] as $m) {
            $fid = (int) ($m['furniture_id'] ?? 0);
            if ($fid <= 0 || empty($m['position'])) {
                continue;
            }
            $local = $this->views->model3d($tenantId, $fid);
            $maxH = max($maxH, (float) ($local['bounds']['height'] ?? 0));
            $ox = (float) $m['position']['x'];
            $oy = (float) $m['position']['y'];
            $oz = (float) $m['position']['z'];
            $rot = deg2rad((float) ($m['position']['rotation'] ?? 0));
            $c = cos($rot);
            $s = sin($rot);
            $rotDeg = (float) ($m['position']['rotation'] ?? 0);
            foreach ($local['meshes'] ?? [] as $mesh) {
                $px = (float) $mesh['position'][0];
                $py = (float) $mesh['position'][1];
                $pz = (float) $mesh['position'][2];
                $wx = $ox + ($px * $c - $pz * $s);
                $wz = $oz + ($px * $s + $pz * $c);
                $mesh['position'] = [round($wx, 2), round($py + $oy, 2), round($wz, 2)];
                // Placement degrees (CCW about Y); Three.js applies the negated yaw.
                $mesh['rotation_y'] = $rotDeg;
                $mesh['name'] = ($m['run'] ?? '') . ':' . ($mesh['name'] ?? 'part');
                $mesh['composition_furniture_id'] = $fid;
                $meshes[] = $mesh;
            }
        }
        $bounds = KitchenPlacement::bounds($comp['modules']);
        return [
            'composition_id' => $compositionId,
            'shape' => $comp['shape'],
            'bounds' => [
                'width' => $bounds['width'],
                'height' => $maxH,
                'depth' => $bounds['depth'],
                'min_x' => $bounds['min_x'],
                'max_x' => $bounds['max_x'],
                'min_z' => $bounds['min_z'],
                'max_z' => $bounds['max_z'],
                'filler_left' => 0,
                'filler_right' => 0,
            ],
            'meshes' => $meshes,
            'furniture_ids' => array_values(array_filter(array_map(
                static fn ($m) => (int) ($m['furniture_id'] ?? 0),
                $comp['modules']
            ))),
            'expo' => [],
        ];
    }

    /** @param array<string,mixed> $spec */
    private function spawnModule(
        int $tenantId,
        int $projectId,
        array $spec,
        float $height,
        float $depth,
        array $finishes,
        string $run,
        int $sort
    ): array {
        $width = max(300.0, (float) ($spec['width_mm'] ?? 600));
        $preset = strtolower((string) ($spec['preset'] ?? 'shelf'));
        $configId = InternalConfigCatalog::configIdForKitchenPreset($preset);
        $layout = match ($preset) {
            'drawers' => [
                'plinth_height_mm' => 100,
                'partition_thickness_mm' => 18,
                'door_type' => 'NONE',
                'loft' => ['enabled' => false, 'height_mm' => 0, 'shelf_count' => 0],
                'bays' => [[
                    'id' => 'bay-1',
                    'label' => 'Drawers',
                    'width_mm' => null,
                    'sections' => [[
                        'type' => 'DRAWERS',
                        'height_mm' => null,
                        'drawer_count' => (int) ($spec['drawer_count'] ?? 3),
                        'drawer_height_mm' => 180,
                        'label' => 'Drawers',
                    ]],
                ]],
            ],
            'sink', 'open' => [
                'plinth_height_mm' => 100,
                'partition_thickness_mm' => 18,
                'door_type' => 'HINGED',
                'loft' => ['enabled' => false, 'height_mm' => 0, 'shelf_count' => 0],
                'bays' => [[
                    'id' => 'bay-1',
                    'label' => 'Sink',
                    'width_mm' => null,
                    'sections' => [
                        ['type' => 'OPEN', 'height_mm' => null, 'label' => 'Plumbing'],
                    ],
                ]],
            ],
            default => FurnitureTemplateCatalog::defaultKitchenBaseLayout(),
        };
        $doorType = $layout['door_type'] ?? 'HINGED';
        $shutters = $preset === 'drawers' ? 0 : 1;

        return $this->engine->createInstance($tenantId, array_filter([
            'project_id' => $projectId,
            'template_code' => 'KITCHEN_BASE',
            'name' => 'Kitchen ' . $run . ($sort + 1) . ' (' . $preset . ')',
            'code' => null,
            'width' => $width,
            'height' => $height,
            'depth' => $depth,
            'quantity' => 1,
            'exterior_finish_id' => $finishes['exterior_finish_id'] ?? null,
            'interior_finish_id' => $finishes['interior_finish_id'] ?? null,
            'material_id' => $finishes['material_id'] ?? null,
            'parameters' => [
                'width' => $width,
                'height' => $height,
                'depth' => $depth,
                'carcass_thickness' => 18,
                'back_thickness' => 18,
                'shutter_count' => $shutters,
                'door_type' => $doorType,
                'layout' => $layout,
                'internal_config_id' => $configId,
            ],
        ], static fn ($v) => $v !== null));
    }

    private function spawnCorner(
        int $tenantId,
        int $projectId,
        float $cornerSize,
        float $height,
        array $finishes
    ): array {
        return $this->engine->createInstance($tenantId, array_filter([
            'project_id' => $projectId,
            'template_code' => 'KITCHEN_CORNER',
            'name' => 'Kitchen Corner',
            'width' => $cornerSize,
            'height' => $height,
            'depth' => $cornerSize,
            'quantity' => 1,
            'exterior_finish_id' => $finishes['exterior_finish_id'] ?? null,
            'interior_finish_id' => $finishes['interior_finish_id'] ?? null,
            'material_id' => $finishes['material_id'] ?? null,
            'parameters' => [
                'width' => $cornerSize,
                'height' => $height,
                'depth' => $cornerSize,
                'carcass_thickness' => 18,
                'back_thickness' => 18,
                'shutter_count' => 1,
                'door_type' => 'HINGED',
                'layout' => FurnitureTemplateCatalog::defaultKitchenCornerLayout(),
            ],
        ], static fn ($v) => $v !== null));
    }

    /** @param array<string,mixed> $row */
    private function hydrate(array $row): array
    {
        $row['modules'] = json_decode($row['modules_json'] ?? '[]', true) ?: [];
        $row['meta'] = json_decode($row['meta_json'] ?? 'null', true);
        $row['height_mm'] = (float) $row['height_mm'];
        $row['depth_mm'] = (float) $row['depth_mm'];
        $row['corner_size_mm'] = (float) $row['corner_size_mm'];
        $row['furniture_ids'] = array_values(array_filter(array_map(
            static fn ($m) => (int) ($m['furniture_id'] ?? 0),
            $row['modules']
        )));
        unset($row['modules_json'], $row['meta_json']);
        return $row;
    }
}
