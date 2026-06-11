<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

class Usernames
{
    /**
     * first-initial + other-initial (if any) + surname, lowercased and made
     * unique against existing usernames (a clash appends a serial).
     */
    public static function generate(string $first, ?string $other, string $surname): string
    {
        $base = Str::lower(
            Str::substr($first, 0, 1)
            . ($other ? Str::substr($other, 0, 1) : '')
            . preg_replace('/\s+/', '', $surname)
        );
        $base = preg_replace('/[^a-z0-9]/', '', $base) ?: 'user';

        $username = $base;
        $n = 1;
        while (User::where('username', $username)->exists()) {
            $username = $base . (++$n);
        }
        return $username;
    }
}
