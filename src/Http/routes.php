<?php

declare(strict_types=1);

/** @var \Fmos\Core\Router $router */

use Fmos\Core\Response;

// Routes registered by phase modules
$phaseRoutes = glob(dirname(__DIR__) . '/Http/Routes/*.php') ?: [];
sort($phaseRoutes);
foreach ($phaseRoutes as $file) {
    require $file;
}

$router->get('/api/v1/ping', static function () {
    Response::json(['pong' => true]);
}, false);
