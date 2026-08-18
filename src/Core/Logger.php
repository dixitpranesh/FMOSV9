<?php

declare(strict_types=1);

namespace Fmos\Core;

/**
 * Structured JSON application logger.
 *
 * Writes under storage/logs/ (outside public web root when Document Root = public/).
 * Never pass secrets, passwords, tokens, or full credentials in $context.
 */
final class Logger
{
    public const LEVEL_DEBUG = 'DEBUG';
    public const LEVEL_INFO = 'INFO';
    public const LEVEL_WARNING = 'WARNING';
    public const LEVEL_ERROR = 'ERROR';
    public const LEVEL_CRITICAL = 'CRITICAL';

    public const CHANNEL_APP = 'app';
    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_ERROR = 'error';

    private static ?string $requestId = null;

    /** @var array<string,mixed> */
    private static array $correlation = [];

    public static function requestId(): string
    {
        if (self::$requestId === null) {
            self::$requestId = 'req_' . date('Ymd_His') . '_' . strtoupper(bin2hex(random_bytes(3)));
        }
        return self::$requestId;
    }

    public static function setRequestId(string $id): void
    {
        self::$requestId = $id;
    }

    /** Bind correlation fields (registration_id, user_id, …) onto subsequent log lines. */
    public static function correlate(array $fields): void
    {
        foreach ($fields as $k => $v) {
            if ($v === null || $v === '') {
                unset(self::$correlation[$k]);
                continue;
            }
            self::$correlation[(string) $k] = $v;
        }
    }

    public static function clearCorrelation(): void
    {
        self::$correlation = [];
    }

