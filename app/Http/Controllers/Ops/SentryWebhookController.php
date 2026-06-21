<?php
// app/Http/Controllers/Ops/SentryWebhookController.php
// The dispatcher. Sentry POSTs error alerts here. Verifies the signature,
// filters noise, scrubs PII, dedups, then opens a labeled GitHub issue which
// triggers the auto-fix workflow. It fixes nothing itself.

namespace App\Http\Controllers\Ops;

use App\Services\GitHubService;
use App\Support\Pii;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SentryWebhookController
{
    public function __invoke(Request $request, GitHubService $github): JsonResponse
    {
        $raw = $request->getContent();

        // 1) Authenticity — HMAC-SHA256 of the raw body with the shared secret.
        if (! $this->verifySignature($raw, $request->header('sentry-hook-signature'))) {
            return response()->json(['error' => 'bad signature'], 401);
        }

        $payload = json_decode($raw, true) ?: [];
        $event = $payload['data']['event'] ?? $payload['data']['issue'] ?? [];

        $level = $event['level'] ?? 'error';
        $environment = $event['environment'] ?? 'production';

        // 2) Gate on severity + environment.
        if (! in_array($level, config('ops.act_levels'), true)
            || ! in_array($environment, config('ops.act_environments'), true)) {
            return response()->json(['skipped' => 'filtered'], 202);
        }

        $fingerprint = (string) (
            $event['issue_id'] ?? $event['id'] ?? $event['short_id'] ?? (string) \Illuminate\Support\Str::uuid()
        );

        // 3) Dedup.
        if ($github->hasOpenIssue($fingerprint)) {
            return response()->json(['skipped' => 'duplicate'], 202);
        }

        // 4) Compose a scrubbed, structured issue.
        $title = mb_substr(Pii::scrub($event['title'] ?? $event['metadata']['value'] ?? 'Unhandled error'), 0, 120);
        $culprit = Pii::scrub($event['culprit'] ?? $event['transaction'] ?? 'unknown');
        $permalink = $event['web_url'] ?? $payload['data']['issue']['permalink'] ?? '';

        $haystack = mb_strtolower($title.' '.$culprit);
        $sensitive = collect(config('ops.sensitive_keywords'))
            ->contains(fn ($w) => str_contains($haystack, $w));

        $labels = ['auto-fix', "severity:{$level}"];
        if ($sensitive) {
            $labels[] = 'needs-human';
        }

        $body = collect([
            "**[sentry:{$fingerprint}]**",
            '',
            "**Title:** {$title}",
            "**Where:** `{$culprit}`",
            "**Level:** {$level}  |  **Env:** {$environment}",
            $permalink ? "**Sentry:** {$permalink}" : '',
            '',
            $sensitive
                ? '> ⚠️ Touches a sensitive area (auth/grades/payments/migrations). A human MUST review and merge this PR.'
                : '',
            '',
            '---',
            '_Filed automatically by the Sentry dispatcher. The auto-fix agent will pick this up._',
        ])->filter()->implode("\n");

        $github->createIssue("[auto] {$title}", $body, $labels);

        return response()->json(['ok' => true], 201);
    }

    private function verifySignature(string $rawBody, ?string $signature): bool
    {
        $secret = (string) config('ops.sentry_secret');
        if (! $signature || $secret === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signature);
    }
}
