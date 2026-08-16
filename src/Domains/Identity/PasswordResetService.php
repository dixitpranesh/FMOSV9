<?php

declare(strict_types=1);

namespace Fmos\Domains\Identity;

use Fmos\Core\Audit;
use Fmos\Core\Auth;
use Fmos\Core\Database;
use Fmos\Core\Env;
use Fmos\Core\Mailer;

final class PasswordResetService
{
    public function __construct(private readonly Mailer $mailer = new Mailer())
    {
    }

    /** Always returns generic message. */
    public function request(string $email): string
    {
        $generic = 'If an account exists for this email address, password reset instructions have been sent.';
        $email = EmailVerificationService::normalizeEmail($email);
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        Audit::record('PASSWORD_RESET_REQUESTED', 'user', $user ? (int) $user['id'] : null, null, [
            'email_domain' => explode('@', $email)[1] ?? '',
            'found' => (bool) $user,
        ]);
        if (!$user) {
            return $generic;
        }

        $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')
            ->execute([(int) $user['id']]);

        $raw = bin2hex(random_bytes(32));
        $hash = hash('sha256', $raw);
        $minutes = (int) (Env::get('PASSWORD_RESET_TTL_MINUTES', '60') ?? '60');
        $pdo->prepare(
            'INSERT INTO password_resets (user_id, token_hash, purpose, expires_at, created_at)
             VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), NOW())'
        )->execute([(int) $user['id'], $hash, 'PASSWORD_RESET', $minutes]);

        $appUrl = rtrim((string) (Env::get('APP_URL', 'http://localhost:8080') ?? 'http://localhost:8080'), '/');
        $resetUrl = $appUrl . '/#reset-password?token=' . urlencode($raw);
        $this->mailer->sendTemplate('password_reset', [(string) $user['email']], 'Reset your FMOS password', [
            'name' => (string) ($user['display_name'] ?? $user['name'] ?? ''),
            'resetUrl' => $resetUrl,
        ], ['channel' => 'accounts']);
        return $generic;
    }

    /** @return array{ok:bool,code?:string,message?:string} */
    public function reset(string $rawToken, string $newPassword): array
    {
        $policy = PasswordPolicy::validate($newPassword);
        if (!$policy['ok']) {
            return ['ok' => false, 'code' => 'WEAK_PASSWORD', 'message' => (string) $policy['message']];
        }

        $hash = hash('sha256', $rawToken);
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM password_resets WHERE token_hash = ? LIMIT 1');
        $stmt->execute([$hash]);
        $row = $stmt->fetch();
        if (!$row) {
            return ['ok' => false, 'code' => 'INVALID_TOKEN', 'message' => 'Reset link is invalid.'];
        }
        if (!empty($row['used_at'])) {
            return ['ok' => false, 'code' => 'TOKEN_USED', 'message' => 'Reset link has already been used.'];
        }
        if (strtotime((string) $row['expires_at']) < time()) {
            return ['ok' => false, 'code' => 'TOKEN_EXPIRED', 'message' => 'Reset link has expired.'];
        }

        $userId = (int) $row['user_id'];
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')->execute([(int) $row['id']]);
            $pdo->prepare('UPDATE users SET password_hash = ?, failed_login_count = 0, locked_until = NULL, updated_at = NOW() WHERE id = ?')
                ->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
            Auth::revokeAllSessions($userId);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $userStmt = $pdo->prepare('SELECT email, name, display_name FROM users WHERE id = ?');
        $userStmt->execute([$userId]);
        $u = $userStmt->fetch() ?: [];
        if (!empty($u['email'])) {
            $this->mailer->sendTemplate('password_changed', [(string) $u['email']], 'Your FMOS password was changed', [
                'name' => (string) ($u['display_name'] ?? $u['name'] ?? ''),
            ], ['channel' => 'noreply']);
        }
        Audit::record('PASSWORD_RESET_COMPLETED', 'user', $userId);
        return ['ok' => true];
    }
}
