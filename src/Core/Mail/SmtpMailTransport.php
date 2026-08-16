<?php

declare(strict_types=1);

namespace Fmos\Core\Mail;

use Fmos\Core\Env;

final class SmtpMailTransport implements MailTransport
{
    public function send(array $to, string $subject, string $htmlBody, string $textBody = '', array $meta = []): array
    {
        $host = Env::get('MAIL_HOST');
        $port = (int) (Env::get('MAIL_PORT', '587') ?? '587');
        $user = Env::get('MAIL_USERNAME', '') ?? '';
        $pass = Env::get('MAIL_PASSWORD', '') ?? '';
        $defaults = MailAddresses::forChannel(MailAddresses::CHANNEL_ACCOUNTS);
        $from = (string) ($meta['from'] ?? $defaults['from']);
        $fromName = (string) ($meta['from_name'] ?? $defaults['from_name']);
        $replyTo = $meta['reply_to'] ?? $defaults['reply_to'];
        $encryption = strtolower((string) (Env::get('MAIL_ENCRYPTION', 'tls') ?? 'tls'));

        if (!$host || $from === '') {
            return ['ok' => false, 'error' => 'SMTP is not configured.'];
        }

        $remote = ($encryption === 'ssl' ? 'ssl://' : '') . $host;
        $fp = @fsockopen($remote, $port, $errno, $errstr, 20);
        if (!$fp) {
            // Fallback: still deliver via log so local/dev never hard-fails registration.
            $log = new LogMailTransport();
            $res = $log->send($to, $subject, $htmlBody, $textBody, $meta);
            $res['error'] = "SMTP connect failed ({$errno}): {$errstr}; logged instead.";
            return $res;
        }
        stream_set_timeout($fp, 20);
        $this->expect($fp, 220);
        $this->cmd($fp, 'EHLO localhost', 250);
        if ($encryption === 'tls') {
            $this->cmd($fp, 'STARTTLS', 220);
            stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->cmd($fp, 'EHLO localhost', 250);
        }
        if ($user !== '') {
            $this->cmd($fp, 'AUTH LOGIN', 334);
            $this->cmd($fp, base64_encode($user), 334);
            $this->cmd($fp, base64_encode($pass), 235);
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
        return ['ok' => true];
    }

    /** @param resource $fp */
    private function cmd($fp, string $line, int $expect): void
    {
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
