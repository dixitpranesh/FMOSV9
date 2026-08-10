<?php

declare(strict_types=1);

namespace Fmos\Domains\Furniture;

use Fmos\Core\Database;
use Fmos\Domains\Catalog\MaterialService;

final class FurnitureViewService
{
    public function drawing2d(int $tenantId, int $furnitureId, string $view = 'FRONT'): array
    {
        $engine = new FurnitureEngine();
        $furniture = $engine->get($tenantId, $furnitureId);
        $view = strtoupper($view);
        $w = (float) ($furniture['width_mm'] ?? $furniture['parameters']['width'] ?? 0);
        $h = (float) ($furniture['height_mm'] ?? $furniture['parameters']['height'] ?? 0);
        $d = (float) ($furniture['depth_mm'] ?? $furniture['parameters']['depth'] ?? 0);
        $t = (float) ($furniture['parameters']['carcass_thickness'] ?? 18);

        $elements = [];
        $dimensions = [];

        if (in_array($view, ['FRONT', 'INTERNAL', 'BACK'], true)) {
            $elements[] = ['type' => 'rect', 'x' => 0, 'y' => 0, 'w' => $w, 'h' => $h, 'label' => 'Carcass', 'role' => 'outer'];
            $elements[] = ['type' => 'rect', 'x' => $t, 'y' => $t, 'w' => max(0, $w - 2 * $t), 'h' => max(0, $h - 2 * $t), 'label' => 'Internal', 'role' => 'inner'];
            $shutters = (int) ($furniture['parameters']['shutter_count'] ?? 1);
            $innerW = max(0, $w - 2 * $t);
            $sw = $shutters > 0 ? $innerW / $shutters : $innerW;
            for ($i = 0; $i < $shutters; $i++) {
                $elements[] = [
                    'type' => 'rect',
                    'x' => $t + ($i * $sw),
                    'y' => $t,
                    'w' => $sw,
                    'h' => max(0, $h - 2 * $t),
                    'label' => 'Shutter ' . ($i + 1),
                    'role' => 'shutter',
                ];
            }
            $shelves = (int) ($furniture['parameters']['shelf_count'] ?? 0);
            if ($shelves > 0) {
                $span = max(0, $h - 2 * $t);
                for ($i = 1; $i <= $shelves; $i++) {
                    $y = $t + ($span * $i / ($shelves + 1));
                    $elements[] = ['type' => 'line', 'x1' => $t, 'y1' => $y, 'x2' => $w - $t, 'y2' => $y, 'label' => 'Shelf', 'role' => 'shelf'];
                }
            }
            $dimensions[] = ['axis' => 'H', 'value' => $w, 'from' => [0, -$h * 0.08], 'to' => [$w, -$h * 0.08], 'label' => (string) $w];
            $dimensions[] = ['axis' => 'V', 'value' => $h, 'from' => [-$w * 0.08, 0], 'to' => [-$w * 0.08, $h], 'label' => (string) $h];
        } elseif ($view === 'PLAN') {
            $elements[] = ['type' => 'rect', 'x' => 0, 'y' => 0, 'w' => $w, 'h' => $d, 'label' => 'Plan', 'role' => 'outer'];
            $dimensions[] = ['axis' => 'H', 'value' => $w, 'from' => [0, -$d * 0.12], 'to' => [$w, -$d * 0.12], 'label' => (string) $w];
            $dimensions[] = ['axis' => 'V', 'value' => $d, 'from' => [-$w * 0.08, 0], 'to' => [-$w * 0.08, $d], 'label' => (string) $d];
        } else { // LEFT / RIGHT / SECTION
            $elements[] = ['type' => 'rect', 'x' => 0, 'y' => 0, 'w' => $d, 'h' => $h, 'label' => $view, 'role' => 'outer'];
            $dimensions[] = ['axis' => 'H', 'value' => $d, 'from' => [0, -$h * 0.08], 'to' => [$d, -$h * 0.08], 'label' => (string) $d];
            $dimensions[] = ['axis' => 'V', 'value' => $h, 'from' => [-$d * 0.12, 0], 'to' => [-$d * 0.12, $h], 'label' => (string) $h];
        }

        $pdo = Database::connection();
        $project = $pdo->prepare('SELECT p.name AS project_name, c.name AS client_name FROM projects p LEFT JOIN clients c ON c.id=p.client_id WHERE p.id=? AND p.tenant_id=?');
        $project->execute([(int) $furniture['project_id'], $tenantId]);
        $meta = $project->fetch() ?: ['project_name' => '', 'client_name' => ''];

        return [
            'view' => $view,
            'unit' => 'mm',
            'bounds' => ['width' => $w, 'height' => $h, 'depth' => $d],
            'elements' => $elements,
            'dimensions' => $dimensions,
            'title_block' => [
                'project' => $meta['project_name'],
                'client' => $meta['client_name'],
                'furniture' => $furniture['name'],
                'code' => $furniture['code'],
                'revision' => (int) $furniture['revision'],
                'view' => $view,
            ],
        ];
    }

