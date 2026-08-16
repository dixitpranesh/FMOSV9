<?php

declare(strict_types=1);

namespace Fmos\Core;

final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $body,
        public readonly array $headers,
        public readonly array $server,
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/') ?: '/';

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            }
        }

        $body = $_POST;
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input') ?: '';
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }

        return new self($method, $path, $_GET, $body, $headers, $_SERVER);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function header(string $name, mixed $default = null): mixed
    {
        foreach ($this->headers as $k => $v) {
            if (strcasecmp((string) $k, $name) === 0) {
                return $v;
            }
        }
        // Common CGI variants
        $cgi = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->server[$cgi] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->headers['Authorization'] ?? $this->header('Authorization', '') ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', (string) $auth, $m)) {
            return trim($m[1]);
        }
        return null;
    }
}
