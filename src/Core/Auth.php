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
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'use_strict_mode' => true,
        ]);
    }

    public static function attempt(string $email, string $password): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        self::login($user);
        return self::publicUser($user);
    }

    public static function login(array $user): void
    {
        self::startSession();
        session_regenerate_id(true);
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
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
        self::$user = null;
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
                if ($user) {
                    self::$user = $user;
                    return self::$user;
                }
            }
            return null;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([(int) $userId]);
        $user = $stmt->fetch();
        self::$user = $user ?: null;
        return self::$user;
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
}
