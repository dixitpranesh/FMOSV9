<?php

declare(strict_types=1);

/** @var \Fmos\Core\Router $router */

use Fmos\Core\Auth;
use Fmos\Core\Request;
use Fmos\Core\Response;
use Fmos\Domains\Architecture\DesignService;
use Fmos\Domains\Catalog\CatalogService;
use Fmos\Domains\Catalog\MaterialService;
use Fmos\Domains\Export\ExportService;
use Fmos\Domains\Furniture\FurnitureEngine;
use Fmos\Domains\Furniture\FurnitureViewService;
use Fmos\Domains\Manufacturing\ManufacturingService;
use Fmos\Domains\Pricing\CommercialService;

$router->get('/api/v1/rooms/{id}/design', static function (Request $r, array $p) {
    Auth::requirePermission('design.view');
    $svc = new DesignService();
    Response::json($svc->listByRoom(Auth::requireTenant(), (int) $p['id']));
});

$router->post('/api/v1/design/objects', static function (Request $request) {
    Auth::requirePermission('design.create');
    $svc = new DesignService();
    Response::json($svc->upsert(Auth::requireTenant(), [
        'project_id' => (int) $request->input('project_id'),
        'room_id' => (int) $request->input('room_id'),
        'object_type' => (string) $request->input('object_type'),
        'name' => $request->input('name'),
        'geometry' => $request->input('geometry'),
        'parameters' => $request->input('parameters') ?? [],
        'materials' => $request->input('materials') ?? [],
        'id' => $request->input('id'),
    ]), 201);
});

$router->delete('/api/v1/design/objects/{id}', static function (Request $r, array $p) {
    Auth::requirePermission('design.update');
    (new DesignService())->delete(Auth::requireTenant(), (int) $p['id']);
    Response::json(['deleted' => true]);
});

$router->get('/api/v1/catalog/products', static function (Request $request) {
    Auth::requirePermission('catalog.view');
    $publishedOnly = (string) $request->input('published', '0') === '1';
    Response::json((new CatalogService())->list(Auth::requireTenant(), $publishedOnly));
});

$router->post('/api/v1/catalog/products', static function (Request $request) {
    Auth::requirePermission('catalog.manage');
    Response::json((new CatalogService())->create(Auth::requireTenant(), [
        'sku' => (string) $request->input('sku'),
        'name' => (string) $request->input('name'),
        'category' => (string) $request->input('category'),
        'publish_status' => $request->input('publish_status'),
        'availability_status' => $request->input('availability_status'),
        'thickness_mm' => $request->input('thickness_mm'),
        'length_mm' => $request->input('length_mm'),
        'width_mm' => $request->input('width_mm'),
        'cost' => $request->input('cost'),
        'selling_price' => $request->input('selling_price'),
        'uom' => $request->input('uom'),
    ]), 201);
});

$router->post('/api/v1/catalog/products/{id}/publish', static function (Request $r, array $p) {
    Auth::requirePermission('catalog.manage');
    Response::json((new CatalogService())->publish(Auth::requireTenant(), (int) $p['id']));
});

$router->post('/api/v1/catalog/seed', static function () {
    Auth::requirePermission('catalog.manage');
    (new CatalogService())->seedDefaults(Auth::requireTenant());
    Response::json(['seeded' => true]);
});

$router->get('/api/v1/furniture/templates', static function () {
    Auth::requirePermission('furniture.view');
    Response::json((new FurnitureEngine())->listTemplates());
});

$router->post('/api/v1/furniture/instances', static function (Request $request) {
    Auth::requirePermission('furniture.create');
    $roomInput = $request->input('room_id');
    Response::json((new FurnitureEngine())->createInstance(Auth::requireTenant(), [
        'template_code' => (string) $request->input('template_code'),
        'project_id' => (int) $request->input('project_id'),
        'room_id' => $roomInput === null || $roomInput === '' ? null : (int) $roomInput,
        'name' => $request->input('name'),
        'code' => $request->input('code'),
        'category' => $request->input('category'),
        'type' => $request->input('type'),
        'quantity' => $request->input('quantity') ?? 1,
        'parameters' => $request->input('parameters') ?? [],
        'position' => $request->input('position') ?? [],
        'material_id' => $request->input('material_id'),
        'exterior_finish_id' => $request->input('exterior_finish_id'),
        'interior_finish_id' => $request->input('interior_finish_id'),
        'specification' => $request->input('specification') ?? [],
    ]), 201);
});

$router->get('/api/v1/projects/{id}/furniture', static function (Request $r, array $p) {
    Auth::requirePermission('furniture.view');
    Response::json((new FurnitureEngine())->listByProject(Auth::requireTenant(), (int) $p['id']));
});

