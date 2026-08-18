<?php

declare(strict_types=1);

namespace Fmos\Core;

use Fmos\Core\Mail\LogMailTransport;
use Fmos\Core\Mail\MailAddresses;
use Fmos\Core\Mail\MailTransport;
use Fmos\Core\Mail\SmtpMailTransport;

final class Mailer
{
    private MailTransport $transport;
    private string $driver;

    public function __construct(?MailTransport $transport = null)
    {
        if ($transport !== null) {
            $this->transport = $transport;
            $this->driver = 'custom';
            return;
        }
        $this->driver = strtolower((string) (Env::get('MAIL_DRIVER', 'log') ?? 'log'));
        $this->transport = $this->driver === 'smtp' ? new SmtpMailTransport() : new LogMailTransport();
    }

    /**
     * @param array<string,mixed> $vars
     * @param array{channel?:string,email_type?:string} $options channel: accounts|noreply
     * @return array{ok:bool,path?:string,error?:string,error_type?:string,driver?:string,duration_ms?:int,fallback?:string}
     */
    public function sendTemplate(string $template, array $to, string $subject, array $vars = [], array $options = []): array
    {
        $started = hrtime(true);
        $emailType = (string) ($options['email_type'] ?? $template);
        $channel = (string) ($options['channel'] ?? MailAddresses::CHANNEL_ACCOUNTS);
        $meta = MailAddresses::forChannel($channel);
        $meta['email_type'] = $emailType;

        $ctx = array_merge(Logger::mailConfigStatus(), [
            'email_type' => $emailType,
            'template' => $template,
            'mail_channel' => $channel,
            'mail_driver' => $this->driver,
            'from_domain' => Logger::fromDomain($meta['from']),
            'recipient_domains' => array_values(array_unique(array_map(
                static fn (string $e): string => Logger::emailDomain($e),
                $to
            ))),
        ]);

        Logger::event('EMAIL_PIPELINE_STARTED', Logger::LEVEL_INFO, $ctx, Logger::CHANNEL_EMAIL);
        Logger::event('EMAIL_TEMPLATE_GENERATION_STARTED', Logger::LEVEL_DEBUG, $ctx, Logger::CHANNEL_EMAIL);

        try {
            $tplStarted = hrtime(true);
            $html = $this->render($template, array_merge($vars, [
                'supportEmail' => MailAddresses::support(),
                'privacyEmail' => MailAddresses::privacy(),
                'securityEmail' => MailAddresses::security(),
            ]));
            $text = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html))));
            $tplMs = (int) ((hrtime(true) - $tplStarted) / 1_000_000);
            Logger::event('EMAIL_TEMPLATE_GENERATED', Logger::LEVEL_INFO, array_merge($ctx, [
                'email_template_duration_ms' => $tplMs,
                'html_length' => strlen($html),
            ]), Logger::CHANNEL_EMAIL);
        } catch (\Throwable $e) {
            Logger::event('EMAIL_TEMPLATE_GENERATION_FAILED', Logger::LEVEL_ERROR, array_merge($ctx, [
                'error_type' => 'TEMPLATE_FAILED',
                'exception_class' => $e::class,
                'message' => Logger::sanitizeMessage($e->getMessage()),
            ]), Logger::CHANNEL_EMAIL);
            return [
                'ok' => false,
                'error' => 'Email template generation failed.',
                'error_type' => 'TEMPLATE_FAILED',
                'driver' => $this->driver,
            ];
        }

        Logger::event('EMAIL_SEND_STARTED', Logger::LEVEL_INFO, $ctx, Logger::CHANNEL_EMAIL);
        $sendStarted = hrtime(true);
        $result = $this->transport->send($to, $subject, $html, $text, $meta);
        $sendMs = (int) ((hrtime(true) - $sendStarted) / 1_000_000);
        $totalMs = (int) ((hrtime(true) - $started) / 1_000_000);
        $result['driver'] = $result['driver'] ?? $this->driver;
        $result['duration_ms'] = $totalMs;

        if (!empty($result['ok'])) {
            Logger::event('EMAIL_SEND_SUCCESS', Logger::LEVEL_INFO, array_merge($ctx, [
                'email_send_duration_ms' => $sendMs,
                'duration_ms' => $totalMs,
                'path' => $result['path'] ?? null,
            ]), Logger::CHANNEL_EMAIL);
        } else {
            $errorType = (string) ($result['error_type'] ?? Logger::classifySmtpError((string) ($result['error'] ?? '')));
            Logger::event('EMAIL_SEND_FAILED', Logger::LEVEL_ERROR, array_merge($ctx, [
                'error_type' => $errorType,
                'message' => Logger::sanitizeMessage((string) ($result['error'] ?? 'send failed')),
                'email_send_duration_ms' => $sendMs,
                'duration_ms' => $totalMs,
                'fallback' => $result['fallback'] ?? null,
            ]), Logger::CHANNEL_EMAIL);
        }

        return $result;
    }

    /** @param array<string,mixed> $vars */
    public function render(string $template, array $vars): string
    {
        $path = dirname(__DIR__, 2) . '/templates/email/' . $template . '.php';
        if (!is_file($path)) {
            throw new \RuntimeException("Email template not found: {$template}");
        }
        extract($vars, EXTR_SKIP);
        $appName = Env::get('APP_NAME', 'FMOS') ?? 'FMOS';
        $appUrl = rtrim((string) (Env::get('APP_URL', 'http://localhost:8080') ?? 'http://localhost:8080'), '/');
        $supportEmail = $supportEmail ?? MailAddresses::support();
        $privacyEmail = $privacyEmail ?? MailAddresses::privacy();
        $securityEmail = $securityEmail ?? MailAddresses::security();
        ob_start();
        include $path;
        return (string) ob_get_clean();
    }
}
