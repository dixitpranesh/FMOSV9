<?php

declare(strict_types=1);

namespace Fmos\Domains\Furniture;

/**
 * Places kitchen modules in world space for L / straight compositions.
 *
 * Plan axes: X right, Z depth into room. Rotation is degrees about Y.
 * Corner sits at origin; Run A continues along +X; Run B along +Z.
 */
final class KitchenPlacement
{
    /**
     * @param list<array{furniture_id?:int,run:string,role?:string,sort?:int,width_mm:float,depth_mm:float,height_mm:float}> $modules
     * @return list<array{run:string,role:string,sort:int,width_mm:float,depth_mm:float,height_mm:float,furniture_id?:int,position:array{x:float,y:float,z:float,rotation:float}}>
     */
    public static function placeL(array $modules, float $cornerSize, float $runDepth): array
    {
        $cornerSize = max(1.0, $cornerSize);
        $runDepth = max(1.0, $runDepth);
        $runA = [];
        $runB = [];
        $corner = null;
        foreach ($modules as $m) {
            $run = strtoupper((string) ($m['run'] ?? 'A'));
            $item = [
                'run' => $run,
                'role' => (string) ($m['role'] ?? 'base'),
                'sort' => (int) ($m['sort'] ?? 0),
                'width_mm' => (float) ($m['width_mm'] ?? $runDepth),
                'depth_mm' => (float) ($m['depth_mm'] ?? $runDepth),
                'height_mm' => (float) ($m['height_mm'] ?? 720),
            ];
            if (isset($m['furniture_id'])) {
                $item['furniture_id'] = (int) $m['furniture_id'];
            }
            if ($run === 'CORNER') {
                $corner = $item;
            } elseif ($run === 'B') {
                $runB[] = $item;
            } else {
                $runA[] = $item;
            }
        }
        usort($runA, static fn ($a, $b) => $a['sort'] <=> $b['sort']);
        usort($runB, static fn ($a, $b) => $a['sort'] <=> $b['sort']);

        $out = [];
        if ($corner !== null) {
            $corner['width_mm'] = $cornerSize;
            $corner['depth_mm'] = $cornerSize;
            $corner['position'] = ['x' => 0.0, 'y' => 0.0, 'z' => 0.0, 'rotation' => 0.0];
            $out[] = $corner;
        }

        $x = $cornerSize;
        foreach ($runA as $i => $mod) {
            $mod['sort'] = $i;
            $mod['depth_mm'] = $runDepth;
            $mod['position'] = [
                'x' => $x,
                'y' => 0.0,
                'z' => 0.0,
                'rotation' => 0.0,
            ];
            $x += $mod['width_mm'];
            $out[] = $mod;
        }

        $z = $cornerSize;
        foreach ($runB as $i => $mod) {
            $mod['sort'] = $i;
            $mod['depth_mm'] = $runDepth;
            // Rotate 90° CCW: local +X → world +Z, local +Z → world −X.
            // Anchor at (runDepth, corner+) so the footprint occupies x∈[0,depth], z∈[C,C+w].
            $mod['position'] = [
                'x' => $runDepth,
                'y' => 0.0,
                'z' => $z,
                'rotation' => 90.0,
            ];
            $z += $mod['width_mm'];
            $out[] = $mod;
        }

        return $out;
    }

    /**
     * Split a run length into module widths (preferred size + remainder).
     *
     * @return list<float>
     */
    public static function splitRun(float $lengthMm, float $preferredWidth = 600.0): array
    {
        $lengthMm = max(0.0, $lengthMm);
        $preferredWidth = max(300.0, $preferredWidth);
        if ($lengthMm < 1.0) {
            return [];
        }
        if ($lengthMm <= $preferredWidth + 50) {
            return [round($lengthMm, 2)];
        }
        $n = max(1, (int) floor($lengthMm / $preferredWidth));
        $widths = array_fill(0, $n, $preferredWidth);
        $used = $preferredWidth * $n;
        $remain = round($lengthMm - $used, 2);
        if ($remain >= 300) {
            $widths[] = $remain;
        } elseif ($remain > 0.5) {
            $widths[$n - 1] = round($widths[$n - 1] + $remain, 2);
        }
        return $widths;
    }

    /**
     * World AABB for placed modules (plan footprint).
     *
     * @param list<array{width_mm:float,depth_mm:float,position:array{x:float,y:float,z:float,rotation:float}}> $placed
     * @return array{min_x:float,max_x:float,min_z:float,max_z:float,width:float,depth:float}
     */
    public static function bounds(array $placed): array
    {
        $minX = 0.0;
        $maxX = 0.0;
        $minZ = 0.0;
        $maxZ = 0.0;
        $first = true;
        foreach ($placed as $m) {
            $w = (float) $m['width_mm'];
            $d = (float) $m['depth_mm'];
            $x = (float) $m['position']['x'];
            $z = (float) $m['position']['z'];
            $rot = (float) ($m['position']['rotation'] ?? 0);
            if (abs($rot) > 45) {
                // 90° with origin at (depth, z): footprint x∈[origin.x−depth, origin.x], z∈[origin.z, origin.z+width]
                $x0 = $x - $d;
                $x1 = $x;
                $z0 = $z;
                $z1 = $z + $w;
            } else {
                $x0 = $x;
                $x1 = $x + $w;
                $z0 = $z;
                $z1 = $z + $d;
            }
            if ($first) {
                $minX = $x0;
                $maxX = $x1;
                $minZ = $z0;
                $maxZ = $z1;
                $first = false;
            } else {
                $minX = min($minX, $x0);
                $maxX = max($maxX, $x1);
                $minZ = min($minZ, $z0);
                $maxZ = max($maxZ, $z1);
            }
        }
        return [
            'min_x' => $minX,
            'max_x' => $maxX,
            'min_z' => $minZ,
            'max_z' => $maxZ,
            'width' => max(1.0, $maxX - $minX),
            'depth' => max(1.0, $maxZ - $minZ),
        ];
    }
}
