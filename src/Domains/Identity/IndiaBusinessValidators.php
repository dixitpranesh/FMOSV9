<?php

declare(strict_types=1);

namespace Fmos\Domains\Identity;

/**
 * India-focused format validators (GSTIN/PAN/PIN/mobile). Format only — not government verification.
 */
final class IndiaBusinessValidators
{
    /** @return list<string> GST state codes 01–38 (+ 97) commonly used */
    public static function gstStateCodes(): array
    {
        $codes = [];
        for ($i = 1; $i <= 38; $i++) {
            $codes[] = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
        }
        $codes[] = '97';
        return $codes;
    }

    public static function normalizeGstin(?string $gstin): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $gstin) ?? '');
    }

    public static function normalizePan(?string $pan): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $pan) ?? '');
    }

    /** @return array{ok:bool,message:?string} */
    public static function validateGstin(string $gstin): array
    {
        $g = self::normalizeGstin($gstin);
        if (strlen($g) !== 15) {
            return ['ok' => false, 'message' => 'GSTIN must be exactly 15 characters.'];
        }
        if (!preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', $g)) {
            return ['ok' => false, 'message' => 'Invalid GSTIN format.'];
        }
        $state = substr($g, 0, 2);
        if (!in_array($state, self::gstStateCodes(), true)) {
            return ['ok' => false, 'message' => 'Invalid GSTIN state code.'];
        }
        $pan = substr($g, 2, 10);
        $panCheck = self::validatePan($pan);
        if (!$panCheck['ok']) {
            return ['ok' => false, 'message' => 'Invalid GSTIN PAN portion.'];
        }
        if (!self::gstinChecksumValid($g)) {
            return ['ok' => false, 'message' => 'Invalid GSTIN checksum.'];
        }
        return ['ok' => true, 'message' => null];
    }

    public static function gstinChecksumValid(string $gstin): bool
    {
        $g = self::normalizeGstin($gstin);
        if (strlen($g) !== 15) {
            return false;
        }
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $factor = 1;
        $sum = 0;
        for ($i = 0; $i < 14; $i++) {
            $codePoint = strpos($chars, $g[$i]);
            if ($codePoint === false) {
                return false;
            }
            $product = $codePoint * $factor;
            $sum += intdiv($product, 36) + ($product % 36);
            $factor = $factor === 1 ? 2 : 1;
        }
        $check = (36 - ($sum % 36)) % 36;
        return $g[14] === $chars[$check];
    }

    /** @return array{ok:bool,message:?string} */
    public static function validatePan(string $pan): array
    {
        $p = self::normalizePan($pan);
        if (!preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $p)) {
            return ['ok' => false, 'message' => 'Invalid PAN format.'];
        }
        return ['ok' => true, 'message' => null];
    }

    /** @return array{ok:bool,message:?string} */
    public static function validatePin(string $pin): array
    {
        if (!preg_match('/^[1-9][0-9]{5}$/', trim($pin))) {
            return ['ok' => false, 'message' => 'Enter a valid 6-digit PIN code.'];
        }
        return ['ok' => true, 'message' => null];
    }

    /** @return array{ok:bool,message:?string} */
    public static function validateMobile(string $mobile, string $countryCode = '+91'): array
    {
        $m = preg_replace('/\s+|-/', '', $mobile) ?? '';
        $cc = trim($countryCode);
        if ($cc === '+91' || $cc === '91') {
            if (!preg_match('/^[6-9][0-9]{9}$/', $m)) {
                return ['ok' => false, 'message' => 'Enter a valid 10-digit Indian mobile number.'];
            }
            return ['ok' => true, 'message' => null];
        }
        if (!preg_match('/^[0-9]{6,15}$/', $m)) {
            return ['ok' => false, 'message' => 'Enter a valid mobile number.'];
        }
        return ['ok' => true, 'message' => null];
    }

    public static function maskPan(?string $pan): ?string
    {
        $p = self::normalizePan($pan);
        if ($p === '') {
            return null;
        }
        if (strlen($p) < 4) {
            return '****';
        }
        return str_repeat('*', max(0, strlen($p) - 4)) . substr($p, -4);
    }

    public static function maskGstin(?string $gstin): ?string
    {
        $g = self::normalizeGstin($gstin);
        if ($g === '') {
            return null;
        }
        if (strlen($g) < 4) {
            return '****';
        }
        return substr($g, 0, 2) . str_repeat('*', strlen($g) - 4) . substr($g, -2);
    }

    /** @return list<string> */
    public static function constitutions(): array
    {
        return [
            'PROPRIETORSHIP',
            'PARTNERSHIP',
            'LLP',
            'PRIVATE_LIMITED',
            'PUBLIC_LIMITED',
            'OPC',
            'HUF',
            'SOCIETY',
            'TRUST',
            'OTHER',
        ];
    }
}
