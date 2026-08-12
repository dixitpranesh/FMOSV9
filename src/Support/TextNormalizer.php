<?php

declare(strict_types=1);

namespace Fmos\Support;

/**
 * Make labels safe for PDF (Helvetica WinAnsi) and Excel CSV.
 */
final class TextNormalizer
{
    public static function ascii(string $text): string
    {
        $map = [
            '—' => '-',
            '–' => '-',
            '−' => '-',
            '·' => '-',
            '•' => '-',
            '’' => "'",
            '‘' => "'",
            '“' => '"',
            '”' => '"',
            '…' => '...',
            '×' => 'x',
            '™' => 'TM',
            '®' => '(R)',
            '°' => ' deg',
            '²' => '2',
            '³' => '3',
            "\u{00A0}" => ' ',
        ];
        $text = strtr($text, $map);
        // Collapse leftover non-ASCII to closest ASCII when iconv available
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if (is_string($converted) && $converted !== '') {
                $text = $converted;
            }
        }
        $text = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        return trim($text);
    }
}
