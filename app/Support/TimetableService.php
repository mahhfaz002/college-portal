<?php

namespace App\Support;

use App\Models\SchoolClass;
use App\Models\TimetableEntry;
use App\Models\TimetablePlan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Builds a weekly, clash-free timetable.
 *
 * AI (Anthropic Claude) proposes the grid; a deterministic validator guarantees
 * no teacher is double-booked. If the AI output clashes/is incomplete — or no
 * API key is set — a deterministic round-robin generator (clash-free by
 * construction) is used instead.
 */
class TimetableService
{
    public function defaultParams(): array
    {
        return [
            'days'          => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            'periods'       => 8,
            'period_minutes'=> 40,
            'start_time'    => '08:00',
            'break_after'   => 4,
            'break_minutes' => 20,
        ];
    }

    /**
     * Coerce numeric params to ints (HTML form posts arrive as strings, which
     * blows up Carbon::addMinutes). Always run this before using params.
     */
    public function normalizeParams(array $params): array
    {
        $params = array_merge($this->defaultParams(), $params);
        $params['periods']        = max(1, (int) $params['periods']);
        $params['period_minutes'] = max(5, (int) $params['period_minutes']);
        $params['break_after']    = (int) ($params['break_after'] ?? 0);
        $params['break_minutes']  = (int) ($params['break_minutes'] ?? 0);
        $params['start_time']     = $params['start_time'] ?: '08:00';
        return $params;
    }

    /**
     * Period rows: [['no'=>1,'start'=>'08:00','end'=>'08:40'], ...] with the break gap applied.
     */
    public function periodRows(array $params): array
    {
        $params = $this->normalizeParams($params);
        $rows = [];
        $cursor = Carbon::createFromFormat('H:i', $params['start_time']);
        for ($i = 1; $i <= $params['periods']; $i++) {
            $start = $cursor->copy();
            $end = $cursor->copy()->addMinutes($params['period_minutes']);
            $rows[] = ['no' => $i, 'start' => $start->format('H:i'), 'end' => $end->format('H:i')];
            $cursor = $end;
            if ($params['break_after'] === $i) {
                $cursor->addMinutes($params['break_minutes']);
            }
        }
        return $rows;
    }

    /**
     * Courses grouped by programme + level, derived from the course assignments
     * the Academic Secretary made (subject_teacher pivot). The group key is a
     * human label ("ND Science Lab Tech · L100") which is stored on each entry's
     * class_arm for display; program_id + level travel on each course so entries
     * can be filtered structurally.
     *
     * [label => [['subject_id','subject','teacher_id','teacher','program_id','level'], ...]]
     */
    public function gatherCourses(): array
    {
        $courses = [];

        $subjects = \App\Models\Subject::with(['teachers', 'program'])
            ->whereNotNull('program_id')
            ->orderBy('level')->orderBy('course_code')->get();

        foreach ($subjects as $subject) {
            $teacher = $subject->teachers->first();   // one lecturer per course
            if (!$teacher || !$subject->program) {
                continue;                              // unassigned course → skip
            }

            $level = $subject->level ?: '';
            $label = trim($subject->program->name.($level ? " · L{$level}" : ''));

            $courses[$label][] = [
                'subject_id' => $subject->id,
                'subject'    => $subject->course_code ? "{$subject->name} ({$subject->course_code})" : $subject->name,
                'teacher_id' => $teacher->id,
                'teacher'    => $teacher->name,
                'program_id' => $subject->program_id,
                'level'      => $level,
            ];
        }

        return $courses;
    }

    /**
     * Generate + persist a draft plan. Returns the plan (with entries).
     */
    public function generate(array $params, ?int $userId = null): TimetablePlan
    {
        $params = $this->normalizeParams($params);
        $rows = $this->periodRows($params);
        $courses = $this->gatherCourses();

        [$grid, $engine] = $this->buildGrid($courses, $params);

        $plan = TimetablePlan::create([
            'term'        => setting('current_term', ''),
            'session'     => setting('current_session', ''),
            'status'      => 'draft',
            'params'      => $params,
            'engine'      => $engine,
            'generated_by'=> $userId,
        ]);

        // Materialise grid → entries.
        foreach ($grid as $classArm => $days) {
            foreach ($params['days'] as $day) {
                foreach ($rows as $row) {
                    $course = $days[$day][$row['no']] ?? null;
                    if (!$course) {
                        continue; // free period
                    }
                    TimetableEntry::create([
                        'plan_id'    => $plan->id,
                        'class_arm'  => $classArm,                  // display label
                        'program_id' => $course['program_id'] ?? null,
                        'level'      => $course['level'] ?? null,
                        'day'        => $day,
                        'period_no'  => $row['no'],
                        'start_time' => $row['start'],
                        'end_time'   => $row['end'],
                        'subject_id' => $course['subject_id'],
                        'teacher_id' => $course['teacher_id'],
                    ]);
                }
            }
        }

        return $plan->load('entries');
    }

