<?php
// app/Actions/SendIncidentReport.php
// Turns a merged auto-fix PR into a plain-English email for a non-technical reader.

namespace App\Actions;

use App\Mail\IncidentReportMail;
use App\Services\ClaudeService;
use App\Services\GitHubService;
use App\Support\Pii;
use Illuminate\Support\Facades\Mail;

class SendIncidentReport
{
    public function __construct(
        private GitHubService $github,
        private ClaudeService $claude,
    ) {}

    public function handle(int $prNumber): void
    {
        $pr = $this->github->getPullRequest($prNumber);

        $prompt = <<<PROMPT
You are writing a status email to a college administrator who is NOT an engineer.
An automated system just detected and fixed a problem in the college portal.
Below is the developer-facing pull request.

Rewrite it as a short, calm, plain-English email. No jargon, no code, no file names.
Structure:
- One sentence: what went wrong and who it affected.
- One sentence: what the system did to fix it.
- One sentence: current status (is everything working now?).
- If a human still needs to review/approve, say so clearly at the very top.

Keep it under 120 words. Warm, factual, reassuring but not over-promising.

PULL REQUEST
Title: {$pr['title']}
Description:
{$this->cleanBody($pr['body'] ?? '')}
PROMPT;

        $summary = $this->claude->summarize($prompt, 600);

        Mail::to(config('ops.report_to'))->send(
            new IncidentReportMail(
                prNumber: $prNumber,
                summary: $summary,
                prUrl: $pr['html_url'] ?? '',
            )
        );
    }

    private function cleanBody(string $body): string
    {
        return Pii::scrub(mb_substr($body, 0, 4000));
    }
}