    public function model3d(int $tenantId, int $furnitureId): array
    {
        $engine = new FurnitureEngine();
        $furniture = $engine->get($tenantId, $furnitureId);
        $matSvc = new MaterialService();
        $finishCache = [];

        $resolveFinish = function (?int $finishId) use ($tenantId, $matSvc, &$finishCache): ?array {
            if (!$finishId) {
                return null;
            }
            if (!isset($finishCache[$finishId])) {
                try {
                    $m = $matSvc->get($tenantId, $finishId);
                    $url = null;
                    foreach ($m['assets'] ?? [] as $a) {
                        if (($a['asset_type'] ?? '') === 'TEXTURE_ALBEDO') {
                            $url = $a['public_url'];
                            break;
                        }
                    }
                    $finishCache[$finishId] = [
                        'id' => $finishId,
                        'sku' => $m['sku'],
                        'texture_url' => $url,
                        'roughness' => (float) $m['default_roughness'],
                        'metalness' => (float) $m['default_metalness'],
                    ];
                } catch (\Throwable) {
                    $finishCache[$finishId] = null;
                }
            }
            return $finishCache[$finishId];
        };

        $exterior = $resolveFinish($furniture['exterior_finish_id'] !== null ? (int) $furniture['exterior_finish_id'] : null);
        $interior = $resolveFinish($furniture['interior_finish_id'] !== null ? (int) $furniture['interior_finish_id'] : null);

        $w = (float) ($furniture['width_mm'] ?? 0);
        $h = (float) ($furniture['height_mm'] ?? 0);
        $d = (float) ($furniture['depth_mm'] ?? 0);
        $t = (float) ($furniture['parameters']['carcass_thickness'] ?? 18);
        $meshes = [];

        $meshes[] = $this->box('Left Side', $t, $h, $d, $t / 2, $h / 2, $d / 2, $interior ?: $exterior, 'carcass');
        $meshes[] = $this->box('Right Side', $t, $h, $d, $w - $t / 2, $h / 2, $d / 2, $interior ?: $exterior, 'carcass');
        $meshes[] = $this->box('Top', max(1, $w - 2 * $t), $t, $d, $w / 2, $h - $t / 2, $d / 2, $interior ?: $exterior, 'carcass');
        $meshes[] = $this->box('Bottom', max(1, $w - 2 * $t), $t, $d, $w / 2, $t / 2, $d / 2, $interior ?: $exterior, 'carcass');
        $backT = (float) ($furniture['parameters']['back_thickness'] ?? 6);
        $meshes[] = $this->box('Back', $w, $h, $backT, $w / 2, $h / 2, $backT / 2, $interior ?: $exterior, 'carcass');

        $shelves = (int) ($furniture['parameters']['shelf_count'] ?? 0);
        $innerH = max(1, $h - 2 * $t);
        for ($i = 1; $i <= $shelves; $i++) {
            $y = $t + ($innerH * $i / ($shelves + 1));
            $meshes[] = $this->box('Shelf ' . $i, max(1, $w - 2 * $t), $t, max(1, $d - $t), $w / 2, $y, $d / 2, $interior ?: $exterior, 'shelf');
        }

        $shutters = max(1, (int) ($furniture['parameters']['shutter_count'] ?? 1));
        $innerW = max(1, $w - 2 * $t);
        $sw = $innerW / $shutters;
        $componentRows = $furniture['component_rows'] ?? [];
        for ($i = 0; $i < $shutters; $i++) {
            $finish = $exterior;
            foreach ($componentRows as $row) {
                if (stripos((string) $row['name'], 'Shutter') !== false && !empty($row['finish_id'])) {
                    // First shutter uses first shutter finish override if any; simple: any shutter finish overrides all for MVP if one set
                    $finish = $resolveFinish((int) $row['finish_id']) ?: $finish;
                }
            }
            // Prefer matching shutter component by index if present
            $shutterRows = array_values(array_filter($componentRows, static fn ($r) => stripos((string) $r['name'], 'Shutter') !== false));
            if (isset($shutterRows[$i]) && !empty($shutterRows[$i]['finish_id'])) {
                $finish = $resolveFinish((int) $shutterRows[$i]['finish_id']) ?: $finish;
            }
            $meshes[] = $this->box(
                'Shutter ' . ($i + 1),
                $sw - 2,
                max(1, $h - 2 * $t),
                $t,
                $t + $sw * $i + $sw / 2,
                $h / 2,
                $d - $t / 2,
                $finish,
                'shutter'
            );
        }

        return [
            'furniture_id' => $furnitureId,
            'bounds' => ['width' => $w, 'height' => $h, 'depth' => $d],
            'default_exterior' => $exterior,
            'default_interior' => $interior,
            'meshes' => $meshes,
        ];
    }

    private function box(string $name, float $sx, float $sy, float $sz, float $x, float $y, float $z, ?array $finish, string $role): array
    {
        return [
            'name' => $name,
            'role' => $role,
            'size' => [round($sx, 2), round($sy, 2), round($sz, 2)],
            'position' => [round($x, 2), round($y, 2), round($z, 2)],
            'finish' => $finish,
            'color' => $finish ? null : ($role === 'shutter' ? '#c9d6df' : '#e6ebf0'),
        ];
    }
}
