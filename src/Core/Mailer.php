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

    public function __construct(?MailTransport $transport = null)
    {
        if ($transport !== null) {
            $this->transport = $transport;
            return;
        }
        $driver = strtolower((string) (Env::get('MAIL_DRIVER', 'log') ?? 'log'));
        $this->transport = $driver === 'smtp' ? new SmtpMailTransport() : new LogMailTransport();
    }

    /**
     * @param array<string,mixed> $vars
     * @param array{channel?:string} $options channel: accounts|noreply
     * @return array{ok:bool,path?:string,error?:string}
     */
    public function sendTemplate(string $template, array $to, string $subject, array $vars = [], array $options = []): array
    {
        $channel = (string) ($options['channel'] ?? MailAddresses::CHANNEL_ACCOUNTS);
        $meta = MailAddresses::forChannel($channel);
        $html = $this->render($template, array_merge($vars, [
            'supportEmail' => MailAddresses::support(),
            'privacyEmail' => MailAddresses::privacy(),
            'securityEmail' => MailAddresses::security(),
        ]));
        $text = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html))));
        return $this->transport->send($to, $subject, $html, $text, $meta);
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
