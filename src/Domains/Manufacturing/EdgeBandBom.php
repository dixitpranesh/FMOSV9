<?php

declare(strict_types=1);

namespace Fmos\Domains\Manufacturing;

use Fmos\Core\Database;
use Fmos\Domains\Catalog\CatalogService;

/**
 * Edge-band length BOM from panel finishing sizes + per-side edge thickness.
 * Shop convention (same as cutting size): edges 1/2 along length, 3/4 along width.
 * Does not invent CNC machine tables — meters only for procurement/BOM.
 */
final class EdgeBandBom
{
    /**
     * Linear meters for one panel line (finishing size × qty × edged sides).
     *
     * @param array{edge_1?:float|int,edge_2?:float|int,edge_3?:float|int,edge_4?:float|int} $edges
     */
    public static function metersForPanel(
        float $finishingLengthMm,
        float $finishingWidthMm,
        float $quantity,
        array $edges
    ): float {
        $len = 0.0;
        if (((float) ($edges['edge_1'] ?? 0)) > 0) {
            $len += $finishingLengthMm;
        }
        if (((float) ($edges['edge_2'] ?? 0)) > 0) {
            $len += $finishingLengthMm;
        }
        if (((float) ($edges['edge_3'] ?? 0)) > 0) {
            $len += $finishingWidthMm;
        }
        if (((float) ($edges['edge_4'] ?? 0)) > 0) {
            $len += $finishingWidthMm;
        }
        return round(($len * max(0.0, $quantity)) / 1000.0, 4);
    }

    /**
     * Aggregate edge meters from manufacturing panels rows.
     *
     * @param list<array<string,mixed>> $panels
     * @return array{meters:float,sku:string,catalog_product_id:?int,name:string,unit_cost:float,uom:string,panel_count:int}
     */
    public static function aggregateFromPanels(int $tenantId, array $panels): array
    {
        $product = $tenantId > 0 ? self::resolveEdgeProduct($tenantId) : null;
        $meters = 0.0;
        $count = 0;
        foreach ($panels as $p) {
            $e1 = (float) ($p['edge_1'] ?? 0);
            $e2 = (float) ($p['edge_2'] ?? 0);
            $e3 = (float) ($p['edge_3'] ?? 0);
            $e4 = (float) ($p['edge_4'] ?? 0);
            if ($e1 + $e2 + $e3 + $e4 <= 0) {
                continue;
            }
            $m = self::metersForPanel(
                (float) ($p['finishing_length_mm'] ?? $p['length_mm'] ?? 0),
                (float) ($p['finishing_width_mm'] ?? $p['width_mm'] ?? 0),
                (float) ($p['quantity'] ?? 1),
                ['edge_1' => $e1, 'edge_2' => $e2, 'edge_3' => $e3, 'edge_4' => $e4]
            );
            if ($m > 0) {
                $meters += $m;
                $count++;
            }
        }
        return [
            'meters' => round($meters, 4),
            'sku' => (string) ($product['sku'] ?? 'EDGE-22-WH'),
            'catalog_product_id' => isset($product['id']) ? (int) $product['id'] : null,
            'name' => (string) ($product['name'] ?? 'White Edge Band 22mm'),
            'unit_cost' => (float) ($product['cost'] ?? 2.5),
            'uom' => (string) ($product['uom'] ?? 'M'),
            'panel_count' => $count,
        ];
    }

    /**
     * Estimate edge meters from generator components when panels are not yet created
     * (commercial draft BOM path).
     *
     * @param list<array<string,mixed>> $components
     * @param array{edge_1?:float,edge_2?:float,edge_3?:float,edge_4?:float,apply_to_thickness_gte_mm?:float} $edgeRule
     * @return array{meters:float,sku:string,catalog_product_id:?int,name:string,unit_cost:float,uom:string,panel_count:int}
     */
    public static function aggregateFromComponents(int $tenantId, array $components, array $edgeRule): array
    {
        $minT = (float) ($edgeRule['apply_to_thickness_gte_mm'] ?? 12);
        $synthetic = [];
        foreach ($components as $c) {
            if (($c['type'] ?? $c['component_type'] ?? '') === 'HARDWARE') {
                continue;
            }
            $t = (float) ($c['thickness_mm'] ?? 0);
            if ($t < $minT) {
                continue;
            }
            $synthetic[] = [
                'finishing_length_mm' => (float) ($c['length_mm'] ?? 0),
                'finishing_width_mm' => (float) ($c['width_mm'] ?? 0),
                'quantity' => (float) ($c['qty'] ?? $c['quantity'] ?? 1),
                'edge_1' => (float) ($edgeRule['edge_1'] ?? 0),
                'edge_2' => (float) ($edgeRule['edge_2'] ?? 0),
                'edge_3' => (float) ($edgeRule['edge_3'] ?? 0),
                'edge_4' => (float) ($edgeRule['edge_4'] ?? 0),
            ];
        }
        return self::aggregateFromPanels($tenantId, $synthetic);
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function resolveEdgeProduct(int $tenantId, ?float $bandWidthMm = 22.0): ?array
    {
        if ($tenantId <= 0) {
            return null;
        }
        try {
            $pdo = Database::connection();
        } catch (\Throwable) {
            return null;
        }
        if ($bandWidthMm !== null) {
            $stmt = $pdo->prepare(
                "SELECT * FROM catalog_products
                 WHERE tenant_id=? AND category='EDGE_BAND' AND deleted_at IS NULL
                   AND publish_status='PUBLISHED'
                   AND width_mm IS NOT NULL
                 ORDER BY ABS(width_mm - ?) ASC, id ASC LIMIT 1"
            );
            $stmt->execute([$tenantId, $bandWidthMm]);
            $row = $stmt->fetch();
            if ($row) {
                return $row;
            }
        }
        $stmt = $pdo->prepare(
            "SELECT * FROM catalog_products
             WHERE tenant_id=? AND category='EDGE_BAND' AND deleted_at IS NULL
             ORDER BY (publish_status='PUBLISHED') DESC, id ASC LIMIT 1"
        );
        $stmt->execute([$tenantId]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
        // Ensure seed exists for tests/demo tenants.
        try {
            (new CatalogService())->seedDefaults($tenantId);
            $stmt->execute([$tenantId]);
            return $stmt->fetch() ?: null;
        } catch (\Throwable) {
            return null;
        }
    }
}
