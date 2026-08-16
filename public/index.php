<?php

declare(strict_types=1);

use Fmos\Core\Auth;
use Fmos\Core\Database;
use Fmos\Core\Env;
use Fmos\Core\Logger;
use Fmos\Core\Request;
use Fmos\Core\Response;
use Fmos\Core\Router;

require dirname(__DIR__) . '/vendor/autoload.php';

Env::load(dirname(__DIR__) . '/.env');
Logger::requestId();
Auth::startSession();

$router = new Router();

$router->get('/api/v1/health', static function () {
    try {
        Database::connection()->query('SELECT 1');
        Response::json([
            'status' => 'ok',
            'app' => Env::get('APP_NAME', 'FMOS'),
            'time' => date('c'),
            'phase' => '0-foundation',
        ]);
    } catch (Throwable $e) {
        Logger::error('Health check failed', ['error' => $e->getMessage()]);
        Response::error('INTERNAL_ERROR', 'Health check failed', 500);
    }
}, false);

require dirname(__DIR__) . '/src/Http/routes.php';

$request = Request::fromGlobals();

// Serve SPA shell for non-API routes
if (!str_starts_with($request->path, '/api/')) {
    $file = dirname(__DIR__) . '/public/app.html';
    if (is_file($file)) {
        Response::html(file_get_contents($file) ?: '');
        return;
    }
}

try {
    $router->dispatch($request);
} catch (Throwable $e) {
    Logger::error('Unhandled exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    Response::error('INTERNAL_ERROR', \Fmos\Core\Security::clientErrorMessage($e), 500);
}
