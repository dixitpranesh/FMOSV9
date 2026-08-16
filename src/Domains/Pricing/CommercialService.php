<?php

declare(strict_types=1);

namespace Fmos\Domains\Pricing;

use Fmos\Core\Audit;
use Fmos\Core\Auth;
use Fmos\Core\Database;
use Fmos\Domains\Catalog\HardwareSkuCatalog;
use Fmos\Domains\Furniture\FurnitureEngine;
use Fmos\Domains\Manufacturing\EdgeBandBom;

final class CommercialService
{
    public function ensurePricingVersion(int $tenantId): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare("SELECT id FROM pricing_versions WHERE tenant_id=? AND status='ACTIVE' ORDER BY id DESC LIMIT 1");
        $stmt->execute([$tenantId]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int) $id;
        }
        $stmt = $pdo->prepare("INSERT INTO pricing_versions (tenant_id, name, commercial_mode, default_markup_percent, default_margin_percent, area_uom, status, created_at) VALUES (?, 'Default', 'markup', 25, 20, 'SQ_FT', 'ACTIVE', NOW())");
        $stmt->execute([$tenantId]);
        return (int) $pdo->lastInsertId();
    }

    public function generateBomBoqPrice(int $tenantId, int $projectId, int $furnitureId): array
    {
        $pdo = Database::connection();
        // Unified BOM path: prefer latest manufacturing package BOM revision when present (CRD-002)
        $pkgStmt = $pdo->prepare('SELECT id, bom_revision_id FROM manufacturing_packages WHERE tenant_id=? AND project_id=? AND furniture_id=? AND bom_revision_id IS NOT NULL ORDER BY id DESC LIMIT 1');
        $pkgStmt->execute([$tenantId, $projectId, $furnitureId]);
        $pkg = $pkgStmt->fetch();
        if ($pkg) {
            return $this->priceFromBomRevision($tenantId, $projectId, $furnitureId, (int) $pkg['bom_revision_id'], (int) $pkg['id']);
        }
        return $this->generateBomBoqPriceFromComponents($tenantId, $projectId, $furnitureId);
    }

    private function priceFromBomRevision(int $tenantId, int $projectId, int $furnitureId, int $bomRevId, int $pkgId): array
    {
        $pdo = Database::connection();
        $items = $pdo->prepare('SELECT * FROM bom_items WHERE bom_revision_id=?');
        $items->execute([$bomRevId]);
        $costTotal = 0.0;
        foreach ($items->fetchAll() as $item) {
            $costTotal += (float) $item['total_cost'];
        }
        $bomId = (int) $pdo->query('SELECT bom_id FROM bom_revisions WHERE id=' . (int) $bomRevId)->fetchColumn();
        $engine = new FurnitureEngine();
        $furniture = $engine->get($tenantId, $furnitureId);

        $boqNumber = 'BOQ-' . $projectId . '-' . $furnitureId . '-' . time();
        $stmt = $pdo->prepare('INSERT INTO boq_headers (tenant_id, project_id, bom_id, boq_number, current_revision, status, created_at, updated_at) VALUES (?, ?, ?, ?, 1, ?, NOW(), NOW())');
        $stmt->execute([$tenantId, $projectId, $bomId, $boqNumber, 'GENERATED']);
        $boqId = (int) $pdo->lastInsertId();
        $stmt = $pdo->prepare('INSERT INTO boq_revisions (boq_id, revision_number, status, created_at) VALUES (?, 1, ?, NOW())');
        $stmt->execute([$boqId, 'DRAFT']);
        $boqRevId = (int) $pdo->lastInsertId();

        $pricingVersionId = $this->ensurePricingVersion($tenantId);
        $stmt = $pdo->prepare('SELECT * FROM pricing_versions WHERE id=?');
        $stmt->execute([$pricingVersionId]);
        $pv = $stmt->fetch();
        $markup = (float) $pv['default_markup_percent'];
        $gross = $costTotal * (1 + $markup / 100);
        $discount = 0.0;
        $afterDiscount = $gross * (1 - $discount / 100);
        $tax = 18.0;
        $final = $afterDiscount * (1 + $tax / 100);

        $stmt = $pdo->prepare('INSERT INTO boq_items (boq_revision_id, description, category, quantity, uom, unit_rate, discount_percent, tax_percent, line_total) VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?)');
        $stmt->execute([$boqRevId, $furniture['name'] . ' Package', 'FURNITURE', 'SET', round($gross, 4), $discount, $tax, round($final, 4)]);

        $breakdown = [
            'cost_total' => round($costTotal, 4),
            'markup_percent' => $markup,
            'gross_selling' => round($gross, 4),
            'discount_percent' => $discount,
            'tax_percent' => $tax,
            'final_price' => round($final, 4),
            'mode' => 'markup',
            'area_uom' => 'SQ_FT',
            'waterfall' => ['Cost', 'Markup', 'Gross', 'Discount', 'Tax', 'Final'],
            'source' => 'unified_bom',
            'bom_revision_id' => $bomRevId,
            'manufacturing_package_id' => $pkgId,
        ];
        $stmt = $pdo->prepare('INSERT INTO pricing_calculations (tenant_id, project_id, boq_id, pricing_version_id, cost_total, markup_percent, gross_selling, discount_percent, tax_percent, final_price, breakdown_json, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$tenantId, $projectId, $boqId, $pricingVersionId, $breakdown['cost_total'], $markup, $breakdown['gross_selling'], $discount, $tax, $breakdown['final_price'], json_encode($breakdown)]);
        $calcId = (int) $pdo->lastInsertId();
        Audit::record('GENERATE', 'commercial', $calcId, null, $breakdown);
        return [
            'bom_id' => $bomId,
            'bom_revision_id' => $bomRevId,
            'boq_id' => $boqId,
            'pricing_calculation_id' => $calcId,
            'breakdown' => $breakdown,
        ];
    }

    public function generateBomBoqPriceFromComponents(int $tenantId, int $projectId, int $furnitureId): array
    {
        $engine = new FurnitureEngine();
        $furniture = $engine->get($tenantId, $furnitureId);
        $pdo = Database::connection();

        $board = $pdo->prepare("SELECT * FROM catalog_products WHERE tenant_id=? AND category='BOARD' AND publish_status='PUBLISHED' ORDER BY id LIMIT 1");
        $board->execute([$tenantId]);
        $boardProduct = $board->fetch() ?: ['id' => null, 'name' => 'Board', 'cost' => 45, 'selling_price' => 65, 'uom' => 'SQ_FT'];

        $bomNumber = 'BOM-' . $projectId . '-' . $furnitureId . '-' . time() . '-' . bin2hex(random_bytes(3));
        $stmt = $pdo->prepare('INSERT INTO bom_headers (tenant_id, project_id, furniture_id, bom_number, current_revision, status, created_at, updated_at) VALUES (?, ?, ?, ?, 1, ?, NOW(), NOW())');
        $stmt->execute([$tenantId, $projectId, $furnitureId, $bomNumber, 'GENERATED']);
        $bomId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare('INSERT INTO bom_revisions (bom_id, revision_number, source_hash, status, created_at) VALUES (?, 1, ?, ?, NOW())');
        $hash = hash('sha256', json_encode($furniture['components']));
        $stmt->execute([$bomId, $hash, 'DRAFT']);
        $bomRevId = (int) $pdo->lastInsertId();

        $costTotal = 0.0;
        foreach ($furniture['components'] as $c) {
            if (($c['type'] ?? '') === 'HARDWARE') {
                $qty = (float) $c['qty'];
                $resolved = HardwareSkuCatalog::resolveFromComponent($tenantId, $c);
                $unit = $resolved['unit_cost'];
                $total = $qty * $unit;
                $costTotal += $total;
                $desc = $resolved['sku'] . ' — ' . $resolved['name'];
                $stmt = $pdo->prepare('INSERT INTO bom_items (bom_revision_id, item_type, catalog_product_id, description, quantity, uom, unit_cost, total_cost, source_ref) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([
                    $bomRevId,
                    'HARDWARE',
                    $resolved['catalog_product_id'],
                    $desc,
                    $qty,
                    $resolved['uom'],
                    $unit,
                    $total,
                    $c['name'] ?? $resolved['sku'],
                ]);
                continue;
            }
            $areaMm2 = ((float) $c['length_mm'] * (float) $c['width_mm'] * (float) $c['qty']);
            $areaSqFt = $areaMm2 / 92903.04; // mm² to sq.ft
            $unit = (float) $boardProduct['cost'];
            $total = $areaSqFt * $unit;
            $costTotal += $total;
            $stmt = $pdo->prepare('INSERT INTO bom_items (bom_revision_id, item_type, catalog_product_id, description, quantity, uom, unit_cost, total_cost, source_ref) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$bomRevId, 'BOARD', $boardProduct['id'], $c['name'], round($areaSqFt, 4), 'SQ_FT', $unit, round($total, 4), $c['name']]);
        }

        $edgeRule = ['edge_1' => 0.8, 'edge_2' => 0.8, 'edge_3' => 0.8, 'edge_4' => 0.8, 'apply_to_thickness_gte_mm' => 12];
        $edgeAgg = EdgeBandBom::aggregateFromComponents($tenantId, $furniture['components'] ?? [], $edgeRule);
        if ($edgeAgg['meters'] > 0) {
            $total = $edgeAgg['meters'] * $edgeAgg['unit_cost'];
            $costTotal += $total;
            $stmt = $pdo->prepare('INSERT INTO bom_items (bom_revision_id, item_type, catalog_product_id, description, quantity, uom, unit_cost, total_cost, source_ref) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $bomRevId,
                'EDGE_BAND',
                $edgeAgg['catalog_product_id'],
                $edgeAgg['sku'] . ' — ' . $edgeAgg['name'],
                $edgeAgg['meters'],
                $edgeAgg['uom'],
                $edgeAgg['unit_cost'],
                round($total, 4),
                'EDGE-TOTAL',
            ]);
        }

        $boqNumber = 'BOQ-' . $projectId . '-' . $furnitureId . '-' . time();
        $stmt = $pdo->prepare('INSERT INTO boq_headers (tenant_id, project_id, bom_id, boq_number, current_revision, status, created_at, updated_at) VALUES (?, ?, ?, ?, 1, ?, NOW(), NOW())');
        $stmt->execute([$tenantId, $projectId, $bomId, $boqNumber, 'GENERATED']);
        $boqId = (int) $pdo->lastInsertId();
        $stmt = $pdo->prepare('INSERT INTO boq_revisions (boq_id, revision_number, status, created_at) VALUES (?, 1, ?, NOW())');
        $stmt->execute([$boqId, 'DRAFT']);
        $boqRevId = (int) $pdo->lastInsertId();

        $pricingVersionId = $this->ensurePricingVersion($tenantId);
        $stmt = $pdo->prepare('SELECT * FROM pricing_versions WHERE id=?');
        $stmt->execute([$pricingVersionId]);
        $pv = $stmt->fetch();
        $markup = (float) $pv['default_markup_percent'];
        // Waterfall: Cost -> Markup -> Gross -> Discount -> Tax -> Final
        $gross = $costTotal * (1 + $markup / 100);
        $discount = 0.0;
        $afterDiscount = $gross * (1 - $discount / 100);
        $tax = 18.0;
        $final = $afterDiscount * (1 + $tax / 100);

        $stmt = $pdo->prepare('INSERT INTO boq_items (boq_revision_id, description, category, quantity, uom, unit_rate, discount_percent, tax_percent, line_total) VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?)');
        $stmt->execute([$boqRevId, $furniture['name'] . ' Package', 'FURNITURE', 'SET', round($gross, 4), $discount, $tax, round($final, 4)]);

        $breakdown = [
            'cost_total' => round($costTotal, 4),
            'markup_percent' => $markup,
            'gross_selling' => round($gross, 4),
            'discount_percent' => $discount,
            'tax_percent' => $tax,
            'final_price' => round($final, 4),
            'mode' => 'markup',
            'area_uom' => 'SQ_FT',
            'waterfall' => ['Cost', 'Markup', 'Gross', 'Discount', 'Tax', 'Final'],
        ];
        $stmt = $pdo->prepare('INSERT INTO pricing_calculations (tenant_id, project_id, boq_id, pricing_version_id, cost_total, markup_percent, gross_selling, discount_percent, tax_percent, final_price, breakdown_json, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$tenantId, $projectId, $boqId, $pricingVersionId, $breakdown['cost_total'], $markup, $breakdown['gross_selling'], $discount, $tax, $breakdown['final_price'], json_encode($breakdown)]);
        $calcId = (int) $pdo->lastInsertId();

        Audit::record('GENERATE', 'commercial', $calcId, null, $breakdown);
        return [
            'bom_id' => $bomId,
            'boq_id' => $boqId,
            'pricing_calculation_id' => $calcId,
            'breakdown' => $breakdown,
        ];
    }

    public function createQuotation(int $tenantId, int $projectId, int $clientId, int $pricingCalculationId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM pricing_calculations WHERE id=? AND tenant_id=?');
        $stmt->execute([$pricingCalculationId, $tenantId]);
        $calc = $stmt->fetch();
        if (!$calc) {
            throw new \RuntimeException('Pricing calculation not found');
        }
        $number = 'QT-' . $projectId . '-' . time();
        $stmt = $pdo->prepare('INSERT INTO quotations (tenant_id, project_id, client_id, quote_number, status, pricing_calculation_id, pricing_snapshot_json, grand_total, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([
            $tenantId,
            $projectId,
            $clientId,
            $number,
            'DRAFT',
            $pricingCalculationId,
            $calc['breakdown_json'],
            $calc['final_price'],
        ]);
        $id = (int) $pdo->lastInsertId();
        Audit::record('CREATE', 'quotation', $id);
        return $this->getQuote($tenantId, $id);
    }

    public function transitionQuote(int $tenantId, int $id, string $status): array
    {
        $allowed = ['DRAFT','INTERNAL_REVIEW','SENT','CLIENT_REVIEW','APPROVED','ACCEPTED','REJECTED','EXPIRED','CANCELLED'];
        if (!in_array($status, $allowed, true)) {
            throw new \RuntimeException('Invalid quote status');
        }
        $pdo = Database::connection();
        $before = $this->getQuote($tenantId, $id);
        $stmt = $pdo->prepare('UPDATE quotations SET status=?, updated_at=NOW() WHERE id=? AND tenant_id=?');
        $stmt->execute([$status, $id, $tenantId]);
        Audit::record('STATUS', 'quotation', $id, $before, ['status' => $status]);
        return $this->getQuote($tenantId, $id);
    }

    public function getQuote(int $tenantId, int $id): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM quotations WHERE id=? AND tenant_id=?');
        $stmt->execute([$id, $tenantId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new \RuntimeException('Quote not found');
        }
        $row['pricing_snapshot'] = json_decode($row['pricing_snapshot_json'] ?? 'null', true);
        unset($row['pricing_snapshot_json']);
        return $row;
    }
}
