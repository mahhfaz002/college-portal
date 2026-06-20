<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Program;
use App\Models\Subject;
use App\Models\TimetablePlan;
use App\Models\User;
use App\Support\TimetableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

class TimetableTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    private Program $program;
    private User $lecturer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bootCollege();
        $this->wireAcademicStructure();
    }

    /**
     * One lecturer teaches courses at TWO levels of the same programme — distinct
     * timetable groups sharing a teacher, i.e. a deliberate clash risk the
     * generator must resolve.
     */
    private function wireAcademicStructure(): void
    {
        $department = Department::create(['name' => 'Health Sciences', 'acronym' => 'HS', 'section' => 'UG']);
        $this->program = Program::create([
            'name' => 'Nursing', 'acronym' => 'NUR', 'department_id' => $department->id,
        ]);

        $this->lecturer = $this->userWithRole('lecturer');

        $level100 = Subject::create([
            'name' => 'Anatomy', 'course_code' => 'NUR101',
            'department_id' => $department->id, 'program_id' => $this->program->id, 'level' => '100',
        ]);
        $level200 = Subject::create([
            'name' => 'Pharmacology', 'course_code' => 'NUR201',
            'department_id' => $department->id, 'program_id' => $this->program->id, 'level' => '200',
        ]);

        // Same lecturer across both levels — the clash risk.
        $level100->teachers()->attach($this->lecturer->id);
        $level200->teachers()->attach($this->lecturer->id);
    }

    public function test_deterministic_generator_is_clash_free(): void
    {
        $service = app(TimetableService::class);
        $params = $service->defaultParams();
        $courses = $service->gatherCourses();
        $grid = $service->deterministicGrid($courses, $params);

        $this->assertNotEmpty($grid);
        $this->assertTrue($service->isClashFree($grid, $params), 'Generated grid must have no lecturer double-booked.');
    }

    public function test_academic_secretary_can_generate_and_approve(): void
    {
        $secretary = $this->userWithRole('academic_secretary');

        // Params arrive from the form as STRINGS — must not 500 (Carbon int bug).
        $this->actingAs($secretary)->post('/timetable/generate', [
            'periods' => '6', 'period_minutes' => '45', 'start_time' => '08:30', 'break_after' => '3',
        ])->assertRedirect();

        $plan = TimetablePlan::where('status', 'draft')->latest()->firstOrFail();
        $this->assertNotEmpty($plan->entries);

        // No lecturer is double-booked in the persisted entries.
        $clashes = $plan->entries->whereNotNull('teacher_id')
            ->groupBy(fn ($e) => $e->day.'-'.$e->period_no.'-'.$e->teacher_id)
            ->filter(fn ($g) => $g->count() > 1);
        $this->assertCount(0, $clashes, 'Persisted timetable has a lecturer clash.');

        $this->actingAs($secretary)->post("/timetable/{$plan->id}/approve")->assertRedirect();
        $this->assertSame('approved', $plan->fresh()->status);
    }

    public function test_lecturer_and_student_see_only_their_own_timetable(): void
    {
        // Generate + approve as the Academic Secretary.
        $secretary = $this->userWithRole('academic_secretary');
        $this->actingAs($secretary)->post('/timetable/generate', []);
        $plan = TimetablePlan::where('status', 'draft')->latest()->firstOrFail();
        $this->actingAs($secretary)->post("/timetable/{$plan->id}/approve");

        // Lecturer's personal schedule loads.
        $this->actingAs($this->lecturer)->get('/timetable')->assertOk();

        // A level-100 Nursing student sees the level-100 course, not the level-200 one.
        $this->studentRecord([
            'email' => 'nursing100@pupil.test', 'program_id' => $this->program->id, 'level' => '100',
        ]);
        $studentUser = $this->userWithRole('student', ['email' => 'nursing100@pupil.test']);

        $this->actingAs($studentUser)->get('/timetable')->assertOk()
            ->assertSee('Anatomy')->assertDontSee('Pharmacology');
    }

    public function test_only_academic_secretary_can_generate(): void
    {
        // Generation is academic_secretary-only (manage_timetable); other staff are blocked.
        $this->actingAs($this->lecturer)
            ->post('/timetable/generate', [])->assertForbidden();
        $this->actingAs($this->userWithRole('mis'))
            ->post('/timetable/generate', [])->assertForbidden();
    }

    public function test_other_staff_see_published_timetable_readonly(): void
    {
        // Academic Secretary generates + approves.
        $secretary = $this->userWithRole('academic_secretary');
        $this->actingAs($secretary)->post('/timetable/generate', ['period_minutes' => '40']);
        $plan = TimetablePlan::where('status', 'draft')->latest()->firstOrFail();
        $this->actingAs($secretary)->post("/timetable/{$plan->id}/approve");

        // Registrar sees the published timetable but no generate control.
        $this->actingAs($this->userWithRole('registrar'))
            ->get('/timetable')->assertOk()
            ->assertSee('Published Timetable')
            ->assertDontSee('Generate Weekly Timetable');
    }
}
