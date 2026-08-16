<?php

declare(strict_types=1);

namespace Fmos\Domains\Catalog;

/**
 * Maps LayoutEngine hardware roles → tenant catalog SKUs.
 * Definitions are code defaults; tenants override by publishing alternate SKUs with the same sku key
 * or by setting attributes_json.hardware_role on a HARDWARE product.
 */
final class HardwareSkuCatalog
{
    /**
     * @return array<string, array{sku:string,name:string,uom:string,cost:float,selling_price:float,brand:?string}>
     */
    public static function definitions(): array
    {
        return [
            'HINGE' => ['sku' => 'HW-HINGE-01', 'name' => 'Concealed Hinge', 'uom' => 'PCS', 'cost' => 35, 'selling_price' => 55, 'brand' => null],
            'LOFT_HINGE' => ['sku' => 'HW-HINGE-LOFT', 'name' => 'Loft Hinge', 'uom' => 'PCS', 'cost' => 40, 'selling_price' => 60, 'brand' => null],
            'DRAWER_SLIDE' => ['sku' => 'HW-SLIDE-450', 'name' => 'Drawer Slide 450mm (pair half)', 'uom' => 'PCS', 'cost' => 45, 'selling_price' => 70, 'brand' => null],
            'PULL_OUT_SLIDE' => ['sku' => 'HW-SLIDE-PO', 'name' => 'Pull-Out Full Extension Slide', 'uom' => 'PCS', 'cost' => 55, 'selling_price' => 85, 'brand' => null],
            'SLIDING_TRACK' => ['sku' => 'HW-TRACK-SL', 'name' => 'Sliding Door Track Set', 'uom' => 'SET', 'cost' => 850, 'selling_price' => 1200, 'brand' => null],
            'HANGING_ROD' => ['sku' => 'HW-ROD-OVAL', 'name' => 'Oval Hanging Rod', 'uom' => 'PCS', 'cost' => 120, 'selling_price' => 180, 'brand' => null],
            'SHELF_PIN' => ['sku' => 'HW-PIN-SHELF', 'name' => 'Shelf Support Pin', 'uom' => 'PCS', 'cost' => 2, 'selling_price' => 5, 'brand' => null],
            'CUTLERY_ORGANIZER' => ['sku' => 'HW-CUTLERY-01', 'name' => 'Cutlery Organizer Insert', 'uom' => 'PCS', 'cost' => 180, 'selling_price' => 280, 'brand' => null],
            'BOTTLE_PULLOUT' => ['sku' => 'HW-BOTTLE-01', 'name' => 'Bottle / Spice Pull-Out Unit', 'uom' => 'SET', 'cost' => 2200, 'selling_price' => 3200, 'brand' => null],
            'WASTE_BIN' => ['sku' => 'HW-WASTE-01', 'name' => 'Waste Bin Pull-Out', 'uom' => 'SET', 'cost' => 1800, 'selling_price' => 2600, 'brand' => null],
            'TROUSER_RACK' => ['sku' => 'HW-TROUSER-01', 'name' => 'Trouser Pull-Out Rack', 'uom' => 'SET', 'cost' => 1600, 'selling_price' => 2400, 'brand' => null],
            'TROUSER_ARM' => ['sku' => 'HW-TROUSER-ARM', 'name' => 'Trouser Rack Arm', 'uom' => 'PCS', 'cost' => 25, 'selling_price' => 40, 'brand' => null],
            'WICKER_BASKET' => ['sku' => 'HW-WICKER-01', 'name' => 'Wicker / Wire Basket', 'uom' => 'PCS', 'cost' => 450, 'selling_price' => 650, 'brand' => null],
            'HOB_CLEARANCE' => ['sku' => 'HW-HOB-NOTE', 'name' => 'Hob Bay Clearance / Vent Note', 'uom' => 'PCS', 'cost' => 0, 'selling_price' => 0, 'brand' => null],
            'HARDWARE' => ['sku' => 'HW-GENERIC', 'name' => 'Generic Hardware', 'uom' => 'PCS', 'cost' => 35, 'selling_price' => 55, 'brand' => null],
        ];
    }

    /**
     * Seed rows for CatalogService (sku, name, category, thickness, length, width, cost, sell, uom).
     *
     * @return list<array{0:string,1:string,2:string,3:?float,4:?float,5:?float,6:float,7:float,8:string,9:?string}>
     */
    public static function seedRows(): array
    {
        $rows = [];
        foreach (self::definitions() as $role => $def) {
            $rows[] = [
                $def['sku'],
                $def['name'],
                'HARDWARE',
                null,
                null,
                null,
                $def['cost'],
                $def['selling_price'],
                $def['uom'],
                $role,
            ];
        }
        return $rows;
    }

    public static function skuForRole(string $role): string
    {
        $role = strtoupper(trim($role));
        $defs = self::definitions();
        if (isset($defs[$role])) {
            return $defs[$role]['sku'];
        }
        return $defs['HARDWARE']['sku'];
    }

    /**
     * Resolve a catalog product for a hardware role (tenant override via attributes_json.hardware_role first).
     *
     * @return array<string,mixed>|null
     */
    public static function resolveProduct(int $tenantId, string $role): ?array
    {
        if ($tenantId <= 0) {
            return null;
        }
        $role = strtoupper(trim($role));
        try {
            $pdo = \Fmos\Core\Database::connection();
        } catch (\Throwable) {
            return null;
        }

        $byAttr = $pdo->prepare(
            "SELECT * FROM catalog_products
             WHERE tenant_id=? AND category='HARDWARE' AND deleted_at IS NULL
               AND publish_status='PUBLISHED'
               AND JSON_UNQUOTE(JSON_EXTRACT(attributes_json, '$.hardware_role')) = ?
             ORDER BY id ASC LIMIT 1"
        );
        $byAttr->execute([$tenantId, $role]);
        $row = $byAttr->fetch();
        if ($row) {
            return $row;
        }

        $sku = self::skuForRole($role);
        $bySku = $pdo->prepare(
            "SELECT * FROM catalog_products
             WHERE tenant_id=? AND sku=? AND deleted_at IS NULL
             ORDER BY id ASC LIMIT 1"
        );
        $bySku->execute([$tenantId, $sku]);
        $row = $bySku->fetch();
        return $row ?: null;
    }

    /**
     * @param array<string,mixed> $component furniture component or generator row
     * @return array{role:string,sku:string,catalog_product_id:?int,name:string,uom:string,unit_cost:float}
     */
    public static function resolveFromComponent(int $tenantId, array $component): array
    {
        $role = strtoupper((string) (
            $component['manufacturing_data']['role']
            ?? $component['geometry']['role']
            ?? $component['role']
            ?? 'HARDWARE'
        ));
        if ($role === '' || $role === 'PANEL') {
            $role = 'HARDWARE';
        }
        $product = self::resolveProduct($tenantId, $role);
        $def = self::definitions()[$role] ?? self::definitions()['HARDWARE'];
        return [
            'role' => $role,
            'sku' => (string) ($product['sku'] ?? $def['sku']),
            'catalog_product_id' => $product ? (int) $product['id'] : null,
            'name' => (string) ($product['name'] ?? $def['name']),
            'uom' => (string) ($product['uom'] ?? $def['uom']),
            'unit_cost' => (float) ($product['cost'] ?? $def['cost']),
        ];
    }
}
