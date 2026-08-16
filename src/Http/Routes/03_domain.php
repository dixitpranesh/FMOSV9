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
use Fmos\Domains\Furniture\FurnitureLayoutEngine;
use Fmos\Domains\Furniture\FurnitureViewService;
use Fmos\Domains\Furniture\InternalConfigCatalog;
use Fmos\Domains\Furniture\KitchenCompositionService;
use Fmos\Domains\Furniture\ModuleRulesEngine;
use Fmos\Domains\Furniture\ModuleTypeCatalog;
use Fmos\Domains\Manufacturing\ManufacturingService;
use Fmos\Domains\Manufacturing\SheetPlanService;
use Fmos\Domains\Pricing\CommercialService;
use Fmos\Core\Database;

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

$router->get('/api/v1/furniture/module-types', static function () {
    Auth::requirePermission('furniture.view');
    Response::json(array_values(ModuleTypeCatalog::all()));
});

$router->get('/api/v1/furniture/module-types/{code}', static function (Request $r, array $p) {
    Auth::requirePermission('furniture.view');
    $mod = ModuleTypeCatalog::get((string) $p['code']);
    if ($mod === null) {
        Response::error('NOT_FOUND', 'Unknown module type', 404);
        return;
    }
    Response::json($mod);
});

$router->get('/api/v1/furniture/internal-configs', static function (Request $request) {
    Auth::requirePermission('furniture.view');
    $all = array_values(InternalConfigCatalog::all());
    $cat = $request->input('category');
    if (is_string($cat) && $cat !== '') {
        $catU = strtoupper($cat);
        $all = array_values(array_filter(
            $all,
            static fn ($c) => strtoupper((string) ($c['category'] ?? '')) === $catU
        ));
    }
    Response::json($all);
});

$router->get('/api/v1/furniture/layout-presets', static function (Request $request) {
    Auth::requirePermission('furniture.view');
    $category = $request->input('category');
    Response::json(InternalConfigCatalog::layoutPresets(is_string($category) ? $category : null));
});

$router->post('/api/v1/furniture/instances/{id}/recommend-internals', static function (Request $request, array $p) {
    Auth::requirePermission('furniture.view');
    $engine = new FurnitureEngine();
    $inst = $engine->get(Auth::requireTenant(), (int) $p['id']);
    $params = $inst['parameters'] ?? [];
    $layout = $request->input('layout');
    if (!is_array($layout)) {
        $layout = $params['layout'] ?? [];
    }
    $dims = [
        'width' => (float) ($request->input('width') ?? $params['width'] ?? $inst['width_mm'] ?? 0),
        'height' => (float) ($request->input('height') ?? $params['height'] ?? $inst['height_mm'] ?? 0),
        'depth' => (float) ($request->input('depth') ?? $params['depth'] ?? $inst['depth_mm'] ?? 0),
    ];
    $moduleType = (string) ($inst['type'] ?? $inst['template_code'] ?? '');
    try {
        Response::json((new ModuleRulesEngine())->recommend($moduleType, $dims, is_array($layout) ? $layout : []));
    } catch (\InvalidArgumentException $e) {
        Response::error('VALIDATION', $e->getMessage(), 422);
    }
});

$router->post('/api/v1/furniture/instances/{id}/validate-layout', static function (Request $request, array $p) {
    Auth::requirePermission('furniture.view');
    $engine = new FurnitureEngine();
    $inst = $engine->get(Auth::requireTenant(), (int) $p['id']);
    $params = $inst['parameters'] ?? [];
    $layout = $request->input('layout');
    if (!is_array($layout)) {
        Response::error('VALIDATION', 'layout object required', 422);
        return;
    }
    $dims = [
        'width' => (float) ($request->input('width') ?? $params['width'] ?? $inst['width_mm'] ?? 0),
        'height' => (float) ($request->input('height') ?? $params['height'] ?? $inst['height_mm'] ?? 0),
        'depth' => (float) ($request->input('depth') ?? $params['depth'] ?? $inst['depth_mm'] ?? 0),
    ];
    $moduleType = (string) ($inst['type'] ?? $inst['template_code'] ?? '');
    try {
        Response::json((new ModuleRulesEngine())->validate($moduleType, $dims, $layout));
    } catch (\InvalidArgumentException $e) {
        Response::error('VALIDATION', $e->getMessage(), 422);
    }
});

