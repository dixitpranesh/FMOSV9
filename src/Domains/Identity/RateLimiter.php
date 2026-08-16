<?php

declare(strict_types=1);

namespace Fmos\Domains\Identity;

use Fmos\Core\Env;

/**
 * File-backed rate limiter with exclusive locking.
 */
final class RateLimiter
{
    public static function hit(string $bucket, string $key, int $max, int $windowSeconds): bool
    {
        $dir = self::dir();
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $bucket . '_' . $key) ?: 'key';
        $path = $dir . '/' . $safe . '.json';
        $fp = fopen($path, 'c+');
        if ($fp === false) {
            return true;
        }
        try {
            if (!flock($fp, LOCK_EX)) {
                return true;
            }
            $raw = stream_get_contents($fp);
            $now = time();
            $data = ['window_start' => $now, 'count' => 0];
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $data = $decoded;
                }
            }
            $start = (int) ($data['window_start'] ?? $now);
            $count = (int) ($data['count'] ?? 0);
            if ($now - $start >= $windowSeconds) {
                $start = $now;
                $count = 0;
            }
            $count++;
            $payload = json_encode(['window_start' => $start, 'count' => $count]);
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, (string) $payload);
            fflush($fp);
            flock($fp, LOCK_UN);
            return $count <= $max;
        } finally {
            fclose($fp);
        }
    }

    public static function allowOrFail(string $bucket, string $key, int $max, int $windowSeconds, string $message = 'Too many requests. Please try again later.'): void
    {
        if (!self::hit($bucket, $key, $max, $windowSeconds)) {
            \Fmos\Core\Response::error('RATE_LIMITED', $message, 429);
            exit;
        }
    }

    private static function dir(): string
    {
        $base = Env::get('STORAGE_PATH', 'storage') ?? 'storage';
        if (!str_starts_with($base, '/') && !preg_match('#^[A-Za-z]:\\\\#', $base)) {
            $base = dirname(__DIR__, 3) . '/' . $base;
        }
        return rtrim($base, '/\\') . '/rate_limits';
    }
}
