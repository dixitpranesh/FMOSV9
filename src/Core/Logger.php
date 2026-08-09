<?php

declare(strict_types=1);

namespace Fmos\Core;

final class Logger
{
    private static ?string $requestId = null;

    public static function requestId(): string
    {
        if (self::$requestId === null) {
            self::$requestId = bin2hex(random_bytes(8));
        }
        return self::$requestId;
    }

    public static function setRequestId(string $id): void
    {
        self::$requestId = $id;
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        $dir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $line = json_encode([
            'ts' => date('c'),
            'level' => $level,
            'request_id' => self::requestId(),
            'message' => $message,
            'context' => $context,
        ], JSON_UNESCAPED_UNICODE);
        file_put_contents($dir . '/app.log', $line . PHP_EOL, FILE_APPEND);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }
}
