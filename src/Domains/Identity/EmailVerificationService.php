<?php

declare(strict_types=1);

namespace Fmos\Domains\Identity;

use Fmos\Core\Audit;
use Fmos\Core\Database;
use Fmos\Core\Env;
use Fmos\Core\Logger;
use Fmos\Core\Mailer;

final class EmailVerificationService
{
    public function __construct(private readonly Mailer $mailer = new Mailer())
    {
    }

    /**
     * @return array{
     *   token:string,
     *   expires_at:string,
     *   email_sent:bool,
     *   email_error?:string,
     *   email_error_type?:string
     * }
     */
    public function issueAndSend(int $userId, string $email, string $name): array
    {
        Logger::correlate(['user_id' => $userId]);
        $domain = $this->emailDomain($email);

        Logger::event('VERIFICATION_TOKEN_GENERATION_STARTED', Logger::LEVEL_INFO, [
            'user_id' => $userId,
            'email_domain' => $domain,
        ]);

        $pdo = Database::connection();
        $pdo->prepare('UPDATE email_verification_tokens SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')
            ->execute([$userId]);

        $tokenStarted = hrtime(true);
        $raw = bin2hex(random_bytes(32));
        $hash = hash('sha256', $raw);
        $hours = (int) (Env::get('EMAIL_VERIFY_TTL_HOURS', '24') ?? '24');
        $tokenMs = (int) ((hrtime(true) - $tokenStarted) / 1_000_000);

        Logger::event('VERIFICATION_TOKEN_GENERATED', Logger::LEVEL_INFO, array_merge(
            Logger::tokenMeta($raw),
            [
                'user_id' => $userId,
                'token_generation_duration_ms' => $tokenMs,
                'ttl_hours' => $hours,
            ]
        ));

        Logger::event('VERIFICATION_TOKEN_STORAGE_STARTED', Logger::LEVEL_DEBUG, [
            'user_id' => $userId,
            'token_hash_prefix' => substr($hash, 0, 8),
        ]);

        try {
            $storeStarted = hrtime(true);
            $pdo->prepare(
                'INSERT INTO email_verification_tokens (user_id, token_hash, expires_at, created_at)
                 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? HOUR), NOW())'
            )->execute([$userId, $hash, $hours]);
            $expiresAt = date('c', time() + $hours * 3600);
            Logger::event('VERIFICATION_TOKEN_STORED', Logger::LEVEL_INFO, [
                'user_id' => $userId,
                'token_hash_prefix' => substr($hash, 0, 8),
                'expires_at' => $expiresAt,
                'database_insert_duration_ms' => (int) ((hrtime(true) - $storeStarted) / 1_000_000),
            ]);
        } catch (\Throwable $e) {
            Logger::event('VERIFICATION_TOKEN_STORAGE_FAILED', Logger::LEVEL_ERROR, [
                'user_id' => $userId,
                'exception_class' => $e::class,
                'message' => Logger::sanitizeMessage($e->getMessage()),
            ]);
            throw $e;
        }

        $appUrl = rtrim((string) (Env::get('APP_URL', 'http://localhost:8080') ?? 'http://localhost:8080'), '/');
        $verifyUrl = $appUrl . '/#verify-email?token=' . urlencode($raw);
        $urlHost = parse_url($appUrl, PHP_URL_HOST);
        Logger::event('VERIFICATION_URL_GENERATED', Logger::LEVEL_INFO, [
            'user_id' => $userId,
            'app_url_host' => is_string($urlHost) ? $urlHost : '',
            'verify_path' => '/#verify-email',
            'token_hash_prefix' => substr($hash, 0, 8),
        ]);

        Logger::event('VERIFICATION_EMAIL_STARTED', Logger::LEVEL_INFO, [
            'user_id' => $userId,
            'email_domain' => $domain,
            'email_type' => 'registration_verification',
        ], Logger::CHANNEL_EMAIL);

        $mail = $this->mailer->sendTemplate('verify_email', [$email], 'Verify your FMOS email', [
            'name' => $name,
            'verifyUrl' => $verifyUrl,
        ], [
            'channel' => 'accounts',
            'email_type' => 'registration_verification',
        ]);

        if (!empty($mail['ok'])) {
            Audit::record('EMAIL_VERIFICATION_SENT', 'user', $userId, null, ['email_domain' => $domain]);
            return [
                'token' => $raw,
                'expires_at' => $expiresAt,
                'email_sent' => true,
            ];
        }

        Logger::event('VERIFICATION_EMAIL_FAILED', Logger::LEVEL_ERROR, [
            'user_id' => $userId,
            'email_domain' => $domain,
            'error_type' => $mail['error_type'] ?? 'EMAIL_SEND_FAILED',
            'message' => Logger::sanitizeMessage((string) ($mail['error'] ?? 'send failed')),
            'mail_driver' => $mail['driver'] ?? null,
        ], Logger::CHANNEL_EMAIL);

        return [
            'token' => $raw,
            'expires_at' => $expiresAt,
            'email_sent' => false,
            'email_error' => (string) ($mail['error'] ?? 'Unable to send verification email.'),
            'email_error_type' => (string) ($mail['error_type'] ?? 'EMAIL_SEND_FAILED'),
        ];
    }

    /** @return array{ok:bool,code?:string,message?:string} */
    public function verify(string $rawToken): array
    {
        Logger::event('VERIFICATION_REQUEST_RECEIVED', Logger::LEVEL_INFO, [
            'token_length' => strlen($rawToken),
            'token_hash_prefix' => substr(hash('sha256', $rawToken), 0, 8),
        ]);

        if ($rawToken === '') {
            Logger::event('VERIFICATION_TOKEN_INVALID', Logger::LEVEL_WARNING, [
                'reason' => 'empty',
            ]);
            return ['ok' => false, 'code' => 'INVALID_TOKEN', 'message' => 'Verification link is invalid.'];
        }

        $hash = hash('sha256', $rawToken);
        $pdo = Database::connection();
        Logger::event('VERIFICATION_TOKEN_LOOKUP', Logger::LEVEL_DEBUG, [
            'token_hash_prefix' => substr($hash, 0, 8),
        ]);

        $stmt = $pdo->prepare(
            'SELECT * FROM email_verification_tokens WHERE token_hash = ? LIMIT 1'
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch();
        if (!$row) {
            Logger::event('VERIFICATION_TOKEN_INVALID', Logger::LEVEL_WARNING, [
                'token_hash_prefix' => substr($hash, 0, 8),
                'reason' => 'not_found',
            ]);
            return ['ok' => false, 'code' => 'INVALID_TOKEN', 'message' => 'Verification link is invalid.'];
        }

        $userId = (int) $row['user_id'];
        Logger::correlate(['user_id' => $userId]);

        if (!empty($row['used_at'])) {
            Logger::event('VERIFICATION_TOKEN_INVALID', Logger::LEVEL_WARNING, [
                'user_id' => $userId,
                'token_hash_prefix' => substr($hash, 0, 8),
                'reason' => 'already_used',
            ]);
            return ['ok' => false, 'code' => 'TOKEN_USED', 'message' => 'Verification link has already been used.'];
        }
        if (strtotime((string) $row['expires_at']) < time()) {
            Logger::event('VERIFICATION_TOKEN_EXPIRED', Logger::LEVEL_WARNING, [
                'user_id' => $userId,
                'token_hash_prefix' => substr($hash, 0, 8),
                'expires_at' => $row['expires_at'],
            ]);
            return ['ok' => false, 'code' => 'TOKEN_EXPIRED', 'message' => 'Verification link has expired.'];
        }

        Logger::event('VERIFICATION_TOKEN_VALID', Logger::LEVEL_INFO, [
            'user_id' => $userId,
            'token_hash_prefix' => substr($hash, 0, 8),
        ]);
        Logger::event('USER_VERIFICATION_STARTED', Logger::LEVEL_INFO, ['user_id' => $userId]);

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
            Logger::event('USER_VERIFICATION_FAILED', Logger::LEVEL_ERROR, [
                'user_id' => $userId,
                'exception_class' => $e::class,
                'message' => Logger::sanitizeMessage($e->getMessage()),
            ]);
            throw $e;
        }

        Logger::event('USER_VERIFIED', Logger::LEVEL_INFO, ['user_id' => $userId]);

        $user = $pdo->prepare('SELECT email, name, display_name FROM users WHERE id = ?');
        $user->execute([$userId]);
        $u = $user->fetch() ?: [];
        $welcomeEmail = (string) ($u['email'] ?? '');
        if ($welcomeEmail !== '') {
            $welcome = $this->mailer->sendTemplate('welcome', [$welcomeEmail], 'Welcome to FMOS', [
                'name' => (string) ($u['display_name'] ?? $u['name'] ?? ''),
            ], [
                'channel' => 'noreply',
                'email_type' => 'welcome',
            ]);
            if (empty($welcome['ok'])) {
                Logger::warning('Welcome email failed after verification', [
                    'event' => 'WELCOME_EMAIL_FAILED',
                    'user_id' => $userId,
                    'email_domain' => $this->emailDomain($welcomeEmail),
                    'error_type' => $welcome['error_type'] ?? null,
                ]);
            }
        }
        Audit::record('EMAIL_VERIFIED', 'user', $userId);
        return ['ok' => true];
    }

    /** @return array{ok:bool,message:string,email_sent?:bool} */
    public function resend(string $email): array
    {
        $email = self::normalizeEmail($email);
        $generic = 'If an account exists for this email address, you will receive further instructions.';
        $domain = $this->emailDomain($email);

        Logger::event('VERIFICATION_RESEND_STARTED', Logger::LEVEL_INFO, [
            'email_domain' => $domain,
        ]);

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user) {
            Logger::event('VERIFICATION_RESEND_NO_USER', Logger::LEVEL_INFO, [
                'email_domain' => $domain,
            ]);
            return ['ok' => true, 'message' => $generic];
        }
        if (!empty($user['email_verified_at']) && ($user['status'] ?? '') === 'ACTIVE') {
            Logger::event('VERIFICATION_RESEND_ALREADY_VERIFIED', Logger::LEVEL_INFO, [
                'user_id' => (int) $user['id'],
                'email_domain' => $domain,
            ]);
            return ['ok' => true, 'message' => $generic];
        }

        $issued = $this->issueAndSend((int) $user['id'], (string) $user['email'], (string) ($user['display_name'] ?? $user['name'] ?? ''));
        Logger::event('VERIFICATION_RESEND_COMPLETED', Logger::LEVEL_INFO, [
            'user_id' => (int) $user['id'],
            'email_domain' => $domain,
            'email_sent' => !empty($issued['email_sent']),
            'email_error_type' => $issued['email_error_type'] ?? null,
        ]);

        if (empty($issued['email_sent'])) {
            return [
                'ok' => true,
                'message' => 'We could not send the verification email right now. Please try again shortly or contact support.',
                'email_sent' => false,
            ];
        }

        return ['ok' => true, 'message' => $generic, 'email_sent' => true];
    }

    public static function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    private function emailDomain(string $email): string
    {
        return Logger::emailDomain($email);
    }
}
