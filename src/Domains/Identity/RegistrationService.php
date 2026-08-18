<?php

declare(strict_types=1);

namespace Fmos\Domains\Identity;

use Fmos\Core\Audit;
use Fmos\Core\Database;
use Fmos\Core\Logger;
use Fmos\Domains\Tenant\TenantService;

final class RegistrationService
{
    public const TYPE_INDEPENDENT = 'INDEPENDENT_DESIGNER';
    public const TYPE_FACTORY = 'FACTORY_OWNER';
    public const TYPE_FIRM = 'DESIGN_FIRM';

    public function __construct(
        private readonly EmailVerificationService $verification = new EmailVerificationService(),
    ) {
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{
     *   ok:bool,
     *   message:string,
     *   user_id?:int,
     *   organization_id?:int,
     *   email_sent?:bool,
     *   debug_verify_token?:string
     * }
     */
    public function register(array $payload): array
    {
        $started = hrtime(true);
        $registrationId = 'reg_' . date('Ymd_His') . '_' . strtoupper(bin2hex(random_bytes(3)));
        Logger::correlate([
            'registration_id' => $registrationId,
        ]);

        $generic = 'If registration can proceed, check your email for verification instructions.';
        $type = strtoupper(trim((string) ($payload['registration_type'] ?? '')));
        $emailRaw = (string) ($payload['email'] ?? '');
        $emailDomain = Logger::emailDomain($emailRaw);

        Logger::event('REGISTRATION_STARTED', Logger::LEVEL_INFO, [
            'registration_id' => $registrationId,
            'registration_type' => $type !== '' ? $type : null,
            'email_domain' => $emailDomain,
        ]);

        try {
            if (!in_array($type, [self::TYPE_INDEPENDENT, self::TYPE_FACTORY, self::TYPE_FIRM], true)) {
                throw new \InvalidArgumentException('Select a valid registration type.');
            }

            $email = EmailVerificationService::normalizeEmail($emailRaw);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException('Enter a valid email address.');
            }

            $password = (string) ($payload['password'] ?? '');
            $confirm = (string) ($payload['password_confirm'] ?? $payload['confirm_password'] ?? '');
            if ($password !== $confirm) {
                throw new \InvalidArgumentException('Password confirmation does not match.');
            }
            $policy = PasswordPolicy::validate($password);
            if (!$policy['ok']) {
                throw new \InvalidArgumentException((string) $policy['message']);
            }

            if (empty($payload['terms_accepted']) || empty($payload['privacy_acknowledged'])) {
                throw new \InvalidArgumentException('You must accept the Terms of Service and acknowledge the Privacy Notice.');
            }

            $first = trim((string) ($payload['first_name'] ?? ''));
            $last = trim((string) ($payload['last_name'] ?? ''));
            if ($first === '' || $last === '') {
                throw new \InvalidArgumentException('First name and last name are required.');
            }
            $display = trim((string) ($payload['display_name'] ?? ($first . ' ' . $last)));
            $mobileCc = trim((string) ($payload['mobile_country_code'] ?? '+91'));
            $mobile = trim((string) ($payload['mobile'] ?? ''));
            if ($mobile !== '') {
                $mv = IndiaBusinessValidators::validateMobile($mobile, $mobileCc);
                if (!$mv['ok']) {
                    throw new \InvalidArgumentException((string) $mv['message']);
                }
            } elseif ($type !== self::TYPE_INDEPENDENT) {
                throw new \InvalidArgumentException('Mobile number is required.');
            }

            $designation = trim((string) ($payload['designation'] ?? ''));
            $orgPayload = is_array($payload['organization'] ?? null) ? $payload['organization'] : [];
            $addressPayload = is_array($payload['address'] ?? null) ? $payload['address'] : [];
            if ($type !== self::TYPE_INDEPENDENT) {
                $this->assertOrgRequired($orgPayload, $addressPayload, $type);
            }

            Logger::event('REGISTRATION_VALIDATION_SUCCESS', Logger::LEVEL_INFO, [
                'registration_id' => $registrationId,
                'registration_type' => $type,
                'email_domain' => Logger::emailDomain($email),
            ]);
        } catch (\InvalidArgumentException $e) {
            Logger::event('REGISTRATION_VALIDATION_FAILED', Logger::LEVEL_WARNING, [
                'registration_id' => $registrationId,
                'registration_type' => $type !== '' ? $type : null,
                'email_domain' => $emailDomain,
                'message' => Logger::sanitizeMessage($e->getMessage()),
                'duration_ms' => (int) ((hrtime(true) - $started) / 1_000_000),
            ]);
            throw $e;
        }

        $pdo = Database::connection();
        $exists = $pdo->prepare('SELECT id FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1');
        $exists->execute([$email]);
        if ($exists->fetch()) {
            // Anti-enumeration: pretend success; optionally resend if pending.
            $u = $pdo->prepare('SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1');
            $u->execute([$email]);
            $row = $u->fetch();
            $emailSent = null;
            if ($row && empty($row['email_verified_at'])) {
                Logger::correlate(['user_id' => (int) $row['id']]);
                $issued = $this->verification->issueAndSend((int) $row['id'], $email, (string) ($row['display_name'] ?? $row['name'] ?? ''));
                $emailSent = !empty($issued['email_sent']);
            }
            Logger::event('REGISTRATION_DUPLICATE_EMAIL', Logger::LEVEL_INFO, [
                'registration_id' => $registrationId,
                'email_domain' => Logger::emailDomain($email),
                'resend_attempted' => $emailSent !== null,
                'email_sent' => $emailSent,
                'duration_ms' => (int) ((hrtime(true) - $started) / 1_000_000),
            ]);
            return [
                'ok' => true,
                'message' => $generic,
                'email_sent' => $emailSent,
                'registration_id' => $registrationId,
            ];
        }

        $tenantCode = $this->uniqueTenantCode($type, $email);
        $tenantName = $type === self::TYPE_INDEPENDENT
            ? ($display !== '' ? $display : 'Personal Workspace')
            : (string) ($orgPayload['trade_name'] ?? $orgPayload['legal_name'] ?? $display);

        Logger::event('USER_CREATION_STARTED', Logger::LEVEL_INFO, [
            'registration_id' => $registrationId,
            'registration_type' => $type,
            'email_domain' => Logger::emailDomain($email),
        ]);

        $pdo->beginTransaction();
        try {
            $dbStarted = hrtime(true);
            $created = (new TenantService())->createTenantForRegistration([
                'tenant_code' => $tenantCode,
                'tenant_name' => $tenantName,
                'email' => $email,
                'password' => $password,
                'first_name' => $first,
                'last_name' => $last,
                'display_name' => $display,
                'name' => $display,
                'mobile_country_code' => $mobileCc !== '' ? $mobileCc : null,
                'mobile' => $mobile !== '' ? $mobile : null,
                'designation' => $designation !== '' ? $designation : null,
                'registration_type' => $type,
                'terms_version' => (string) ($payload['terms_version'] ?? '1.0'),
                'privacy_version' => (string) ($payload['privacy_version'] ?? '1.0'),
                'marketing_email_consent' => !empty($payload['marketing_email_consent']),
                'organization' => $orgPayload,
                'address' => $addressPayload,
                'status' => 'PENDING_EMAIL_VERIFICATION',
            ]);
            $pdo->commit();
            Logger::event('REGISTRATION_TRANSACTION_COMMITTED', Logger::LEVEL_INFO, [
                'registration_id' => $registrationId,
                'user_id' => (int) $created['user_id'],
                'organization_id' => (int) ($created['organization_id'] ?? 0),
                'database_insert_duration_ms' => (int) ((hrtime(true) - $dbStarted) / 1_000_000),
            ]);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::event('REGISTRATION_TRANSACTION_ROLLED_BACK', Logger::LEVEL_ERROR, [
                'registration_id' => $registrationId,
                'exception_class' => $e::class,
                'message' => Logger::sanitizeMessage($e->getMessage()),
            ]);
            Logger::event('USER_CREATION_FAILED', Logger::LEVEL_ERROR, [
                'registration_id' => $registrationId,
                'email_domain' => Logger::emailDomain($email),
                'exception_class' => $e::class,
                'message' => Logger::sanitizeMessage($e->getMessage()),
            ]);
            throw $e;
        }

        $userId = (int) $created['user_id'];
        Logger::correlate(['user_id' => $userId]);
        Logger::event('USER_CREATED', Logger::LEVEL_INFO, [
            'registration_id' => $registrationId,
            'user_id' => $userId,
            'organization_id' => (int) ($created['organization_id'] ?? 0),
            'registration_type' => $type,
            'email_domain' => Logger::emailDomain($email),
        ]);

        $issued = $this->verification->issueAndSend($userId, $email, $display);
        $emailSent = !empty($issued['email_sent']);

        Audit::record('USER_REGISTERED', 'user', $userId, null, [
            'registration_type' => $type,
            'organization_id' => $created['organization_id'] ?? null,
            'registration_id' => $registrationId,
            'email_sent' => $emailSent,
        ]);
        Audit::record('ORGANIZATION_CREATED', 'organization', (int) ($created['organization_id'] ?? 0), null, [
            'registration_type' => $type,
        ]);

        $message = $generic;
        if (!$emailSent) {
            $message = 'Your account was created, but we could not send the verification email. Please use "Resend verification email" or contact support.';
        }

        Logger::event('REGISTRATION_COMPLETED', Logger::LEVEL_INFO, [
            'registration_id' => $registrationId,
            'user_id' => $userId,
            'email_sent' => $emailSent,
            'email_error_type' => $issued['email_error_type'] ?? null,
            'registration_duration_ms' => (int) ((hrtime(true) - $started) / 1_000_000),
        ]);

        $out = [
            'ok' => true,
            'message' => $message,
            'user_id' => $userId,
            'organization_id' => (int) ($created['organization_id'] ?? 0),
            'email_sent' => $emailSent,
            'registration_id' => $registrationId,
        ];
        // Expose raw token only for explicit local test harnesses.
        $expose = strtolower((string) (\Fmos\Core\Env::get('MAIL_EXPOSE_TOKENS', 'false') ?? 'false')) === 'true'
            && strtolower((string) (\Fmos\Core\Env::get('APP_ENV', 'local') ?? 'local')) === 'local'
            && \Fmos\Core\Env::bool('APP_DEBUG', false);
        if ($expose) {
            $out['debug_verify_token'] = $issued['token'];
        }
        return $out;
    }

