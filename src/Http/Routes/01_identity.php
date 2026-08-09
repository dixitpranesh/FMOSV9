<?php

declare(strict_types=1);

/** @var \Fmos\Core\Router $router */

use Fmos\Core\Audit;
use Fmos\Core\Auth;
use Fmos\Core\Database;
use Fmos\Core\Request;
use Fmos\Core\Response;
use Fmos\Domains\Identity\RbacSeeder;
use Fmos\Domains\Tenant\TenantService;

$router->post('/api/v1/auth/login', static function (Request $request) {
    $email = trim((string) $request->input('email', ''));
    $password = (string) $request->input('password', '');
    $user = Auth::attempt($email, $password);
    if (!$user) {
        Response::error('AUTH_REQUIRED', 'Invalid credentials', 401);
        return;
    }
    Audit::record('LOGIN', 'user', $user['id']);
    Response::json([
        'user' => $user,
        'token' => Auth::apiToken(),
        'csrf' => Auth::csrfToken(),
    ]);
}, false);

$router->post('/api/v1/auth/logout', static function () {
    Audit::record('LOGOUT', 'user', Auth::id());
    Auth::logout();
    Response::json(['logged_out' => true]);
}, false);

$router->get('/api/v1/auth/me', static function () {
    $user = Auth::user();
    if (!$user) {
        Response::error('AUTH_REQUIRED', 'Authentication required', 401);
        return;
    }
    Response::json(Auth::publicUser($user));
});

$router->post('/api/v1/tenants', static function (Request $request) {
    // Bootstrap endpoint for demo/platform setup
    $service = new TenantService();
    $result = $service->createTenant(
        (string) $request->input('code'),
        (string) $request->input('name'),
        (string) $request->input('owner_email'),
        (string) $request->input('owner_name'),
        (string) $request->input('password'),
    );
    Response::json($result, 201);
}, false);

$router->get('/api/v1/organizations', static function () {
    Auth::requirePermission('organization.view');
    $tenantId = Auth::requireTenant();
    $service = new TenantService();
    Response::json($service->listOrganizations($tenantId));
});

$router->post('/api/v1/organizations', static function (Request $request) {
    Auth::requirePermission('organization.create');
    $tenantId = Auth::requireTenant();
    $service = new TenantService();
    $org = $service->createOrganization(
        $tenantId,
        (string) $request->input('code'),
        (string) $request->input('name'),
    );
    Response::json($org, 201);
});

$router->get('/api/v1/roles', static function () {
    Auth::requirePermission('role.view');
    $pdo = Database::connection();
    $rows = $pdo->query('SELECT id, code, name, is_system FROM roles WHERE tenant_id IS NULL ORDER BY id')->fetchAll();
    Response::json($rows);
});

$router->get('/api/v1/permissions', static function () {
    Auth::requirePermission('role.view');
    $pdo = Database::connection();
    Response::json($pdo->query('SELECT id, code, module, description FROM permissions ORDER BY module, code')->fetchAll());
});

$router->get('/api/v1/users', static function () {
    Auth::requirePermission('user.view');
    $tenantId = Auth::requireTenant();
    $pdo = Database::connection();
    $stmt = $pdo->prepare('SELECT id, email, name, status, created_at FROM users WHERE tenant_id = ? AND deleted_at IS NULL');
    $stmt->execute([$tenantId]);
    Response::json($stmt->fetchAll());
});

$router->post('/api/v1/rbac/seed', static function () {
    RbacSeeder::seed();
    Response::json(['seeded' => true, 'roles' => count(RbacSeeder::roleCodes())]);
}, false);