    public static function log(string $level, string $message, array $context = [], string $channel = self::CHANNEL_APP): void
    {
        $level = strtoupper($level);
        $dir = self::logDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $payload = array_merge(self::$correlation, $context);
        $payload = self::scrub($payload);

        $line = json_encode([
            'timestamp' => date('c'),
            'level' => $level,
            'channel' => $channel,
            'event' => $payload['event'] ?? null,
            'request_id' => self::requestId(),
            'message' => self::sanitizeMessage($message),
            'context' => $payload,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($line === false) {
            $line = '{"level":"ERROR","message":"logger_json_encode_failed","request_id":"' . self::requestId() . '"}';
        }

        $date = date('Y-m-d');
        @file_put_contents($dir . '/app-' . $date . '.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);

        if ($channel === self::CHANNEL_EMAIL || str_starts_with((string) ($payload['event'] ?? ''), 'EMAIL_')
            || str_starts_with((string) ($payload['event'] ?? ''), 'SMTP_')
            || str_starts_with((string) ($payload['event'] ?? ''), 'VERIFICATION_EMAIL')) {
            @file_put_contents($dir . '/email-' . $date . '.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        if (in_array($level, [self::LEVEL_ERROR, self::LEVEL_CRITICAL], true)) {
            @file_put_contents($dir . '/error-' . $date . '.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        // Keep legacy path for older tooling.
        @file_put_contents($dir . '/app.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);

        self::pruneOldLogs($dir, 14);
    }

    public static function event(string $event, string $level = self::LEVEL_INFO, array $context = [], string $channel = self::CHANNEL_APP): void
    {
        $context['event'] = $event;
        self::log($level, $event, $context, $channel);
    }

    public static function debug(string $message, array $context = []): void
    {
        self::log(self::LEVEL_DEBUG, $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log(self::LEVEL_INFO, $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log(self::LEVEL_WARNING, $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log(self::LEVEL_ERROR, $message, $context, self::CHANNEL_ERROR);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::log(self::LEVEL_CRITICAL, $message, $context, self::CHANNEL_ERROR);
    }

    public static function emailDomain(string $email): string
    {
        $parts = explode('@', strtolower(trim($email)));
        return $parts[1] ?? '';
    }

    public static function maskEmail(string $email): string
    {
        $email = strtolower(trim($email));
        $at = strpos($email, '@');
        if ($at === false) {
            return '***';
        }
        $local = substr($email, 0, $at);
        $domain = substr($email, $at + 1);
        $keep = min(2, max(0, strlen($local)));
        return substr($local, 0, $keep) . '****@' . $domain;
    }

    /** Safe token metadata — never pass the raw token here. */
    public static function tokenMeta(string $rawToken, ?string $expiresAt = null): array
    {
        $hash = hash('sha256', $rawToken);
        return [
            'token_length' => strlen($rawToken),
            'token_hash_prefix' => substr($hash, 0, 8),
            'expires_at' => $expiresAt,
        ];
    }

    public static function fromDomain(string $fromEmail): string
    {
        return self::emailDomain($fromEmail);
    }

    public static function mailConfigStatus(): array
    {
        $driver = strtolower((string) (Env::get('MAIL_DRIVER', 'log') ?? 'log'));
        $host = Env::get('MAIL_HOST');
        $user = Env::get('MAIL_USERNAME');
        $pass = Env::get('MAIL_PASSWORD');
        $from = Env::get('MAIL_FROM_ADDRESS') ?: Env::get('MAIL_FROM_ACCOUNTS', 'accounts@fmos.in');
        return [
            'mail_driver' => $driver,
            'smtp_host_configured' => is_string($host) && $host !== '',
            'smtp_port' => (int) (Env::get('MAIL_PORT', '587') ?? '587'),
            'smtp_encryption' => strtolower((string) (Env::get('MAIL_ENCRYPTION', 'tls') ?? 'tls')),
            'smtp_username_configured' => is_string($user) && $user !== '',
            'smtp_password_configured' => is_string($pass) && $pass !== '',
            'from_domain' => is_string($from) ? self::emailDomain($from) : '',
            'app_url_configured' => (string) (Env::get('APP_URL', '') ?? '') !== '',
            'app_url_host' => self::urlHost((string) (Env::get('APP_URL', '') ?? '')),
        ];
    }

    public static function classifySmtpError(string $message): string
    {
        $m = strtolower($message);
        return match (true) {
            str_contains($m, 'not configured') => 'SMTP_NOT_CONFIGURED',
            str_contains($m, 'connect failed'), str_contains($m, 'connection refused'), str_contains($m, 'timed out'), str_contains($m, 'timeout') => 'SMTP_CONNECTION_FAILED',
            str_contains($m, '535'), str_contains($m, 'auth') && str_contains($m, 'fail') => 'SMTP_AUTHENTICATION_FAILED',
            str_contains($m, 'starttls'), str_contains($m, 'tls'), str_contains($m, 'ssl'), str_contains($m, 'crypto') => 'SMTP_TLS_FAILED',
            str_contains($m, '550'), str_contains($m, 'mailbox'), str_contains($m, 'recipient') => 'SMTP_RECIPIENT_REJECTED',
            str_contains($m, '553'), str_contains($m, 'sender') => 'SMTP_SENDER_REJECTED',
            str_contains($m, '421'), str_contains($m, 'rate') => 'SMTP_RATE_LIMITED',
            str_contains($m, 'unexpected response') => 'SMTP_PROTOCOL_ERROR',
            default => 'SMTP_SEND_FAILED',
        };
    }

    public static function sanitizeMessage(string $message): string
    {
        $message = preg_replace('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/', '[email]', $message) ?? $message;
        // Strip common secret-looking assignments if they leak into exception text.
        $message = preg_replace('/(password|passwd|pwd|secret|api[_-]?key|token)\s*[:=]\s*\S+/i', '$1=[redacted]', $message) ?? $message;
        return $message;
    }

    private static function urlHost(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        return is_string($host) ? $host : '';
    }

    private static function logDir(): string
    {
        $base = Env::get('STORAGE_PATH', 'storage') ?? 'storage';
        if (!str_starts_with($base, '/') && !preg_match('#^[A-Za-z]:[/\\\\]#', $base)) {
            $base = dirname(__DIR__, 2) . '/' . $base;
        }
        return rtrim($base, '/\\') . '/logs';
    }

    /** @param array<string,mixed> $context */
    private static function scrub(array $context): array
    {
        $denyExact = [
            'password', 'password_hash', 'password_confirm', 'confirm_password',
            'token', 'raw_token', 'verify_token', 'debug_verify_token', 'csrf', 'csrf_token',
            'authorization', 'cookie', 'cookies', 'session', 'api_token', 'jwt',
            'mail_password', 'smtp_password', 'smtp_pass', 'api_key', 'secret',
            'bootstrap_secret', 'app_key',
        ];
        $out = [];
        foreach ($context as $key => $value) {
            $lk = strtolower((string) $key);
            if (in_array($lk, $denyExact, true) || str_contains($lk, 'password') || str_contains($lk, 'secret')) {
                continue;
            }
            if (is_array($value)) {
                $out[$key] = self::scrub($value);
                continue;
            }
            if (is_string($value) && strlen($value) > 2000) {
                $out[$key] = substr(self::sanitizeMessage($value), 0, 500) . '…[truncated]';
                continue;
            }
            if (is_string($value)) {
                $out[$key] = self::sanitizeMessage($value);
                continue;
            }
            $out[$key] = $value;
        }
        return $out;
    }

    private static function pruneOldLogs(string $dir, int $keepDays): void
    {
        // Cheap probabilistic prune so we do not scan on every write.
        if (random_int(1, 100) !== 1) {
            return;
        }
        $cutoff = time() - ($keepDays * 86400);
        foreach (['app', 'email', 'error'] as $prefix) {
            foreach (glob($dir . '/' . $prefix . '-*.log') ?: [] as $file) {
                $mtime = @filemtime($file);
                if ($mtime !== false && $mtime < $cutoff) {
                    @unlink($file);
                }
            }
        }
    }
}
