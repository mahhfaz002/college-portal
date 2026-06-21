<?php
// app/Services/GitHubService.php
// Thin wrapper over the GitHub REST API for the pipeline's needs.

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GitHubService
{
    private string $repo;
    private string $token;

    public function __construct()
    {
        $this->repo  = (string) config('ops.github_repo');
        $this->token = (string) config('ops.github_token');
    }

    private function client()
    {
        return Http::withToken($this->token)
            ->acceptJson()
            ->withHeaders(['X-GitHub-Api-Version' => '2022-11-28']);
    }

    /** True if an open issue already carries this Sentry fingerprint marker. */
    public function hasOpenIssue(string $fingerprint): bool
    {
        $q = "repo:{$this->repo} is:issue is:open in:title \"[sentry:{$fingerprint}]\"";
        $res = $this->client()->get('https://api.github.com/search/issues', ['q' => $q]);

        // Fail open: a duplicate issue is better than a missed error.
        return $res->ok() ? ($res->json('total_count', 0) > 0) : false;
    }

    public function createIssue(string $title, string $body, array $labels): array
    {
        $res = $this->client()->post(
            "https://api.github.com/repos/{$this->repo}/issues",
            ['title' => $title, 'body' => $body, 'labels' => $labels]
        );
        $res->throw();

        return $res->json();
    }

    public function getPullRequest(int $number): array
    {
        $res = $this->client()->get(
            "https://api.github.com/repos/{$this->repo}/pulls/{$number}"
        );
        $res->throw();

        return $res->json();
    }

    /** Auto-fix issues created since the given Y-m-d date. */
    public function recentAutoFixIssues(string $sinceDate): array
    {
        $q = "repo:{$this->repo} is:issue label:auto-fix created:>={$sinceDate}";
        $res = $this->client()->get('https://api.github.com/search/issues', ['q' => $q]);

        if (! $res->ok()) {
            return [];
        }

        return collect($res->json('items', []))->map(fn ($i) => [
            'number'      => $i['number'],
            'title'       => $i['title'],
            'state'       => $i['state'],
            'needs_human' => collect($i['labels'] ?? [])->contains(fn ($l) => ($l['name'] ?? '') === 'needs-human'),
        ])->all();
    }
}
