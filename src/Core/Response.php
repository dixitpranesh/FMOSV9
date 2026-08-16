<?php

declare(strict_types=1);

namespace Fmos\Core;

final class Response
{
    public static function json(mixed $data, int $status = 200, array $meta = []): void
    {
        http_response_code($status);
        Security::applyHeaders();
        header('Content-Type: application/json; charset=utf-8');
        header('X-Request-Id: ' . (Logger::requestId()));
        echo json_encode([
            'success' => $status >= 200 && $status < 300,
            'data' => $data,
            'meta' => (object) $meta,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function error(string $code, string $message, int $status = 400, array $details = []): void
    {
        http_response_code($status);
        Security::applyHeaders();
        header('Content-Type: application/json; charset=utf-8');
        header('X-Request-Id: ' . (Logger::requestId()));
        $env = Env::get('APP_ENV', 'local') ?? 'local';
        $debug = Env::bool('APP_DEBUG', false);
        if (!($debug && $env === 'local')) {
            $details = [];
        }
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
            'meta' => (object) ['request_id' => Logger::requestId()],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function html(string $html, int $status = 200): void
    {
        http_response_code($status);
        Security::applyHeaders();
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
    }
}
