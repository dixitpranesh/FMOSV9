<?php

declare(strict_types=1);

namespace Fmos\Core\Mail;

use Fmos\Core\Env;
use Fmos\Core\Logger;

final class LogMailTransport implements MailTransport
{
    public function send(array $to, string $subject, string $htmlBody, string $textBody = '', array $meta = []): array
    {
        $dir = $this->dir();
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $file = $dir . '/mail-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.json';
        $payload = [
            'to' => $to,
            'from' => $meta['from'] ?? null,
            'from_name' => $meta['from_name'] ?? null,
            'reply_to' => $meta['reply_to'] ?? null,
            'subject' => $subject,
            'html' => $htmlBody,
            'text' => $textBody,
            'sent_at' => date('c'),
            'email_type' => $meta['email_type'] ?? null,
        ];
        file_put_contents($file, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        Logger::event('EMAIL_SENT_TO_LOG_DRIVER', Logger::LEVEL_INFO, [
            'email_type' => $meta['email_type'] ?? 'transactional',
            'mail_driver' => 'log',
            'path' => $file,
            'recipient_domains' => array_values(array_unique(array_map(
                static fn (string $e): string => Logger::emailDomain($e),
                $to
            ))),
            'from_domain' => Logger::fromDomain((string) ($meta['from'] ?? '')),
            'subject_length' => strlen($subject),
        ], Logger::CHANNEL_EMAIL);

        return ['ok' => true, 'path' => $file, 'driver' => 'log'];
    }

    private function dir(): string
    {
        $base = Env::get('STORAGE_PATH', 'storage') ?? 'storage';
        if (!str_starts_with($base, '/') && !preg_match('#^[A-Za-z]:\\\\#', $base)) {
            $base = dirname(__DIR__, 3) . '/' . $base;
        }
        return rtrim($base, '/\\') . '/mail';
    }
}
