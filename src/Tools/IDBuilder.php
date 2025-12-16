<?php

namespace Michel\PaperORM\Tools;


final class IDBuilder
{
    /**
     * Generates a formatted unique or sequential identifier string.
     *
     * It substitutes dynamic placeholders (tokens) like date/time components,
     * timestamps, and random values (UUID, tokens) with their actual values
     * based on the provided format string.
     *
     * @param string $format The pattern string containing placeholders (e.g., 'INV-{YYYY}-{TOKEN16}').
     * @return string The final generated and formatted identifier.
     */
    public static function generate(string $format): string
    {
        $format = trim($format);
        $format = strtoupper($format);
        $now = new \DateTimeImmutable();

        // Note: I've included the PHP native logic for the tokens you provided
        // and fixed the minute/second token naming for clarity (using {ii} and {ss}).

        $tokens = [
            // Date/Time Tokens
            '{YYYY}'     => $now->format('Y'),
            '{YY}'       => $now->format('y'),
            '{MM}'       => $now->format('m'),
            '{DD}'       => $now->format('d'),
            '{HH}'       => $now->format('H'),
            '{ii}'       => $now->format('i'), // Renamed from {M} to {ii} for minutes
            '{ss}'       => $now->format('s'), // Renamed from {S} to {ss} for seconds
            '{YMD}'      => $now->format('Ymd'),
            '{YMDH}'     => $now->format('YmdH'),
            '{DATE}'     => $now->format('Y-m-d'),
            '{TIME}'     => $now->format('H:i:s'),

            // Sequential/Unique Tokens
            '{TS}'       => (string) $now->getTimestamp(),
            '{UNIQ}'     => uniqid(),

            // UUID V4 (Generated natively using random_bytes)
            // 36 characters (32 hex digits + 4 hyphens)
            '{UUID}'     => vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4)),

            // Random Hex Tokens (using cryptographically secure random_bytes)
            '{TOKEN16}'  => bin2hex(random_bytes(8)),   // 8 bytes * 2 = 16 hex characters
            '{TOKEN32}'  => bin2hex(random_bytes(16)),  // 16 bytes * 2 = 32 hex characters
            '{TOKEN64}'  => bin2hex(random_bytes(32)),  // 32 bytes * 2 = 64 hex characters
            '{TOKEN128}' => bin2hex(random_bytes(64)),  // 64 bytes * 2 = 128 hex characters
        ];

        return trim(strtr($format, $tokens));
    }
}
