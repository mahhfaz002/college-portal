<?php

namespace App\Support;

/**
 * College sections. Every department belongs to one section, which scopes the
 * whole academic structure (department → course of study → level → courses).
 *
 *   UG   = Undergraduate
 *   DIP  = Diploma
 *   CERT = Certificate
 */
class Sections
{
    public const UG   = 'UG';
    public const DIP  = 'DIP';
    public const CERT = 'CERT';

    public const ALL = [self::UG, self::DIP, self::CERT];

    public const LABELS = [
        self::UG   => 'Undergraduate (UG)',
        self::DIP  => 'Diploma (DIP)',
        self::CERT => 'Certificate (CERT)',
    ];

    public static function label(?string $code): string
    {
        return self::LABELS[$code] ?? ($code ?: '—');
    }

    /** Retained for backward-compatible call sites; no longer infers anything. */
    public static function fromClassName(?string $name): ?string
    {
        return null;
    }
}
