<?php
// app/Services/ClaudeService.php
// Calls the Anthropic Messages API directly via the Http facade (no SDK needed).

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ClaudeService
{
    public function summarize(string $prompt, int $maxTokens = 700): string
    {
        $res = Http::withHeaders([
            'x-api-key'         => (string) config('ops.anthropic_key'),
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model'      => config('ops.model'),
            'max_tokens' => $maxTokens,
            'messages'   => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        $res->throw();

        // Response content is an array of blocks; keep the text ones.
        return collect($res->json('content', []))
            ->where('type', 'text')
            ->pluck('text')
            ->implode("\n");
    }
}
