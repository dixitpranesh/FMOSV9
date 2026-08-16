<?php

declare(strict_types=1);

namespace Fmos\Core;

/**
 * Application-layer envelope encryption for sensitive fields (PAN/GSTIN).
 * Format: enc:v1:<base64(iv|tag|ciphertext)>
 */
final class FieldCrypto
{
    private const PREFIX = 'enc:v1:';

    public static function encrypt(?string $plaintext): ?string
    {
        if ($plaintext === null || $plaintext === '') {
            return $plaintext;
        }
        if (str_starts_with($plaintext, self::PREFIX)) {
            return $plaintext;
        }
        $key = self::key();
        if ($key === null) {
            return $plaintext;
        }
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) {
            throw new \RuntimeException('Unable to encrypt field.');
        }
        return self::PREFIX . base64_encode($iv . $tag . $cipher);
    }

    public static function decrypt(?string $stored): ?string
    {
        if ($stored === null || $stored === '') {
            return $stored;
        }
        if (!str_starts_with($stored, self::PREFIX)) {
            return $stored;
        }
        $key = self::key();
        if ($key === null) {
            return null;
        }
        $raw = base64_decode(substr($stored, strlen(self::PREFIX)), true);
        if ($raw === false || strlen($raw) < 28) {
            return null;
        }
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $cipher = substr($raw, 28);
        $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        return $plain === false ? null : $plain;
    }

    private static function key(): ?string
    {
        $appKey = Env::get('APP_KEY');
        if (!is_string($appKey) || strlen($appKey) < 16) {
            return null;
        }
        return hash('sha256', $appKey, true);
    }
}