$router->post('/api/v1/projects/{id}/furniture', static function (Request $request, array $p) {
    Auth::requirePermission('furniture.create');
    $roomInput = $request->input('room_id');
    Response::json((new FurnitureEngine())->createInstance(Auth::requireTenant(), [
        'template_code' => (string) $request->input('template_code'),
        'project_id' => (int) $p['id'],
        'room_id' => $roomInput === null || $roomInput === '' ? null : (int) $roomInput,
        'name' => $request->input('name'),
        'code' => $request->input('code'),
        'category' => $request->input('category'),
        'type' => $request->input('type'),
        'quantity' => $request->input('quantity') ?? 1,
        'parameters' => $request->input('parameters') ?? [],
        'position' => $request->input('position') ?? [],
        'material_id' => $request->input('material_id'),
        'exterior_finish_id' => $request->input('exterior_finish_id'),
        'interior_finish_id' => $request->input('interior_finish_id'),
        'specification' => $request->input('specification') ?? [],
    ]), 201);
});

$router->get('/api/v1/furniture/instances/{id}', static function (Request $r, array $p) {
    Auth::requirePermission('furniture.view');
    Response::json((new FurnitureEngine())->get(Auth::requireTenant(), (int) $p['id']));
});

$router->put('/api/v1/furniture/instances/{id}/parameters', static function (Request $request, array $p) {
    Auth::requirePermission('furniture.update');
    Response::json((new FurnitureEngine())->updateParameters(
        Auth::requireTenant(),
        (int) $p['id'],
        (array) ($request->input('parameters') ?? [])
    ));
});

$router->put('/api/v1/furniture/instances/{id}/layout', static function (Request $request, array $p) {
    Auth::requirePermission('furniture.update');
    $layout = $request->input('layout');
    if (!is_array($layout)) {
        Response::error('VALIDATION', 'layout object required', 422);
        return;
    }
    Response::json((new FurnitureEngine())->updateLayout(
        Auth::requireTenant(),
        (int) $p['id'],
        $layout
    ));
});

$router->put('/api/v1/furniture/instances/{id}/specification', static function (Request $request, array $p) {
    Auth::requirePermission('furniture.update');
    $payload = [];
    foreach (['exterior_finish_id', 'interior_finish_id', 'material_id', 'specification', 'name', 'code', 'quantity'] as $key) {
        if (array_key_exists($key, $request->body)) {
            $payload[$key] = $request->body[$key];
        }
    }
    Response::json((new FurnitureEngine())->updateMeta(
        Auth::requireTenant(),
        (int) $p['id'],
        $payload
    ));
});

$router->put('/api/v1/furniture/instances/{id}', static function (Request $request, array $p) {
    Auth::requirePermission('furniture.update');
    $payload = [];
    foreach (['name', 'code', 'category', 'type', 'quantity', 'room_id', 'exterior_finish_id', 'interior_finish_id', 'material_id', 'specification'] as $key) {
        if (array_key_exists($key, $request->body)) {
            $payload[$key] = $request->body[$key];
        }
    }
    Response::json((new FurnitureEngine())->updateMeta(
        Auth::requireTenant(),
        (int) $p['id'],
        $payload
    ));
});

$router->get('/api/v1/materials', static function (Request $request) {
    Auth::requirePermission('catalog.view');
    Response::json((new MaterialService())->list(
        Auth::requireTenant(),
        $request->input('category'),
        $request->input('series')
    ));
});

$router->get('/api/v1/materials/{id}', static function (Request $r, array $p) {
    Auth::requirePermission('catalog.view');
    Response::json((new MaterialService())->get(Auth::requireTenant(), (int) $p['id']));
});

$router->get('/api/v1/furniture/instances/{id}/components', static function (Request $r, array $p) {
    Auth::requirePermission('furniture.view');
    Response::json((new FurnitureEngine())->listComponentRows(Auth::requireTenant(), (int) $p['id']));
});

$router->put('/api/v1/furniture/instances/{id}/components/{cid}', static function (Request $request, array $p) {
    Auth::requirePermission('furniture.update');
    $payload = [];
    foreach (['name', 'component_type', 'quantity', 'length_mm', 'width_mm', 'thickness_mm', 'material_id', 'finish_id', 'parent_component_id', 'geometry', 'manufacturing_data', 'status'] as $key) {
        if (array_key_exists($key, $request->body)) {
            $payload[$key] = $request->body[$key];
        }
    }
    Response::json((new FurnitureEngine())->updateComponent(
        Auth::requireTenant(),
        (int) $p['id'],
        (int) $p['cid'],
        $payload
    ));
});

$router->delete('/api/v1/furniture/instances/{id}/components/{cid}', static function (Request $r, array $p) {
    Auth::requirePermission('furniture.update');
    (new FurnitureEngine())->softDeleteComponent(Auth::requireTenant(), (int) $p['id'], (int) $p['cid']);
    Response::json(['deleted' => true]);
});

$router->get('/api/v1/furniture/instances/{id}/2d', static function (Request $request, array $p) {
    Auth::requirePermission('furniture.view');
    Response::json((new FurnitureViewService())->drawing2d(
        Auth::requireTenant(),
        (int) $p['id'],
        (string) ($request->input('view') ?? 'FRONT')
    ));
});

