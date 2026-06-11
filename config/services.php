<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Anthropic Claude — used for AI timetable generation (optional; a
    // deterministic generator runs when no key is set).
    'anthropic' => [
        'key'   => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-opus-4-8'),
    ],

    // Paystack — online payments. Per-college keys (stored on the College
    // record) take precedence; these env values are the platform fallback.
    // When no secret key is available AND the app is not in production, the
    // PaystackService falls back to a sandbox auto-confirm so the flow is
    // testable before live keys are supplied.
    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'base_url'   => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
        // One-off student platform-onboarding fee — settled to the PLATFORM
        // owner's Paystack account (the env keys above), not the college's.
        'platform_registration_fee' => env('PLATFORM_REGISTRATION_FEE', 5000),
    ],

];
