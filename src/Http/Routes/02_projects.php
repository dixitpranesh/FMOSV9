<?php

declare(strict_types=1);

/** @var \Fmos\Core\Router $router */

use Fmos\Core\Auth;
use Fmos\Core\Request;
use Fmos\Core\Response;
use Fmos\Domains\Project\ProjectService;

$router->get('/api/v1/clients', static function () {
    Auth::requirePermission('client.view');
    $svc = new ProjectService();
    Response::json($svc->listClients(Auth::requireTenant()));
});

$router->post('/api/v1/clients', static function (Request $request) {
    Auth::requirePermission('client.create');
    $svc = new ProjectService();
    Response::json($svc->createClient(Auth::requireTenant(), [
        'name' => (string) $request->input('name'),
        'company' => $request->input('company'),
        'email' => $request->input('email'),
        'phone' => $request->input('phone'),
        'address' => $request->input('address'),
    ]), 201);
});

$router->get('/api/v1/projects', static function () {
    Auth::requirePermission('project.view');
    $svc = new ProjectService();
    Response::json($svc->listProjects(Auth::requireTenant()));
});

$router->post('/api/v1/projects', static function (Request $request) {
    Auth::requirePermission('project.create');
    $svc = new ProjectService();
    Response::json($svc->createProject(Auth::requireTenant(), [
        'organization_id' => (int) $request->input('organization_id'),
        'client_id' => (int) $request->input('client_id'),
        'name' => (string) $request->input('name'),
        'project_type' => $request->input('project_type'),
    ]), 201);
});

$router->get('/api/v1/projects/{id}', static function (Request $request, array $params) {
    Auth::requirePermission('project.view');
    $svc = new ProjectService();
    Response::json($svc->getProject(Auth::requireTenant(), (int) $params['id']));
});

$router->patch('/api/v1/projects/{id}/workflow', static function (Request $request, array $params) {
    Auth::requirePermission('project.update');
    $svc = new ProjectService();
    try {
        $project = $svc->updateWorkflow(
            Auth::requireTenant(),
            (int) $params['id'],
            $request->input('status') !== null ? (string) $request->input('status') : null,
            $request->input('workflow_stage') !== null ? (string) $request->input('workflow_stage') : null,
            (int) $request->input('version'),
        );
        Response::json($project);
    } catch (\RuntimeException $e) {
        if ($e->getMessage() === 'STALE_DATA') {
            Response::error('STALE_DATA', 'Project version conflict', 409);
            return;
        }
        throw $e;
    }
});
