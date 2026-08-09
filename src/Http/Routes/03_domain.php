<?php

declare(strict_types=1);

/** @var \Fmos\Core\Router $router */

use Fmos\Core\Auth;
use Fmos\Core\Request;
use Fmos\Core\Response;
use Fmos\Domains\Architecture\DesignService;
use Fmos\Domains\Catalog\CatalogService;
use Fmos\Domains\Furniture\FurnitureEngine;
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
    (new FurnitureEngine())->ensureTemplates();
    $pdo = \Fmos\Core\Database::connection();
    Response::json($pdo->query("SELECT id, code, name, category, version, parameters_json FROM furniture_templates WHERE status='PUBLISHED'")->fetchAll());
});

$router->post('/api/v1/furniture/instances', static function (Request $request) {
    Auth::requirePermission('furniture.create');
    Response::json((new FurnitureEngine())->createInstance(Auth::requireTenant(), [
        'template_code' => (string) $request->input('template_code'),
        'project_id' => (int) $request->input('project_id'),
        'room_id' => (int) $request->input('room_id'),
        'name' => $request->input('name'),
        'parameters' => $request->input('parameters') ?? [],
        'position' => $request->input('position') ?? [],
        'material_id' => $request->input('material_id'),
    ]), 201);
});

$router->get('/api/v1/projects/{id}/furniture', static function (Request $r, array $p) {
    Auth::requirePermission('furniture.view');
    Response::json((new FurnitureEngine())->listByProject(Auth::requireTenant(), (int) $p['id']));
});

$router->put('/api/v1/furniture/instances/{id}/parameters', static function (Request $request, array $p) {
    Auth::requirePermission('furniture.update');
    Response::json((new FurnitureEngine())->updateParameters(
        Auth::requireTenant(),
        (int) $p['id'],
        (array) ($request->input('parameters') ?? [])
    ));
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
    Response::json((new ManufacturingService())->validateAndGenerate(
        Auth::requireTenant(),
        (int) $request->input('project_id'),
        (int) $request->input('furniture_id'),
    ), 201);
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
