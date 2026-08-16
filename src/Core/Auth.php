<?php

declare(strict_types=1);

namespace Fmos\Core;

use PDO;

final class Auth
{
    private static ?array $user = null;

    public static function startSession(): void
    {
        if (PHP_SAPI === 'cli' || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $name = Env::get('SESSION_NAME', 'fmos_session') ?? 'fmos_session';
        session_name($name);
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((Env::get('APP_ENV', 'local') ?? 'local') === 'production');
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'cookie_secure' => $secure,
            'use_strict_mode' => true,
        ]);
    }

    /**
     * @return array{user?:array,error?:array}|array
     * Returns public user on success, or throws via structured result for callers.
     */
    public static function attempt(string $email, string $password): ?array
    {
        $email = strtolower(trim($email));
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            if ($user) {
                self::recordFailedLogin((int) $user['id']);
            }
            return null;
        }

        $status = (string) ($user['status'] ?? 'ACTIVE');
        if (in_array($status, ['SUSPENDED', 'LOCKED', 'DEACTIVATED'], true)) {
            throw new AuthException('ACCOUNT_DISABLED', 'This account cannot sign in.', 403);
        }
        if (!empty($user['locked_until']) && strtotime((string) $user['locked_until']) > time()) {
            throw new AuthException('ACCOUNT_LOCKED', 'Too many failed attempts. Try again later.', 423);
        }
        if ($status === 'PENDING_EMAIL_VERIFICATION' || empty($user['email_verified_at'])) {
            throw new AuthException(
                'EMAIL_NOT_VERIFIED',
                'Your email address has not been verified. Please verify your email before signing in.',
                403
            );
        }

        $pdo->prepare('UPDATE users SET failed_login_count = 0, locked_until = NULL, updated_at = NOW() WHERE id = ?')
            ->execute([(int) $user['id']]);

        self::login($user);
        return self::publicUser($user);
    }

    public static function login(array $user): void
    {
        self::startSession();
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['tenant_id'] = $user['tenant_id'] !== null ? (int) $user['tenant_id'] : null;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        self::$user = null;

        $pdo = Database::connection();
        $token = bin2hex(random_bytes(32));
        $expires = (int) (Env::get('SESSION_LIFETIME', '7200') ?? '7200');
        $stmt = $pdo->prepare('INSERT INTO sessions (user_id, token, expires_at, ip_address, user_agent, created_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), ?, ?, NOW())');
        $stmt->execute([
            (int) $user['id'],
            hash('sha256', $token),
            $expires,
            $_SERVER['REMOTE_ADDR'] ?? null,
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
        $_SESSION['api_token'] = $token;
    }

    public static function logout(): void
    {
        self::startSession();
        if (!empty($_SESSION['api_token'])) {
            $pdo = Database::connection();
            $stmt = $pdo->prepare('DELETE FROM sessions WHERE token = ?');
            $stmt->execute([hash('sha256', $_SESSION['api_token'])]);
        }
        $_SESSION = [];
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                $opts = [
                    'expires' => time() - 42000,
                    'path' => $params['path'] ?: '/',
                    'domain' => $params['domain'] ?: '',
                    'secure' => (bool) $params['secure'],
                    'httponly' => (bool) $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax',
                ];
                setcookie(session_name(), '', $opts);
            }
            session_destroy();
        }
        self::$user = null;
    }

    public static function revokeAllSessions(int $userId): void
    {
        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM sessions WHERE user_id = ?')->execute([$userId]);
    }

    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }

        self::startSession();
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            $request = Request::fromGlobals();
            $bearer = $request->bearerToken();
            if ($bearer) {
                $pdo = Database::connection();
                $stmt = $pdo->prepare('SELECT u.* FROM sessions s INNER JOIN users u ON u.id = s.user_id WHERE s.token = ? AND s.expires_at > NOW() AND u.deleted_at IS NULL LIMIT 1');
                $stmt->execute([hash('sha256', $bearer)]);
                $user = $stmt->fetch();
                if ($user && self::isAccountUsable($user)) {
                    self::$user = $user;
                    return self::$user;
                }
                if ($user && !self::isAccountUsable($user)) {
                    self::revokeAllSessions((int) $user['id']);
                }
            }
            return null;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([(int) $userId]);
        $user = $stmt->fetch();
        if ($user && !self::isAccountUsable($user)) {
            self::revokeAllSessions((int) $user['id']);
            if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION = [];
            }
            self::$user = null;
            return null;
        }
        self::$user = $user ?: null;
        return self::$user;
    }

    /** @param array<string,mixed> $user */
    public static function isAccountUsable(array $user): bool
    {
        $status = (string) ($user['status'] ?? 'ACTIVE');
        if (in_array($status, ['SUSPENDED', 'LOCKED', 'DEACTIVATED', 'PENDING_EMAIL_VERIFICATION'], true)) {
            return false;
        }
        if (empty($user['email_verified_at'])) {
            return false;
        }
        if (!empty($user['locked_until']) && strtotime((string) $user['locked_until']) > time()) {
            return false;
        }
        return true;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int) $user['id'] : null;
    }

    public static function tenantId(): ?int
    {
        $user = self::user();
        if (!$user) {
            return null;
        }
        return $user['tenant_id'] !== null ? (int) $user['tenant_id'] : null;
    }

    public static function csrfToken(): string
    {
        self::startSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrf(?string $token): bool
    {
        self::startSession();
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        if ($sessionToken === '' || $token === null || $token === '') {
            return false;
        }
        return hash_equals((string) $sessionToken, $token);
    }

    public static function apiToken(): ?string
    {
        self::startSession();
        return $_SESSION['api_token'] ?? null;
    }

    public static function publicUser(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'tenant_id' => $user['tenant_id'] !== null ? (int) $user['tenant_id'] : null,
            'email' => $user['email'],
            'name' => $user['name'],
            'first_name' => $user['first_name'] ?? null,
            'last_name' => $user['last_name'] ?? null,
            'display_name' => $user['display_name'] ?? null,
            'registration_type' => $user['registration_type'] ?? null,
            'email_verified' => !empty($user['email_verified_at']),
            'status' => $user['status'] ?? null,
            'is_platform_user' => (bool) $user['is_platform_user'],
            'permissions' => self::permissions((int) $user['id']),
            'roles' => self::roles((int) $user['id']),
        ];
    }

    /** @return list<string> */
    public static function permissions(int $userId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT DISTINCT p.code
             FROM permissions p
             INNER JOIN role_permissions rp ON rp.permission_id = p.id
             INNER JOIN user_roles ur ON ur.role_id = rp.role_id
             WHERE ur.user_id = ?'
        );
        $stmt->execute([$userId]);
        return array_map(static fn ($r) => $r['code'], $stmt->fetchAll());
    }

    /** @return list<string> */
    public static function roles(int $userId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT r.code FROM roles r INNER JOIN user_roles ur ON ur.role_id = r.id WHERE ur.user_id = ?'
        );
        $stmt->execute([$userId]);
        return array_map(static fn ($r) => $r['code'], $stmt->fetchAll());
    }

    public static function can(string $permission): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }
        $perms = self::permissions((int) $user['id']);
        return in_array($permission, $perms, true) || in_array('*', $perms, true);
    }

    public static function requirePermission(string $permission): void
    {
        if (!self::can($permission)) {
            Response::error('ACCESS_DENIED', "Missing permission: {$permission}", 403);
            exit;
        }
    }

    public static function requireTenant(): int
    {
        $tenantId = self::tenantId();
        if ($tenantId === null) {
            Response::error('ACCESS_DENIED', 'Tenant context required', 403);
            exit;
        }
        return $tenantId;
    }

    private static function recordFailedLogin(int $userId): void
    {
        $pdo = Database::connection();
        $pdo->prepare('UPDATE users SET failed_login_count = failed_login_count + 1, updated_at = NOW() WHERE id = ?')
            ->execute([$userId]);
        $stmt = $pdo->prepare('SELECT failed_login_count FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $count = (int) $stmt->fetchColumn();
        if ($count >= 5) {
            $pdo->prepare('UPDATE users SET locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?')
                ->execute([$userId]);
        }
    }
}
