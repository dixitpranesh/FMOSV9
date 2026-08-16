<?php

declare(strict_types=1);

namespace Fmos\Core;

/**
 * Shared security headers and request origin checks.
 */
final class Security
{
    public static function applyHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((Env::get('APP_ENV', 'local') ?? 'local') === 'production');
        if ($secure) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        // Prevent caching of API JSON by shared caches
        if (str_starts_with((string) ($_SERVER['REQUEST_URI'] ?? ''), '/api/')) {
            header('Cache-Control: no-store');
        }
    }

    public static function assertTrustedOrigin(Request $request): void
    {
        if (!in_array($request->method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }
        $origin = $request->header('Origin');
        $referer = $request->header('Referer');
        // Non-browser clients often omit Origin; allow when absent.
        if (($origin === null || $origin === '') && ($referer === null || $referer === '')) {
            return;
        }
        $allowed = self::allowedOrigins();
        if ($origin !== null && $origin !== '') {
            if (!self::originAllowed($origin, $allowed)) {
                Response::error('ORIGIN_DENIED', 'Request origin is not allowed.', 403);
                exit;
            }
            return;
        }
        if ($referer !== null && $referer !== '') {
            $refOrigin = self::originFromUrl($referer);
            if ($refOrigin === null || !self::originAllowed($refOrigin, $allowed)) {
                Response::error('ORIGIN_DENIED', 'Request origin is not allowed.', 403);
                exit;
            }
        }
    }

    /** @return list<string> */
    private static function allowedOrigins(): array
    {
        $appUrl = rtrim((string) (Env::get('APP_URL', 'http://localhost:8080') ?? 'http://localhost:8080'), '/');
        $list = [$appUrl];
        $extra = Env::get('CORS_ALLOWED_ORIGINS', '');
        if (is_string($extra) && $extra !== '') {
            foreach (explode(',', $extra) as $o) {
                $o = trim($o);
                if ($o !== '') {
                    $list[] = rtrim($o, '/');
                }
            }
        }
        return array_values(array_unique($list));
    }

    /** @param list<string> $allowed */
    private static function originAllowed(string $origin, array $allowed): bool
    {
        $origin = rtrim($origin, '/');
        foreach ($allowed as $a) {
            if (strcasecmp($origin, $a) === 0) {
                return true;
            }
        }
        return false;
    }

    private static function originFromUrl(string $url): ?string
    {
        $p = parse_url($url);
        if (!is_array($p) || empty($p['scheme']) || empty($p['host'])) {
            return null;
        }
        $port = isset($p['port']) ? ':' . $p['port'] : '';
        return $p['scheme'] . '://' . $p['host'] . $port;
    }

    public static function clientErrorMessage(\Throwable $e): string
    {
        $debug = Env::bool('APP_DEBUG', false);
        $env = Env::get('APP_ENV', 'local') ?? 'local';
        if ($debug && $env === 'local') {
            return $e->getMessage();
        }
        return 'Internal server error';
    }
}
