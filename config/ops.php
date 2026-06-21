<?php
// config/ops.php
// Central config for the self-healing pipeline. All values come from env,
// set in Laravel Cloud (app side). Never commit real values.

return [
    // GitHub
    'github_repo'  => env('GITHUB_REPO'),            // "mafindi/college-portal"
    'github_token' => env('GITHUB_AUTOFIX_TOKEN'),   // fine-grained PAT, Issues R/W only

    // Sentry inbound webhook shared secret (HMAC)
    'sentry_secret' => env('SENTRY_WEBHOOK_SECRET'),

    // Claude API
    'anthropic_key' => env('ANTHROPIC_API_KEY'),
    'model'         => env('OPS_MODEL', 'claude-sonnet-4-6'),

    // Reporting
    'report_to' => env('REPORT_TO_EMAIL'),
    'health_url' => env('HEALTH_URL'),               // https://portal.example/api/health

    // Shared secret the GitHub workflow uses to trigger the incident report endpoint
    'trigger_secret' => env('OPS_TRIGGER_SECRET'),

    // Triage gates
    'act_environments' => ['production'],
    'act_levels'       => ['error', 'fatal'],

    // Areas that force human review even if everything else looks routine
    'sensitive_keywords' => [
        'auth', 'login', 'session', 'password', 'role', 'permission',
        'grade', 'result', 'exam', 'score', 'payment', 'fee',
        'migration', 'policy',
    ],
];
