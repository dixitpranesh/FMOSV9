<?php

declare(strict_types=1);

namespace Fmos\Core\Mail;

use Fmos\Core\Env;
use Fmos\Core\Logger;

final class SmtpMailTransport implements MailTransport
{
    public function send(array $to, string $subject, string $htmlBody, string $textBody = '', array $meta = []): array
    {
        $started = hrtime(true);
        $host = Env::get('MAIL_HOST');
        $port = (int) (Env::get('MAIL_PORT', '587') ?? '587');
        $user = Env::get('MAIL_USERNAME', '') ?? '';
        $pass = Env::get('MAIL_PASSWORD', '') ?? '';
        $defaults = MailAddresses::forChannel(MailAddresses::CHANNEL_ACCOUNTS);
        $from = (string) ($meta['from'] ?? $defaults['from']);
        $fromName = (string) ($meta['from_name'] ?? $defaults['from_name']);
        $replyTo = $meta['reply_to'] ?? $defaults['reply_to'];
        $encryption = strtolower((string) (Env::get('MAIL_ENCRYPTION', 'tls') ?? 'tls'));
        $emailType = (string) ($meta['email_type'] ?? 'transactional');

        $baseCtx = array_merge(Logger::mailConfigStatus(), [
            'email_type' => $emailType,
            'smtp_host' => is_string($host) ? $host : '',
            'smtp_port' => $port,
            'encryption' => $encryption,
            'from_domain' => Logger::fromDomain($from),
            'recipient_count' => count($to),
            'recipient_domains' => array_values(array_unique(array_map(
                static fn (string $e): string => Logger::emailDomain($e),
                $to
            ))),
        ]);

        if (!$host || $from === '') {
            Logger::event('SMTP_CONNECTION_FAILED', Logger::LEVEL_ERROR, array_merge($baseCtx, [
                'error_type' => 'SMTP_NOT_CONFIGURED',
                'message' => 'SMTP host or From address missing',
            ]), Logger::CHANNEL_EMAIL);
            return ['ok' => false, 'error' => 'SMTP is not configured.', 'error_type' => 'SMTP_NOT_CONFIGURED'];
        }

        Logger::event('SMTP_CONNECTION_STARTED', Logger::LEVEL_INFO, $baseCtx, Logger::CHANNEL_EMAIL);

        $remote = ($encryption === 'ssl' ? 'ssl://' : '') . $host;
        $connectStarted = hrtime(true);
        $fp = @fsockopen($remote, $port, $errno, $errstr, 20);
        $connectMs = (int) ((hrtime(true) - $connectStarted) / 1_000_000);

        if (!$fp) {
            $err = "SMTP connect failed ({$errno}): {$errstr}";
            Logger::event('SMTP_CONNECTION_FAILED', Logger::LEVEL_ERROR, array_merge($baseCtx, [
                'error_type' => 'SMTP_CONNECTION_FAILED',
                'smtp_errno' => $errno,
                'message' => Logger::sanitizeMessage($err),
                'smtp_connection_duration_ms' => $connectMs,
                'duration_ms' => (int) ((hrtime(true) - $started) / 1_000_000),
            ]), Logger::CHANNEL_EMAIL);

            // Dev-only soft fallback: never pretend SMTP succeeded in production.
            $env = strtolower((string) (Env::get('APP_ENV', 'local') ?? 'local'));
            $allowFallback = Env::bool('MAIL_FALLBACK_LOG', $env !== 'production');
            if ($allowFallback && $env !== 'production') {
                $log = new LogMailTransport();
                $res = $log->send($to, $subject, $htmlBody, $textBody, $meta);
                Logger::event('EMAIL_FALLBACK_TO_LOG', Logger::LEVEL_WARNING, array_merge($baseCtx, [
                    'path' => $res['path'] ?? null,
                    'message' => 'SMTP connect failed; wrote message to storage/mail instead (non-production only)',
                ]), Logger::CHANNEL_EMAIL);
                return [
                    'ok' => false,
                    'error' => $err . '; logged to storage instead (non-production fallback).',
                    'error_type' => 'SMTP_CONNECTION_FAILED',
                    'path' => $res['path'] ?? null,
                    'fallback' => 'log',
                ];
            }

            return [
                'ok' => false,
                'error' => $err,
                'error_type' => 'SMTP_CONNECTION_FAILED',
            ];
        }

        Logger::event('SMTP_CONNECTION_SUCCESS', Logger::LEVEL_INFO, array_merge($baseCtx, [
            'smtp_connection_duration_ms' => $connectMs,
        ]), Logger::CHANNEL_EMAIL);

        try {
            stream_set_timeout($fp, 20);
            $this->expect($fp, 220);
            $this->cmd($fp, 'EHLO localhost', 250);
            if ($encryption === 'tls') {
                $this->cmd($fp, 'STARTTLS', 220);
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('TLS negotiation failed');
                }
                $this->cmd($fp, 'EHLO localhost', 250);
            }
            if ($user !== '') {
                Logger::event('SMTP_AUTH_STARTED', Logger::LEVEL_INFO, $baseCtx, Logger::CHANNEL_EMAIL);
                $this->cmd($fp, 'AUTH LOGIN', 334);
                $this->cmd($fp, base64_encode($user), 334);
                $this->cmd($fp, base64_encode($pass), 235);
                Logger::event('SMTP_AUTH_SUCCESS', Logger::LEVEL_INFO, $baseCtx, Logger::CHANNEL_EMAIL);
            }
            $this->cmd($fp, 'MAIL FROM:<' . $from . '>', 250);
            foreach ($to as $addr) {
                $this->cmd($fp, 'RCPT TO:<' . $addr . '>', 250);
            }
            $this->cmd($fp, 'DATA', 354);
            $boundary = 'b_' . bin2hex(random_bytes(8));
            $headers = [
                'From: ' . $this->encodeAddress($fromName, $from),
                'To: ' . implode(', ', $to),
                'Subject: ' . $this->encodeHeader($subject),
                'MIME-Version: 1.0',
                'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            ];
            if (is_string($replyTo) && $replyTo !== '') {
                $headers[] = 'Reply-To: ' . $replyTo;
            }
            $body = implode("\r\n", $headers) . "\r\n\r\n";
            $body .= '--' . $boundary . "\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
            $body .= ($textBody !== '' ? $textBody : strip_tags($htmlBody)) . "\r\n";
            $body .= '--' . $boundary . "\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
            $body .= $htmlBody . "\r\n";
            $body .= '--' . $boundary . "--\r\n.";
            fwrite($fp, $body . "\r\n");
            $this->expect($fp, 250);
            $this->cmd($fp, 'QUIT', 221);
            fclose($fp);

            $durationMs = (int) ((hrtime(true) - $started) / 1_000_000);
            Logger::event('EMAIL_ACCEPTED_BY_SMTP', Logger::LEVEL_INFO, array_merge($baseCtx, [
                'email_send_duration_ms' => $durationMs,
            ]), Logger::CHANNEL_EMAIL);

            return ['ok' => true, 'duration_ms' => $durationMs];
        } catch (\Throwable $e) {
            if (is_resource($fp)) {
                fclose($fp);
            }
            $msg = Logger::sanitizeMessage($e->getMessage());
            $errorType = Logger::classifySmtpError($msg);
            if (str_contains(strtolower($msg), '535') || $errorType === 'SMTP_AUTHENTICATION_FAILED') {
                Logger::event('SMTP_AUTH_FAILED', Logger::LEVEL_ERROR, array_merge($baseCtx, [
                    'error_type' => $errorType,
                    'message' => $msg,
                    'exception_class' => $e::class,
                    'duration_ms' => (int) ((hrtime(true) - $started) / 1_000_000),
                ]), Logger::CHANNEL_EMAIL);
            }
            Logger::event('EMAIL_SEND_FAILED', Logger::LEVEL_ERROR, array_merge($baseCtx, [
                'error_type' => $errorType,
                'message' => $msg,
                'exception_class' => $e::class,
                'duration_ms' => (int) ((hrtime(true) - $started) / 1_000_000),
            ]), Logger::CHANNEL_EMAIL);

            return [
                'ok' => false,
                'error' => $msg,
                'error_type' => $errorType,
            ];
        }
    }

    /** @param resource $fp */
    private function cmd($fp, string $line, int $expect): void
    {
        // Never log AUTH LOGIN payload lines (base64 credentials).
        fwrite($fp, $line . "\r\n");
        $this->expect($fp, $expect);
    }

    /** @param resource $fp */
    private function expect($fp, int $code): void
    {
        $resp = '';
        while (($line = fgets($fp, 512)) !== false) {
            $resp .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        if (!str_starts_with($resp, (string) $code)) {
            throw new \RuntimeException('SMTP unexpected response: ' . trim($resp));
        }
    }

    private function encodeAddress(string $name, string $email): string
    {
        return sprintf('"%s" <%s>', addcslashes($name, '"'), $email);
    }

    private function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }
}
