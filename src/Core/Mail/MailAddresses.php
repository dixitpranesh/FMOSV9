<?php

declare(strict_types=1);

namespace Fmos\Core\Mail;

use Fmos\Core\Env;

/**
 * Canonical FMOS mailbox addresses (fmos.in).
 *
 * channels:
 * - accounts: verification, password reset, magic links
 * - noreply: automated system notifications (Reply-To → support)
 */
final class MailAddresses
{
    public const CHANNEL_ACCOUNTS = 'accounts';
    public const CHANNEL_NOREPLY = 'noreply';

    public static function accounts(): string
    {
        return (string) (Env::get('MAIL_FROM_ACCOUNTS', 'accounts@fmos.in') ?? 'accounts@fmos.in');
    }

    public static function noreply(): string
    {
        return (string) (Env::get('MAIL_FROM_NOREPLY', 'no-reply@fmos.in') ?? 'no-reply@fmos.in');
    }

    public static function support(): string
    {
        return (string) (Env::get('MAIL_SUPPORT', 'support@fmos.in') ?? 'support@fmos.in');
    }

    public static function billing(): string
    {
        return (string) (Env::get('MAIL_BILLING', 'billing@fmos.in') ?? 'billing@fmos.in');
    }

    public static function sales(): string
    {
        return (string) (Env::get('MAIL_SALES', 'sales@fmos.in') ?? 'sales@fmos.in');
    }

    public static function privacy(): string
    {
        return (string) (Env::get('MAIL_PRIVACY', 'privacy@fmos.in') ?? 'privacy@fmos.in');
    }

    public static function legal(): string
    {
        return (string) (Env::get('MAIL_LEGAL', 'legal@fmos.in') ?? 'legal@fmos.in');
    }

    public static function security(): string
    {
        return (string) (Env::get('MAIL_SECURITY', 'security@fmos.in') ?? 'security@fmos.in');
    }

    public static function abuse(): string
    {
        return (string) (Env::get('MAIL_ABUSE', 'abuse@fmos.in') ?? 'abuse@fmos.in');
    }

    /** Default Reply-To for transactional/system mail. */
    public static function replyTo(): string
    {
        return (string) (Env::get('MAIL_REPLY_TO', self::support()) ?? self::support());
    }

    /**
     * @return array{from:string,from_name:string,reply_to:?string}
     */
    public static function forChannel(string $channel): array
    {
        $fromName = (string) (Env::get('MAIL_FROM_NAME', Env::get('APP_NAME', 'FMOS') ?? 'FMOS') ?? 'FMOS');
        $channel = strtolower($channel);

        if ($channel === self::CHANNEL_NOREPLY) {
            return [
                'from' => self::noreply(),
                'from_name' => $fromName,
                'reply_to' => self::replyTo(),
            ];
        }

        // accounts (default for auth mail)
        $legacy = Env::get('MAIL_FROM_ADDRESS');
        $from = self::accounts();
        if (is_string($legacy) && $legacy !== '') {
            $from = $legacy;
        }

        return [
            'from' => $from,
            'from_name' => $fromName,
            'reply_to' => self::replyTo(),
        ];
    }
}