$router->post('/api/v1/furniture/instances/{id}/apply-internal-config', static function (Request $request, array $p) {
    Auth::requirePermission('furniture.update');
    $tenantId = Auth::requireTenant();
    $id = (int) $p['id'];
    $configId = (string) ($request->input('config_id') ?? '');
    if ($configId === '') {
        Response::error('VALIDATION', 'config_id required', 422);
        return;
    }
    $furnEngine = new FurnitureEngine();
    $inst = $furnEngine->get($tenantId, $id);
    $params = $inst['parameters'] ?? [];
    $layout = $request->input('layout');
    if (!is_array($layout)) {
        $layout = $params['layout'] ?? [];
    }
    $bayId = $request->input('bay_id');
    $action = strtolower((string) ($request->input('action') ?? 'apply'));
    $rules = new ModuleRulesEngine();
    try {
        if ($action === 'remove') {
            $layout = $rules->remove($configId, is_array($layout) ? $layout : [], is_string($bayId) ? $bayId : null);
            $doorType = null;
            $shutterCount = null;
        } else {
            $result = $rules->apply($configId, is_array($layout) ? $layout : [], is_string($bayId) ? $bayId : null);
            $layout = $result['layout'];
            $doorType = $result['door_type'];
            $shutterCount = $result['shutter_count'];
        }
        $merged = ['layout' => $layout];
        if ($doorType !== null) {
            $merged['door_type'] = $doorType;
        }
        if ($shutterCount !== null) {
            $merged['shutter_count'] = $shutterCount;
        }
        $updated = $furnEngine->updateParameters($tenantId, $id, $merged);
        $moduleType = (string) ($updated['type'] ?? $inst['type'] ?? '');
        $dims = [
            'width' => (float) ($updated['parameters']['width'] ?? $updated['width_mm'] ?? 0),
            'height' => (float) ($updated['parameters']['height'] ?? $updated['height_mm'] ?? 0),
            'depth' => (float) ($updated['parameters']['depth'] ?? $updated['depth_mm'] ?? 0),
        ];
        Response::json([
            'instance' => $updated,
            'recommendation' => $rules->recommend($moduleType, $dims, $updated['parameters']['layout'] ?? []),
        ]);
    } catch (\InvalidArgumentException $e) {
        Response::error('VALIDATION', $e->getMessage(), 422);
    }
});

$router->post('/api/v1/furniture/instances', static function (Request $request) {
    Auth::requirePermission('furniture.create');
    $roomInput = $request->input('room_id');
    try {
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
    } catch (\InvalidArgumentException $e) {
        Response::error('VALIDATION', $e->getMessage(), 422);
    }
});

$router->get('/api/v1/projects/{id}/furniture', static function (Request $r, array $p) {
    Auth::requirePermission('furniture.view');
    Response::json((new FurnitureEngine())->listByProject(Auth::requireTenant(), (int) $p['id']));
});

$router->post('/api/v1/projects/{id}/furniture', static function (Request $request, array $p) {
    Auth::requirePermission('furniture.create');
    $roomInput = $request->input('room_id');
    try {
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
    } catch (\InvalidArgumentException $e) {
        Response::error('VALIDATION', $e->getMessage(), 422);
    }
});

$router->get('/api/v1/furniture/instances/{id}', static function (Request $r, array $p) {
    Auth::requirePermission('furniture.view');
    Response::json((new FurnitureEngine())->get(Auth::requireTenant(), (int) $p['id']));
});

$router->put('/api/v1/furniture/instances/{id}/parameters', static function (Request $request, array $p) {
    Auth::requirePermission('furniture.update');
    try {
        Response::json((new FurnitureEngine())->updateParameters(
            Auth::requireTenant(),
            (int) $p['id'],
            (array) ($request->input('parameters') ?? [])
        ));
    } catch (\InvalidArgumentException $e) {
        Response::error('VALIDATION', $e->getMessage(), 422);
    }
});

$router->put('/api/v1/furniture/instances/{id}/layout', static function (Request $request, array $p) {
    Auth::requirePermission('furniture.update');
    $layout = $request->input('layout');
    if (!is_array($layout)) {
        Response::error('VALIDATION', 'layout object required', 422);
        return;
    }
    try {
        Response::json((new FurnitureEngine())->updateLayout(
            Auth::requireTenant(),
            (int) $p['id'],
            $layout
        ));
    } catch (\InvalidArgumentException $e) {
        Response::error('VALIDATION', $e->getMessage(), 422);
    }
});

