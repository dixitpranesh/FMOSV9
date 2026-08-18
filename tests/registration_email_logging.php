<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Fmos\Core\Env;
use Fmos\Core\Logger;
use Fmos\Core\Mailer;
use Fmos\Core\Mail\MailTransport;

function ok(string $msg): void
{
    echo "  OK  $msg\n";
}

function assertTrue(bool $cond, string $msg): void
{
    if (!$cond) {
        throw new RuntimeException('ASSERT: ' . $msg);
    }
    ok($msg);
}

Env::load(dirname(__DIR__) . '/.env');

echo "Registration / email logging diagnostics tests\n";

Logger::setRequestId('req_test_logging_001');
Logger::clearCorrelation();

assertTrue(Logger::emailDomain('Ada.User@Gmail.com') === 'gmail.com', 'emailDomain extracts domain');
assertTrue(str_starts_with(Logger::maskEmail('pranesh@gmail.com'), 'pr****@'), 'maskEmail masks local part');
assertTrue(Logger::classifySmtpError('SMTP connect failed (111): Connection refused') === 'SMTP_CONNECTION_FAILED', 'classify connect');
assertTrue(Logger::classifySmtpError('SMTP unexpected response: 535 Authentication failed') === 'SMTP_AUTHENTICATION_FAILED', 'classify auth');
assertTrue(!str_contains(Logger::sanitizeMessage('password=Secret123! token=abc'), 'Secret123'), 'sanitize redacts secrets');

$meta = Logger::tokenMeta(str_repeat('a', 64), '2026-01-01T00:00:00+00:00');
assertTrue(($meta['token_length'] ?? 0) === 64, 'tokenMeta length');
assertTrue(strlen((string) ($meta['token_hash_prefix'] ?? '')) === 8, 'tokenMeta hash prefix');
assertTrue(!isset($meta['token']), 'tokenMeta has no raw token');

$cfg = Logger::mailConfigStatus();
assertTrue(isset($cfg['mail_driver'], $cfg['smtp_host_configured'], $cfg['smtp_password_configured']), 'mailConfigStatus keys');
assertTrue(!isset($cfg['smtp_password']) && !isset($cfg['password']), 'mailConfigStatus has no secrets');

Logger::event('REGISTRATION_STARTED', Logger::LEVEL_INFO, [
    'registration_id' => 'reg_test_001',
    'email_domain' => 'example.test',
]);
Logger::event('EMAIL_SEND_FAILED', Logger::LEVEL_ERROR, [
    'error_type' => 'SMTP_AUTHENTICATION_FAILED',
    'smtp_host' => 'smtp.example.test',
], Logger::CHANNEL_EMAIL);

$logDir = dirname(__DIR__) . '/storage/logs';
$date = date('Y-m-d');
assertTrue(is_file($logDir . '/app-' . $date . '.log') || is_file($logDir . '/app.log'), 'app log written');
assertTrue(is_file($logDir . '/email-' . $date . '.log'), 'email channel log written');
assertTrue(is_file($logDir . '/error-' . $date . '.log'), 'error channel log written');

$failing = new class implements MailTransport {
    public function send(array $to, string $subject, string $htmlBody, string $textBody = '', array $meta = []): array
    {
        return [
            'ok' => false,
            'error' => 'SMTP unexpected response: 535 Authentication failed',
            'error_type' => 'SMTP_AUTHENTICATION_FAILED',
        ];
    }
};

$mailer = new Mailer($failing);
$res = $mailer->sendTemplate('verify_email', ['user@example.test'], 'Verify', [
    'name' => 'Test',
    'verifyUrl' => 'https://app.fmos.in/#verify-email?token=not-a-real-token',
], ['email_type' => 'registration_verification']);
assertTrue($res['ok'] === false, 'failing transport returns ok=false');
assertTrue(($res['error_type'] ?? '') === 'SMTP_AUTHENTICATION_FAILED', 'error_type preserved');

$okTransport = new class implements MailTransport {
    public function send(array $to, string $subject, string $htmlBody, string $textBody = '', array $meta = []): array
    {
        return ['ok' => true, 'driver' => 'fake'];
    }
};
$okMailer = new Mailer($okTransport);
$okRes = $okMailer->sendTemplate('verify_email', ['user@example.test'], 'Verify', [
    'name' => 'Test',
    'verifyUrl' => 'https://example.test/#verify-email?token=abc',
], ['email_type' => 'registration_verification']);
assertTrue($okRes['ok'] === true, 'ok transport succeeds');

echo "All registration email logging tests passed\n";
