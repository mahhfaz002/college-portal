<?php
// app/Actions/SendDailyDigest.php
// A daily heartbeat: live health + what the auto-fixer did in the last 24h.

namespace App\Actions;

use App\Mail\DailyDigestMail;
use App\Services\ClaudeService;
use App\Services\GitHubService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class SendDailyDigest
{
    public function __construct(
        private GitHubService $github,
        private ClaudeService $claude,
    ) {}

    public function handle(): void
    {
        $health = $this->liveHealth();
        $issues = $this->github->recentAutoFixIssues(now()->subDay()->toDateString());

        $facts = [
            'health'         => $health,
            'incidents_24h'  => count($issues),
            'resolved'       => collect($issues)->where('state', 'closed')->count(),
            'awaiting_human' => collect($issues)->where('needs_human', true)->where('state', 'open')->count(),
            'items'          => $issues,
        ];

        $prompt = "Write a brief daily status email for a college administrator (not an "
            ."engineer) about their college portal. Plain English, under 130 words, calm and "
            ."clear. Lead with whether the system is healthy right now. Then summarize the last "
            ."24 hours of automated incidents: how many, how many were auto-fixed, and how many "
            ."are waiting for a human to approve. If anything is waiting for a human, make that "
            ."the first thing after the health line. If there were zero incidents, say so plainly "
            ."and positively.\n\nDATA (JSON):\n".json_encode($facts, JSON_PRETTY_PRINT);

        $summary = $this->claude->summarize($prompt, 700);

        Mail::to(config('ops.report_to'))->send(
            new DailyDigestMail(
                summary: $summary,
                healthy: ($health['status'] ?? '') === 'healthy',
                incidents: count($issues),
            )
        );
    }

    private function liveHealth(): array
    {
        try {
            $res = Http::timeout(10)->get((string) config('ops.health_url'));

            return [
                'ok'     => $res->ok(),
                'status' => $res->json('status', 'unknown'),
                'checks' => $res->json('checks', []),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'status' => 'unreachable', 'checks' => []];
        }
    }
}
