<?php
// app/Support/Pii.php
// Strips obvious personal data so it never lands in a GitHub issue or email.

namespace App\Support;

class Pii
{
    public static function scrub(?string $text): string
    {
        if ($text === null) {
            return '';
        }

        $text = preg_replace('/[\w.+-]+@[\w-]+\.[\w.-]+/', '[email]', $text);
        $text = preg_replace('/\b\+?\d[\d\s-]{7,}\d\b/', '[phone]', $text);
        $text = preg_replace('/\b\d{11,}\b/', '[id-number]', $text); // NIN / long IDs
        $text = preg_replace(
            '/("(?:password|token|secret|key|nin|bvn)"\s*:\s*)"[^"]*"/i',
            '$1"[redacted]"',
            $text
        );

        return $text ?? '';
    }
}