$router->put('/api/v1/furniture/instances/{id}/customize', static function (Request $request, array $p) {
    Auth::requirePermission('furniture.update');
    $tenantId = Auth::requireTenant();
    $id = (int) $p['id'];
    $engine = new FurnitureEngine();
    try {
        if (array_key_exists('name', $request->body) || array_key_exists('code', $request->body) || array_key_exists('quantity', $request->body)) {
            $meta = [];
            foreach (['name', 'code', 'quantity'] as $key) {
                if (array_key_exists($key, $request->body)) {
                    $meta[$key] = $request->body[$key];
                }
            }
            $engine->updateMeta($tenantId, $id, $meta);
        }
        $parameters = $request->input('parameters');
        $layout = $request->input('layout');
        $expo = $request->input('expo');
        $merged = is_array($parameters) ? $parameters : [];
        if (is_array($layout)) {
            $merged['layout'] = $layout;
            if (isset($layout['door_type'])) {
                $merged['door_type'] = $layout['door_type'];
            }
        }
        if ($merged !== []) {
            if (isset($merged['layout']) && is_array($merged['layout'])) {
                $layoutEngine = new FurnitureLayoutEngine();
                $merged['layout'] = $layoutEngine->normalizeLayout([
                    'layout' => $merged['layout'],
                    'carcass_thickness' => $merged['carcass_thickness'] ?? 18,
                    'door_type' => $merged['door_type'] ?? ($merged['layout']['door_type'] ?? 'HINGED'),
                ]);
                $merged['door_type'] = $merged['layout']['door_type'] ?? ($merged['door_type'] ?? 'HINGED');
            }
            // Preserve existing expo if customize also updates parameters without expo key
            if (!isset($merged['expo'])) {
                $existing = $engine->get($tenantId, $id);
                if (!empty($existing['parameters']['expo']) && is_array($existing['parameters']['expo'])) {
                    $merged['expo'] = $existing['parameters']['expo'];
                }
            }
            // Preserve fillers unless explicitly provided in parameters
            if (!isset($merged['fillers'])) {
                $existing = $existing ?? $engine->get($tenantId, $id);
                if (!empty($existing['parameters']['fillers']) && is_array($existing['parameters']['fillers'])) {
                    $merged['fillers'] = $existing['parameters']['fillers'];
                }
            } else {
                $merged['fillers'] = \Fmos\Domains\Furniture\FurnitureFillers::normalize(
                    is_array($merged['fillers']) ? $merged['fillers'] : null
                );
            }
            $engine->updateParameters($tenantId, $id, $merged);
        }
        if (is_array($expo)) {
            $engine->updateExpo($tenantId, $id, $expo);
        }
        $specPayload = [];
        foreach (['exterior_finish_id', 'interior_finish_id', 'material_id', 'specification'] as $key) {
            if (array_key_exists($key, $request->body)) {
                $specPayload[$key] = $request->body[$key];
            }
        }
        if ($specPayload !== []) {
            $engine->updateMeta($tenantId, $id, $specPayload);
        }
        Response::json($engine->get($tenantId, $id));
    } catch (\InvalidArgumentException $e) {
        Response::error('VALIDATION', $e->getMessage(), 422);
    }
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

$router->delete('/api/v1/furniture/instances/{id}', static function (Request $r, array $p) {
    Auth::requirePermission('furniture.update');
    (new FurnitureEngine())->softDelete(Auth::requireTenant(), (int) $p['id']);
    Response::json(['deleted' => true]);
});

$router->put('/api/v1/furniture/instances/{id}/position', static function (Request $request, array $p) {
    Auth::requirePermission('furniture.update');
    $pos = $request->input('position');
    if (!is_array($pos)) {
        Response::error('VALIDATION', 'position object required', 422);
        return;
    }
    Response::json((new FurnitureEngine())->updatePosition(Auth::requireTenant(), (int) $p['id'], $pos));
});

$router->get('/api/v1/projects/{id}/kitchen-compositions', static function (Request $r, array $p) {
    Auth::requirePermission('furniture.view');
    Response::json((new KitchenCompositionService())->listByProject(
        Auth::requireTenant(),
        (int) $p['id']
    ));
});

$router->post('/api/v1/projects/{id}/kitchen-compositions', static function (Request $request, array $p) {
    Auth::requirePermission('furniture.create');
    try {
        Response::json((new KitchenCompositionService())->createLShape(
            Auth::requireTenant(),
            (int) $p['id'],
            [
                'name' => $request->input('name'),
                'height_mm' => $request->input('height_mm'),
                'depth_mm' => $request->input('depth_mm'),
                'corner_size_mm' => $request->input('corner_size_mm'),
                'run_a_length_mm' => $request->input('run_a_length_mm'),
                'run_b_length_mm' => $request->input('run_b_length_mm'),
                'module_width_mm' => $request->input('module_width_mm'),
                'run_a_modules' => $request->input('run_a_modules'),
                'run_b_modules' => $request->input('run_b_modules'),
                'exterior_finish_id' => $request->input('exterior_finish_id'),
                'interior_finish_id' => $request->input('interior_finish_id'),
                'material_id' => $request->input('material_id'),
            ]
        ), 201);
    } catch (\InvalidArgumentException $e) {
        Response::error('VALIDATION', $e->getMessage(), 422);
    } catch (\RuntimeException $e) {
        Response::error('ERROR', $e->getMessage(), 400);
    }
});

$router->get('/api/v1/kitchen-compositions/{id}', static function (Request $r, array $p) {
    Auth::requirePermission('furniture.view');
    Response::json((new KitchenCompositionService())->get(
        Auth::requireTenant(),
        (int) $p['id']
    ));
});

$router->delete('/api/v1/kitchen-compositions/{id}', static function (Request $r, array $p) {
    Auth::requirePermission('furniture.update');
    (new KitchenCompositionService())->softDelete(Auth::requireTenant(), (int) $p['id']);
    Response::json(['deleted' => true]);
});

$router->get('/api/v1/kitchen-compositions/{id}/2d', static function (Request $r, array $p) {
    Auth::requirePermission('furniture.view');
    Response::json((new KitchenCompositionService())->drawingPlan(
        Auth::requireTenant(),
        (int) $p['id']
    ));
});

$router->get('/api/v1/kitchen-compositions/{id}/3d-model', static function (Request $r, array $p) {
    Auth::requirePermission('furniture.view');
    Response::json((new KitchenCompositionService())->model3d(
        Auth::requireTenant(),
        (int) $p['id']
    ));
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

$router->post('/api/v1/manufacturing/jobs/{id}/cutlist/export', static function (Request $r, array $p) {
    Auth::requirePermission('manufacturing.view');
    Response::json((new ExportService())->manufacturingJobCsv(Auth::requireTenant(), (int) $p['id']));
});

$router->post('/api/v1/manufacturing/cutlist/export', static function (Request $request) {
    Auth::requirePermission('manufacturing.view');
    $ids = $request->input('package_ids');
    if (!is_array($ids) || $ids === []) {
        Response::error('VALIDATION', 'package_ids required', 422);
        return;
    }
    Response::json((new ExportService())->manufacturingPackagesCsv(Auth::requireTenant(), $ids));
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

$router->post('/api/v1/projects/{id}/nesting/sheet-plan', static function (Request $request, array $p) {
    Auth::requirePermission('nesting.generate');
    $packageIds = $request->input('package_ids');
    if (!is_array($packageIds)) {
        $packageIds = [];
    }
    $plan = (new SheetPlanService())->buildProjectPlan(
        Auth::requireTenant(),
        (int) $p['id'],
        $packageIds
    );
    Response::json($plan, 201);
});

$router->post('/api/v1/projects/{id}/nesting/sheet-plan/pdf', static function (Request $request, array $p) {
    Auth::requirePermission('nesting.view');
    $tenantId = Auth::requireTenant();
    $projectId = (int) $p['id'];
    $packageIds = $request->input('package_ids');
    if (!is_array($packageIds)) {
        $packageIds = [];
    }
    $svc = new SheetPlanService();
    $plan = $svc->buildProjectPlan($tenantId, $projectId, $packageIds);
    $stmt = Database::connection()->prepare('SELECT name FROM projects WHERE id=? AND tenant_id=?');
    $stmt->execute([$projectId, $tenantId]);
    $projectName = (string) ($stmt->fetchColumn() ?: ('Project ' . $projectId));
    Response::json($svc->renderPdf($plan, $projectName));
});

$router->get('/api/v1/manufacturing/{id}/labels', static function (Request $r, array $p) {
    Auth::requirePermission('nesting.view');
    Response::json((new ManufacturingService())->labels(Auth::requireTenant(), (int) $p['id']));
});

$router->get('/api/v1/manufacturing/{id}', static function (Request $r, array $p) {
    Auth::requirePermission('manufacturing.view');
    Response::json((new ManufacturingService())->getPackage(Auth::requireTenant(), (int) $p['id']));
});
