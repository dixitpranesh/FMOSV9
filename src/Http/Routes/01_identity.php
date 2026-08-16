<?php

declare(strict_types=1);

/** @var \Fmos\Core\Router $router */

use Fmos\Core\Audit;
use Fmos\Core\Auth;
use Fmos\Core\AuthException;
use Fmos\Core\Database;
use Fmos\Core\Request;
use Fmos\Core\Response;
use Fmos\Domains\Identity\EmailVerificationService;
use Fmos\Domains\Identity\PasswordResetService;
use Fmos\Domains\Identity\RateLimiter;
use Fmos\Domains\Identity\RegistrationService;
use Fmos\Domains\Tenant\TenantService;

$clientIp = static function (): string {
    return (string) ($_SERVER['REMOTE_ADDR'] ?? 'cli');
};

$router->post('/api/v1/auth/login', static function (Request $request) use ($clientIp) {
    RateLimiter::allowOrFail('login', $clientIp(), 20, 300, 'Too many login attempts. Please try again later.');
    $email = trim((string) $request->input('email', ''));
    $password = (string) $request->input('password', '');
    try {
        $user = Auth::attempt($email, $password);
    } catch (AuthException $e) {
        Audit::record('LOGIN_FAILED', 'user', null, null, ['code' => $e->errorCode]);
        Response::error($e->errorCode, $e->getMessage(), $e->httpStatus, [
            'resend_verification' => $e->errorCode === 'EMAIL_NOT_VERIFIED',
        ]);
        return;
    }
    if (!$user) {
        Audit::record('LOGIN_FAILED', 'user', null, null, ['code' => 'INVALID_CREDENTIALS']);
        Response::error('AUTH_REQUIRED', 'Invalid credentials', 401);
        return;
    }
    Audit::record('LOGIN_SUCCESS', 'user', $user['id']);
    Response::json([
        'user' => $user,
        'token' => Auth::apiToken(),
        'csrf' => Auth::csrfToken(),
    ]);
}, false);

$router->post('/api/v1/auth/logout', static function () {
    Audit::record('LOGOUT', 'user', Auth::id());
    Auth::logout();
    Response::json(['logged_out' => true]);
}, false);

$router->get('/api/v1/auth/me', static function () {
    $user = Auth::user();
    if (!$user) {
        Response::error('AUTH_REQUIRED', 'Authentication required', 401);
        return;
    }
    Response::json(Auth::publicUser($user));
});

$router->post('/api/v1/auth/register', static function (Request $request) use ($clientIp) {
    RateLimiter::allowOrFail('register', $clientIp(), 10, 3600);
    Auth::startSession();
    try {
        $result = (new RegistrationService())->register($request->body);
        $payload = [
            'message' => $result['message'],
            'csrf' => Auth::csrfToken(),
        ];
        if (!empty($result['debug_verify_token'])) {
            $payload['debug_verify_token'] = $result['debug_verify_token'];
        }
        Response::json($payload, 201);
    } catch (\InvalidArgumentException $e) {
        Response::error('VALIDATION_ERROR', $e->getMessage(), 422);
    } catch (\Throwable $e) {
        Response::error('REGISTRATION_FAILED', 'Unable to complete registration. Please check the information provided and try again.', 400);
    }
}, false);

$router->post('/api/v1/auth/verify-email', static function (Request $request) {
    $token = (string) ($request->input('token') ?? '');
    if ($token === '') {
        Response::error('VALIDATION_ERROR', 'Verification token is required.', 422);
        return;
    }
    $result = (new EmailVerificationService())->verify($token);
    if (!$result['ok']) {
        Response::error((string) ($result['code'] ?? 'INVALID_TOKEN'), (string) ($result['message'] ?? 'Verification failed.'), 400);
        return;
    }
    Response::json(['verified' => true, 'message' => 'Email verified. You can sign in.']);
}, false);

$router->post('/api/v1/auth/resend-verification', static function (Request $request) use ($clientIp) {
    RateLimiter::allowOrFail('resend_verification', $clientIp(), 5, 3600);
    $email = (string) $request->input('email', '');
    $result = (new EmailVerificationService())->resend($email);
    Response::json(['message' => $result['message']]);
}, false);