$router->get('/api/v1/furniture/instances/{id}/3d-model', static function (Request $r, array $p) {
    Auth::requirePermission('furniture.view');
    Response::json((new FurnitureViewService())->model3d(Auth::requireTenant(), (int) $p['id']));
});

$router->post('/api/v1/furniture/instances/{id}/validate', static function (Request $r, array $p) {
    Auth::requirePermission('manufacturing.generate');
    Response::json((new ManufacturingService())->validateFurniture(Auth::requireTenant(), (int) $p['id']));
});

$router->post('/api/v1/projects/{id}/manufacturing', static function (Request $request, array $p) {
    Auth::requirePermission('manufacturing.generate');
    $ids = $request->input('furniture_ids') ?? [];
    if (!is_array($ids)) {
        $ids = [];
    }
    Response::json((new ManufacturingService())->createProjectManufacturing(
        Auth::requireTenant(),
        (int) $p['id'],
        $ids
    ), 201);
});

$router->get('/api/v1/manufacturing/{id}/cutlist', static function (Request $request, array $p) {
    Auth::requirePermission('manufacturing.view');
    Response::json((new ManufacturingService())->cutlist(
        Auth::requireTenant(),
        (int) $p['id'],
        $request->input('scope')
    ));
});

$router->post('/api/v1/manufacturing/{id}/cutlist/export', static function (Request $r, array $p) {
    Auth::requirePermission('manufacturing.view');
    Response::json((new ExportService())->manufacturingPackageCsv(Auth::requireTenant(), (int) $p['id']));
});

$router->post('/api/v1/furniture/instances/{id}/export/design', static function (Request $request, array $p) {
    Auth::requirePermission('furniture.view');
    Response::json((new ExportService())->designHtml(
        Auth::requireTenant(),
        (int) $p['id'],
        (string) ($request->input('view') ?? 'FRONT')
    ));
});

$router->put('/api/v1/nesting/{id}/placement', static function (Request $request, array $p) {
    Auth::requirePermission('nesting.generate');
    Response::json((new ManufacturingService())->updateNestPlacement(
        Auth::requireTenant(),
        (int) $p['id'],
        (array) $request->body
    ));
});

$router->post('/api/v1/nesting/{id}/reoptimize', static function (Request $r, array $p) {
    Auth::requirePermission('nesting.generate');
    Response::json((new ManufacturingService())->renestPreservingLocks(Auth::requireTenant(), (int) $p['id']), 201);
});

$router->post('/api/v1/commercial/generate', static function (Request $request) {
    Auth::requirePermission('bom.generate');
    Response::json((new CommercialService())->generateBomBoqPrice(
        Auth::requireTenant(),
        (int) $request->input('project_id'),
        (int) $request->input('furniture_id'),
    ), 201);
});

$router->post('/api/v1/quotations', static function (Request $request) {
    Auth::requirePermission('quote.create');
    Response::json((new CommercialService())->createQuotation(
        Auth::requireTenant(),
        (int) $request->input('project_id'),
        (int) $request->input('client_id'),
        (int) $request->input('pricing_calculation_id'),
    ), 201);
});

$router->post('/api/v1/quotations/{id}/status', static function (Request $request, array $p) {
    Auth::requirePermission('quote.approve');
    Response::json((new CommercialService())->transitionQuote(
        Auth::requireTenant(),
        (int) $p['id'],
        (string) $request->input('status'),
    ));
});

$router->post('/api/v1/manufacturing/generate', static function (Request $request) {
    Auth::requirePermission('manufacturing.generate');
    try {
        Response::json((new ManufacturingService())->validateAndGenerate(
            Auth::requireTenant(),
            (int) $request->input('project_id'),
            (int) $request->input('furniture_id'),
        ), 201);
    } catch (\Throwable $e) {
        \Fmos\Core\Logger::error('manufacturing.generate failed', ['error' => $e->getMessage()]);
        Response::error('JOB_FAILED', $e->getMessage(), 500);
    }
});

$router->post('/api/v1/manufacturing/{id}/release', static function (Request $r, array $p) {
    Response::json((new ManufacturingService())->release(Auth::requireTenant(), (int) $p['id']));
});

$router->post('/api/v1/manufacturing/{id}/nest', static function (Request $r, array $p) {
    Auth::requirePermission('nesting.generate');
    Response::json((new ManufacturingService())->nest(Auth::requireTenant(), (int) $p['id']), 201);
});

$router->get('/api/v1/manufacturing/{id}/labels', static function (Request $r, array $p) {
    Auth::requirePermission('nesting.view');
    Response::json((new ManufacturingService())->labels(Auth::requireTenant(), (int) $p['id']));
});

$router->get('/api/v1/manufacturing/{id}', static function (Request $r, array $p) {
    Auth::requirePermission('manufacturing.view');
    Response::json((new ManufacturingService())->getPackage(Auth::requireTenant(), (int) $p['id']));
});