    /**
     * Returns [grid, engine]. grid: [class][day][period_no] => course array.
     */
    private function buildGrid(array $courses, array $params): array
    {
        if (empty($courses)) {
            return [[], 'fallback'];
        }

        // Try AI first if a key is configured.
        if (config('services.anthropic.key')) {
            try {
                $aiGrid = $this->aiGrid($courses, $params);
                if ($aiGrid && $this->isClashFree($aiGrid, $params)) {
                    return [$aiGrid, 'ai'];
                }
            } catch (\Throwable $e) {
                Log::warning('Timetable AI generation failed: '.$e->getMessage());
            }
        }

        return [$this->deterministicGrid($courses, $params), 'fallback'];
    }

    /**
     * Deterministic round-robin: clash-free by construction. For each slot, each
     * class takes its next course whose teacher is not already busy that slot.
     */
    public function deterministicGrid(array $courses, array $params): array
    {
        $grid = [];
        $pointer = [];
        foreach ($courses as $class => $list) {
            $pointer[$class] = 0;
        }

        foreach ($params['days'] as $day) {
            for ($p = 1; $p <= $params['periods']; $p++) {
                $busy = [];
                foreach ($courses as $class => $list) {
                    $n = count($list);
                    $assigned = null;
                    for ($k = 0; $k < $n; $k++) {
                        $idx = ($pointer[$class] + $k) % $n;
                        $course = $list[$idx];
                        if (!isset($busy[$course['teacher_id']])) {
                            $assigned = $course;
                            $pointer[$class] = $idx + 1;
                            break;
                        }
                    }
                    if ($assigned) {
                        $busy[$assigned['teacher_id']] = true;
                        $grid[$class][$day][$p] = $assigned;
                    }
                }
            }
        }

        return $grid;
    }

    /**
     * No teacher appears in two classes at the same day+period.
     */
    public function isClashFree(array $grid, array $params): bool
    {
        foreach ($params['days'] as $day) {
            for ($p = 1; $p <= $params['periods']; $p++) {
                $seen = [];
                foreach ($grid as $class => $days) {
                    $course = $days[$day][$p] ?? null;
                    if (!$course || !($course['teacher_id'] ?? null)) {
                        continue;
                    }
                    if (isset($seen[$course['teacher_id']])) {
                        return false;
                    }
                    $seen[$course['teacher_id']] = true;
                }
            }
        }
        return true;
    }

    /**
     * Ask Claude to lay out the grid by subject name, then map back to courses.
     */
    private function aiGrid(array $courses, array $params): ?array
    {
        $payload = [];
        foreach ($courses as $class => $list) {
            $payload[$class] = array_map(fn ($c) => ['subject' => $c['subject'], 'teacher' => $c['teacher']], $list);
        }

        $prompt = "You are a school timetabling assistant. Build a weekly class timetable.\n".
            "Days: ".implode(', ', $params['days']).". Periods per day: {$params['periods']}.\n".
            "For each class below you are given its courses (subject + teacher). A teacher must NEVER ".
            "teach two different classes in the same day+period. Spread each class's subjects across the week.\n".
            "Classes and courses (JSON):\n".json_encode($payload)."\n\n".
            "Respond with ONLY a JSON object, no prose, of the form: ".
            "{\"CLASS\": {\"Monday\": {\"1\": \"SubjectName\", \"2\": \"SubjectName\"}}}. ".
            "Use the exact subject names given; periods are numbered 1..{$params['periods']}; ".
            "omit a period to leave it free.";

        $resp = Http::withHeaders([
            'x-api-key'         => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
            'model'      => config('services.anthropic.model', 'claude-opus-4-8'),
            'max_tokens' => 16000,
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ]);

        if (!$resp->successful()) {
            Log::warning('Anthropic timetable call failed: '.$resp->status());
            return null;
        }

        $text = collect($resp->json('content', []))->firstWhere('type', 'text')['text'] ?? '';
        $json = $this->extractJson($text);
        if (!is_array($json)) {
            return null;
        }

        // Map subject names → course arrays per class.
        $grid = [];
        foreach ($json as $class => $days) {
            if (!isset($courses[$class]) || !is_array($days)) {
                continue;
            }
            $byName = collect($courses[$class])->keyBy('subject');
            foreach ($days as $day => $slots) {
                if (!in_array($day, $params['days'], true) || !is_array($slots)) {
                    continue;
                }
                foreach ($slots as $period => $subjectName) {
                    $p = (int) $period;
                    if ($p < 1 || $p > $params['periods']) {
                        continue;
                    }
                    $course = $byName->get($subjectName);
                    if ($course) {
                        $grid[$class][$day][$p] = $course;
                    }
                }
            }
        }

        return $grid ?: null;
    }

    private function extractJson(string $text): mixed
    {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }
        return json_decode(substr($text, $start, $end - $start + 1), true);
    }
}