$router->post('/api/v1/auth/forgot-password', static function (Request $request) use ($clientIp) {
    RateLimiter::allowOrFail('forgot_password', $clientIp(), 5, 3600);
    $email = (string) $request->input('email', '');
    $message = (new PasswordResetService())->request($email);
    Response::json(['message' => $message]);
}, false);

$router->post('/api/v1/auth/reset-password', static function (Request $request) use ($clientIp) {
    RateLimiter::allowOrFail('reset_password', $clientIp(), 10, 3600);
    $token = (string) $request->input('token', '');
    $password = (string) $request->input('password', '');
    $confirm = (string) $request->input('password_confirm', $request->input('confirm_password', ''));
    if ($password !== $confirm) {
        Response::error('VALIDATION_ERROR', 'Password confirmation does not match.', 422);
        return;
    }
    $result = (new PasswordResetService())->reset($token, $password);
    if (!$result['ok']) {
        Response::error((string) ($result['code'] ?? 'RESET_FAILED'), (string) ($result['message'] ?? 'Unable to reset password.'), 400);
        return;
    }
    Response::json(['message' => 'Password updated. You can sign in with your new password.']);
}, false);

$router->post('/api/v1/tenants', static function (Request $request) use ($clientIp) {
    // Break-glass bootstrap only — requires BOOTSTRAP_SECRET (never public).
    $expected = \Fmos\Core\Env::get('BOOTSTRAP_SECRET');
    $provided = $request->header('X-Bootstrap-Secret') ?? $request->input('bootstrap_secret');
    if (!is_string($expected) || $expected === '' || !is_string($provided) || !hash_equals($expected, $provided)) {
        Response::error('ACCESS_DENIED', 'Bootstrap endpoint is disabled.', 403);
        return;
    }
    RateLimiter::allowOrFail('bootstrap_tenants', $clientIp(), 5, 3600, 'Too many bootstrap attempts.');
    $service = new TenantService();
    $result = $service->createTenant(
        (string) $request->input('code'),
        (string) $request->input('name'),
        (string) $request->input('owner_email'),
        (string) $request->input('owner_name'),
        (string) $request->input('password'),
    );
    Audit::record('TENANT_BOOTSTRAP', 'tenant', (int) ($result['tenant_id'] ?? 0));
    Response::json($result, 201);
}, false);

$router->get('/api/v1/organizations', static function () {
    Auth::requirePermission('organization.view');
    $tenantId = Auth::requireTenant();
    $service = new TenantService();
    Response::json($service->listOrganizations($tenantId));
});

$router->post('/api/v1/organizations', static function (Request $request) {
    Auth::requirePermission('organization.create');
    $tenantId = Auth::requireTenant();
    $service = new TenantService();
    $org = $service->createOrganization(
        $tenantId,
        (string) $request->input('code'),
        (string) $request->input('name'),
    );
    Response::json($org, 201);
});

$router->get('/api/v1/roles', static function () {
    Auth::requirePermission('role.view');
    $pdo = Database::connection();
    $rows = $pdo->query('SELECT id, code, name, is_system FROM roles WHERE tenant_id IS NULL ORDER BY id')->fetchAll();
    Response::json($rows);
});

$router->get('/api/v1/permissions', static function () {
    Auth::requirePermission('role.view');
    $pdo = Database::connection();
    Response::json($pdo->query('SELECT id, code, module, description FROM permissions ORDER BY module, code')->fetchAll());
});

$router->get('/api/v1/users', static function () {
    Auth::requirePermission('user.view');
    $tenantId = Auth::requireTenant();
    $pdo = Database::connection();
    $stmt = $pdo->prepare('SELECT id, email, name, status, registration_type, email_verified_at, created_at FROM users WHERE tenant_id = ? AND deleted_at IS NULL');
    $stmt->execute([$tenantId]);
    Response::json($stmt->fetchAll());
});

