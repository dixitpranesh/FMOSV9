<?php

declare(strict_types=1);

namespace Fmos\Core;

final class Router
{
    /** @var array<int, array{method:string,pattern:string,handler:callable,auth:bool}> */
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler, bool $auth = true): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
            'auth' => $auth,
        ];
    }

    public function get(string $pattern, callable $handler, bool $auth = true): void
    {
        $this->add('GET', $pattern, $handler, $auth);
    }

    public function post(string $pattern, callable $handler, bool $auth = true): void
    {
        $this->add('POST', $pattern, $handler, $auth);
    }

    public function put(string $pattern, callable $handler, bool $auth = true): void
    {
        $this->add('PUT', $pattern, $handler, $auth);
    }

    public function patch(string $pattern, callable $handler, bool $auth = true): void
    {
        $this->add('PATCH', $pattern, $handler, $auth);
    }

    public function delete(string $pattern, callable $handler, bool $auth = true): void
    {
        $this->add('DELETE', $pattern, $handler, $auth);
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }
            $params = $this->match($route['pattern'], $request->path);
            if ($params === null) {
                continue;
            }

            if ($route['auth']) {
                $user = Auth::user();
                if ($user === null) {
                    Response::error('AUTH_REQUIRED', 'Authentication required', 401);
                    return;
                }
            }

            ($route['handler'])($request, $params);
            return;
        }

        Response::error('RESOURCE_NOT_FOUND', 'Route not found', 404);
    }

    /** @return array<string, string>|null */
    private function match(string $pattern, string $path): ?array
    {
        $regex = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        if (!preg_match($regex, $path, $matches)) {
            return null;
        }
        $params = [];
        foreach ($matches as $key => $value) {
            if (!is_int($key)) {
                $params[$key] = $value;
            }
        }
        return $params;
    }
}
