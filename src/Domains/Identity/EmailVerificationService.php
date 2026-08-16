<?php

declare(strict_types=1);

namespace Fmos\Domains\Identity;

use Fmos\Core\Audit;
use Fmos\Core\Database;
use Fmos\Core\Env;
use Fmos\Core\Mailer;

final class EmailVerificationService
{
    public function __construct(private readonly Mailer $mailer = new Mailer())
    {
    }

    /** @return array{token:string,expires_at:string} */
    public function issueAndSend(int $userId, string $email, string $name): array
    {
        $pdo = Database::connection();
        $pdo->prepare('UPDATE email_verification_tokens SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')
            ->execute([$userId]);

        $raw = bin2hex(random_bytes(32));
        $hash = hash('sha256', $raw);
        $hours = (int) (Env::get('EMAIL_VERIFY_TTL_HOURS', '24') ?? '24');
        $pdo->prepare(
            'INSERT INTO email_verification_tokens (user_id, token_hash, expires_at, created_at)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? HOUR), NOW())'
        )->execute([$userId, $hash, $hours]);

        $appUrl = rtrim((string) (Env::get('APP_URL', 'http://localhost:8080') ?? 'http://localhost:8080'), '/');
        $verifyUrl = $appUrl . '/#verify-email?token=' . urlencode($raw);
        $this->mailer->sendTemplate('verify_email', [$email], 'Verify your FMOS email', [
            'name' => $name,
            'verifyUrl' => $verifyUrl,
        ], ['channel' => 'accounts']);
        Audit::record('EMAIL_VERIFICATION_SENT', 'user', $userId, null, ['email_domain' => $this->emailDomain($email)]);
        return ['token' => $raw, 'expires_at' => date('c', time() + $hours * 3600)];
    }

    /** @return array{ok:bool,code?:string,message?:string} */
    public function verify(string $rawToken): array
    {
        $hash = hash('sha256', $rawToken);
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT * FROM email_verification_tokens WHERE token_hash = ? LIMIT 1'
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch();
        if (!$row) {
            return ['ok' => false, 'code' => 'INVALID_TOKEN', 'message' => 'Verification link is invalid.'];
        }
        if (!empty($row['used_at'])) {
            return ['ok' => false, 'code' => 'TOKEN_USED', 'message' => 'Verification link has already been used.'];
        }
        if (strtotime((string) $row['expires_at']) < time()) {
            return ['ok' => false, 'code' => 'TOKEN_EXPIRED', 'message' => 'Verification link has expired.'];
        }

        $userId = (int) $row['user_id'];
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE email_verification_tokens SET used_at = NOW() WHERE id = ?')->execute([(int) $row['id']]);
            $pdo->prepare(
                "UPDATE users SET email_verified_at = NOW(), status = 'ACTIVE', updated_at = NOW()
                 WHERE id = ? AND deleted_at IS NULL"
            )->execute([$userId]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $user = $pdo->prepare('SELECT email, name, display_name FROM users WHERE id = ?');
        $user->execute([$userId]);
        $u = $user->fetch() ?: [];
        $this->mailer->sendTemplate('welcome', [(string) ($u['email'] ?? '')], 'Welcome to FMOS', [
            'name' => (string) ($u['display_name'] ?? $u['name'] ?? ''),
        ], ['channel' => 'noreply']);
        Audit::record('EMAIL_VERIFIED', 'user', $userId);
        return ['ok' => true];
    }

    /** @return array{ok:bool,message:string} */
    public function resend(string $email): array
    {
        $email = self::normalizeEmail($email);
        $generic = 'If an account exists for this email address, you will receive further instructions.';
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user) {
            return ['ok' => true, 'message' => $generic];
        }
        if (!empty($user['email_verified_at']) && ($user['status'] ?? '') === 'ACTIVE') {
            return ['ok' => true, 'message' => $generic];
        }
        $this->issueAndSend((int) $user['id'], (string) $user['email'], (string) ($user['display_name'] ?? $user['name'] ?? ''));
        return ['ok' => true, 'message' => $generic];
    }

    public static function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    private function emailDomain(string $email): string
    {
        $parts = explode('@', $email);
        return $parts[1] ?? '';
    }
}
