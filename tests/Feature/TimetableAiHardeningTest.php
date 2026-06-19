<?php

namespace Tests\Feature;

use App\Support\TimetableService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Covers the resilience layer around AI timetable generation:
 *   - transient API failures (429/5xx/529) are retried, not surfaced;
 *   - generation steps down from the primary model to the fallback model;
 *   - the deterministic generator is the guaranteed final floor;
 *   - AI output is validated (clash-free AND covers every course) before use.
 *
 * These tests exercise the service directly with hand-built course arrays and a
 * faked HTTP client — no DB, seeding, or auth needed, so they are independent of
 * the rest of the suite.
 */
class TimetableAiHardeningTest extends TestCase
{
    private const ENDPOINT = 'api.anthropic.com/*';

    private TimetableService $service;
    private array $params;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TimetableService();

        // A real key so the AI path is attempted; primary + fallback distinct.
        config([
            'services.anthropic.key'            => 'test-key',
            'services.anthropic.model'          => 'claude-opus-4-8',
            'services.anthropic.fallback_model' => 'claude-haiku-4-5',
        ]);

        // Small grid keeps fakes readable: two days, two periods.
        $this->params = $this->service->normalizeParams([
            'days'    => ['Monday', 'Tuesday'],
            'periods' => 2,
        ]);
    }

    /** One class, two courses taught by two different teachers (no clash risk). */
    private function courses(): array
    {
        return [
            'JSS1A' => [
                ['subject_id' => 1, 'subject' => 'Mathematics', 'teacher_id' => 1, 'teacher' => 'Mr A', 'program_id' => 1, 'level' => '100'],
                ['subject_id' => 2, 'subject' => 'English',     'teacher_id' => 2, 'teacher' => 'Mr B', 'program_id' => 1, 'level' => '100'],
            ],
        ];
    }

    /** Wrap a model-output JSON string in the Anthropic messages response shape. */
    private function aiResponse(string $json): array
    {
        return ['content' => [['type' => 'text', 'text' => $json]]];
    }

    private function validGridJson(): string
    {
        // Both courses placed in distinct slots — clash-free and full coverage.
        return json_encode(['JSS1A' => ['Monday' => ['1' => 'Mathematics', '2' => 'English']]]);
    }

    public function test_transient_overload_is_retried_then_succeeds(): void
    {
        config(['services.anthropic.fallback_model' => null]); // isolate to one model

        Http::fake([self::ENDPOINT => Http::sequence()
            ->push(['type' => 'error'], 529)              // overloaded → retry
            ->push($this->aiResponse($this->validGridJson()), 200),
        ]);

        [, $engine] = $this->service->buildGrid($this->courses(), $this->params);

        $this->assertSame('ai', $engine, 'A retried success should still yield an AI grid.');
        Http::assertSentCount(2); // one failure + one success on the same model
    }

    public function test_persistent_overload_on_single_model_falls_back_to_deterministic(): void
    {
        config(['services.anthropic.fallback_model' => null]);

        Http::fake([self::ENDPOINT => Http::response(['type' => 'error'], 529)]);

        [$grid, $engine] = $this->service->buildGrid($this->courses(), $this->params);

        $this->assertSame('fallback', $engine, 'Exhausted retries must drop to the deterministic floor.');
        $this->assertTrue($this->service->isClashFree($grid, $this->params));
        Http::assertSentCount(3); // 1 initial + 2 retries
    }

    public function test_primary_failure_steps_down_to_fallback_model(): void
    {
        Http::fake([self::ENDPOINT => Http::sequence()
            ->push(['type' => 'error'], 529)   // primary attempt 1
            ->push(['type' => 'error'], 529)   // primary attempt 2
            ->push(['type' => 'error'], 529)   // primary attempt 3 (exhausted)
            ->push($this->aiResponse($this->validGridJson()), 200), // fallback model succeeds
        ]);

        [, $engine] = $this->service->buildGrid($this->courses(), $this->params);

        $this->assertSame('ai', $engine);
        Http::assertSentCount(4);
        // The grid was produced by the cheaper fallback model, not the primary.
        Http::assertSent(fn ($request) => $request['model'] === 'claude-haiku-4-5');
    }

    public function test_both_models_failing_falls_back_to_deterministic(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['type' => 'error'], 529)]);

        [$grid, $engine] = $this->service->buildGrid($this->courses(), $this->params);

        $this->assertSame('fallback', $engine);
        $this->assertTrue($this->service->isClashFree($grid, $this->params));
        Http::assertSentCount(6); // 2 models × (1 + 2 retries)
        Http::assertSent(fn ($request) => $request['model'] === 'claude-opus-4-8');
        Http::assertSent(fn ($request) => $request['model'] === 'claude-haiku-4-5');
    }

    public function test_clashing_ai_grid_is_rejected_in_favour_of_deterministic(): void
    {
        config(['services.anthropic.fallback_model' => null]);

        // Same teacher (id 1) for both classes, both placed Monday period 1 → clash.
        $courses = [
            'JSS1A' => [['subject_id' => 1, 'subject' => 'Maths',   'teacher_id' => 1, 'teacher' => 'Mr A', 'program_id' => 1, 'level' => '100']],
            'JSS2A' => [['subject_id' => 2, 'subject' => 'Physics', 'teacher_id' => 1, 'teacher' => 'Mr A', 'program_id' => 1, 'level' => '200']],
        ];
        $clashing = json_encode([
            'JSS1A' => ['Monday' => ['1' => 'Maths']],
            'JSS2A' => ['Monday' => ['1' => 'Physics']],
        ]);

        Http::fake([self::ENDPOINT => Http::response($this->aiResponse($clashing), 200)]);

        [$grid, $engine] = $this->service->buildGrid($courses, $this->params);

        $this->assertSame('fallback', $engine, 'A clashing AI grid must be rejected.');
        $this->assertTrue($this->service->isClashFree($grid, $this->params));
    }

    public function test_ai_grid_dropping_a_course_is_rejected_in_favour_of_deterministic(): void
    {
        config(['services.anthropic.fallback_model' => null]);

        // English is silently omitted — coverage validation must reject this.
        $incomplete = json_encode(['JSS1A' => ['Monday' => ['1' => 'Mathematics']]]);

        Http::fake([self::ENDPOINT => Http::response($this->aiResponse($incomplete), 200)]);

        [, $engine] = $this->service->buildGrid($this->courses(), $this->params);

        $this->assertSame('fallback', $engine, 'An AI grid that drops a course must be rejected.');
    }

    public function test_valid_ai_grid_is_accepted(): void
    {
        config(['services.anthropic.fallback_model' => null]);

        Http::fake([self::ENDPOINT => Http::response($this->aiResponse($this->validGridJson()), 200)]);

        [, $engine] = $this->service->buildGrid($this->courses(), $this->params);

        $this->assertSame('ai', $engine);
        Http::assertSentCount(1);
    }

    public function test_covers_all_courses_validator(): void
    {
        $courses = $this->courses();

        $full = ['JSS1A' => [
            'Monday' => [
                1 => ['subject_id' => 1],
                2 => ['subject_id' => 2],
            ],
        ]];
        $this->assertTrue($this->service->coversAllCourses($full, $courses, $this->params));

        $missing = ['JSS1A' => ['Monday' => [1 => ['subject_id' => 1]]]];
        $this->assertFalse($this->service->coversAllCourses($missing, $courses, $this->params));
    }

    public function test_no_api_key_skips_ai_entirely(): void
    {
        config(['services.anthropic.key' => null]);
        Http::fake(); // any call would be recorded

        [$grid, $engine] = $this->service->buildGrid($this->courses(), $this->params);

        $this->assertSame('fallback', $engine);
        $this->assertTrue($this->service->isClashFree($grid, $this->params));
        Http::assertNothingSent();
    }
}
