<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TimetablePlan;
use App\Models\User;
use App\Support\TimetableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->wireAssignments();
    }

    /**
     * Create a scenario where ONE teacher teaches a subject across TWO classes —
     * a deliberate clash risk the generator must resolve.
     */
    private function wireAssignments(): void
    {
        $maths = Subject::firstOrCreate(['name' => 'Mathematics']);
        $english = Subject::firstOrCreate(['name' => 'English Language']);

        $jss1a = SchoolClass::firstOrCreate(['name' => 'JSS1A']);
        $jss2a = SchoolClass::firstOrCreate(['name' => 'JSS2A']);

        $t1 = User::where('role', 'teacher')->firstOrFail();
        $t2 = User::create([
            'name' => 'Mr. Two', 'email' => 't2@mahhfaz.edu', 'password' => bcrypt('x'),
            'role' => 'teacher', 'must_change_password' => false,
        ]);

        // t1 teaches Maths to BOTH JSS1A and JSS2A (clash risk).
        $t1->classes()->sync([$jss1a->id, $jss2a->id]);
        $t1->subjects()->sync([$maths->id]);
        // t2 teaches English to both.
        $t2->classes()->sync([$jss1a->id, $jss2a->id]);
        $t2->subjects()->sync([$english->id]);
    }

    public function test_deterministic_generator_is_clash_free(): void
    {
        $service = app(TimetableService::class);
        $params = $service->defaultParams();
        $courses = $service->gatherCourses();
        $grid = $service->deterministicGrid($courses, $params);

        $this->assertNotEmpty($grid);
        $this->assertTrue($service->isClashFree($grid, $params), 'Generated grid must have no teacher double-booked.');
    }

    public function test_principal_can_generate_and_approve(): void
    {
        $principal = User::where('role', 'principal')->firstOrFail();

        $this->actingAs($principal)->post('/timetable/generate', ['periods' => 6])->assertRedirect();
        $plan = TimetablePlan::where('status', 'draft')->latest()->firstOrFail();
        $this->assertNotEmpty($plan->entries);

        // No teacher is double-booked in the persisted entries.
        $clashes = $plan->entries->whereNotNull('teacher_id')
            ->groupBy(fn ($e) => $e->day.'-'.$e->period_no.'-'.$e->teacher_id)
            ->filter(fn ($g) => $g->count() > 1);
        $this->assertCount(0, $clashes, 'Persisted timetable has a teacher clash.');

        $this->actingAs($principal)->post("/timetable/{$plan->id}/approve")->assertRedirect();
        $this->assertSame('approved', $plan->fresh()->status);
    }

    public function test_teacher_and_student_see_only_their_own_timetable(): void
    {
        // Generate + approve as principal.
        $principal = User::where('role', 'principal')->firstOrFail();
        $this->actingAs($principal)->post('/timetable/generate', []);
        $plan = TimetablePlan::where('status', 'draft')->latest()->firstOrFail();
        $this->actingAs($principal)->post("/timetable/{$plan->id}/approve");

        // Teacher view loads.
        $teacher = User::where('role', 'teacher')->firstOrFail();
        $this->actingAs($teacher)->get('/timetable')->assertOk();

        // Student of JSS1A sees JSS1A and not JSS2A.
        Student::create(['full_name' => 'TT Pupil', 'admission_number' => 'TT/1', 'class_arm' => 'JSS1A', 'parent_phone' => '0', 'fees_balance' => 0, 'email' => 'tt@pupil.local']);
        $studentUser = User::create(['name' => 'TT', 'email' => 'tt@pupil.local', 'password' => bcrypt('x'), 'role' => 'student', 'must_change_password' => false]);
        $this->actingAs($studentUser)->get('/timetable')->assertOk()->assertSee('JSS1A')->assertDontSee('JSS2A');
    }

    public function test_teacher_cannot_generate(): void
    {
        $teacher = User::where('role', 'teacher')->firstOrFail();
        $this->actingAs($teacher)->post('/timetable/generate', [])->assertForbidden();
    }
}