    /** @param array<string,mixed> $org @param array<string,mixed> $address */
    private function assertOrgRequired(array $org, array $address, string $type): void
    {
        $legal = trim((string) ($org['legal_name'] ?? ''));
        if ($legal === '') {
            throw new \InvalidArgumentException('Legal business name is required.');
        }
        $constitution = strtoupper(trim((string) ($org['constitution'] ?? '')));
        if ($constitution === '' || !in_array($constitution, IndiaBusinessValidators::constitutions(), true)) {
            throw new \InvalidArgumentException('Select a valid business constitution.');
        }
        $gstReg = strtoupper(trim((string) ($org['gst_registered'] ?? 'NO')));
        if (!in_array($gstReg, ['YES', 'NO', 'NOT_APPLICABLE', 'NA'], true)) {
            throw new \InvalidArgumentException('Select GST registration status.');
        }
        if ($gstReg === 'NA') {
            $gstReg = 'NOT_APPLICABLE';
        }
        $org['gst_registered'] = $gstReg;

        $pan = trim((string) ($org['pan'] ?? ''));
        if ($pan !== '') {
            $pv = IndiaBusinessValidators::validatePan($pan);
            if (!$pv['ok']) {
                throw new \InvalidArgumentException((string) $pv['message']);
            }
        }

        if ($gstReg === 'YES') {
            $gstin = trim((string) ($org['gstin'] ?? ''));
            $gv = IndiaBusinessValidators::validateGstin($gstin);
            if (!$gv['ok']) {
                throw new \InvalidArgumentException((string) $gv['message']);
            }
            if ($pan === '') {
                // Derive PAN check from GSTIN portion; still require explicit PAN optionally.
            }
        }

        $line1 = trim((string) ($address['line1'] ?? ''));
        $state = trim((string) ($address['state'] ?? ''));
        $pin = trim((string) ($address['pin_code'] ?? $address['pincode'] ?? ''));
        if ($line1 === '' || $state === '' || $pin === '') {
            throw new \InvalidArgumentException('Principal place of business address, state, and PIN are required.');
        }
        $pinOk = IndiaBusinessValidators::validatePin($pin);
        if (!$pinOk['ok']) {
            throw new \InvalidArgumentException((string) $pinOk['message']);
        }
        if ($type === self::TYPE_FACTORY || $type === self::TYPE_FIRM) {
            // business email optional but if present must be valid
            $be = trim((string) ($org['business_email'] ?? ''));
            if ($be !== '' && !filter_var($be, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException('Enter a valid business email.');
            }
        }
    }

    private function uniqueTenantCode(string $type, string $email): string
    {
        $prefix = match ($type) {
            self::TYPE_FACTORY => 'FAC',
            self::TYPE_FIRM => 'IDF',
            default => 'IND',
        };
        $base = $prefix . strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', explode('@', $email)[0] ?? 'user') ?: 'user', 0, 8));
        $pdo = Database::connection();
        for ($i = 0; $i < 20; $i++) {
            $code = $base . ($i === 0 ? '' : (string) $i) . substr(bin2hex(random_bytes(2)), 0, 4);
            $code = substr($code, 0, 64);
            $stmt = $pdo->prepare('SELECT id FROM tenants WHERE code = ? LIMIT 1');
            $stmt->execute([$code]);
            if (!$stmt->fetch()) {
                return $code;
            }
        }
        return $prefix . bin2hex(random_bytes(8));
    }
}
