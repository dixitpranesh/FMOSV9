<?php

declare(strict_types=1);

namespace Fmos\Core;

final class Env
{
    private static bool $loaded = false;

    /** @var array<string, string> */
    private static array $vars = [];

    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }

        if (!is_file($path)) {
            throw new \RuntimeException(".env not found at {$path}");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            // Strip UTF-8 BOM if present on first/any line.
            if (str_starts_with($line, "\xEF\xBB\xBF")) {
                $line = substr($line, 3);
            }
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = self::unquote(trim($value));
            self::$vars[$key] = $value;
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }

        self::$loaded = true;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = self::$vars[$key] ?? $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }
        return self::unquote(trim((string) $value));
    }

    public static function set(string $key, string $value): void
    {
        $value = self::unquote(trim($value));
        self::$vars[$key] = $value;
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }
        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }

    /** Remove surrounding single/double quotes commonly copied into .env files. */
    private static function unquote(string $value): string
    {
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                return substr($value, 1, -1);
            }
        }
        return $value;
    }
}
