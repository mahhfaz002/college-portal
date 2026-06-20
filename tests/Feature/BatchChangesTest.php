<?php

namespace Tests\Feature;

use App\Models\Payslip;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

class BatchChangesTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bootCollege();
    }

    // --- Course (subject) management is Academic Secretary's ---
    public function test_lecturer_cannot_manage_subjects(): void
    {
        $this->actingAs($this->userWithRole('lecturer'))
            ->post('/subjects', ['name' => 'Hacking'])->assertForbidden();
    }

    public function test_academic_secretary_can_add_subjects(): void
    {
        $this->actingAs($this->userWithRole('academic_secretary'))
            ->post('/subjects', ['name' => 'Community Health'])->assertRedirect();

        $this->assertDatabaseHas('subjects', ['name' => 'Community Health']);
    }

    // --- Score entry scoped to assigned courses ---
    public function test_lecturer_cannot_post_scores_for_unassigned_subject(): void
    {
        $lecturer = $this->userWithRole('lecturer');
        $subject = Subject::create(['name' => 'Unassigned Course']);

        // Passes the role gate (lecturer), but the controller blocks an
        // unassigned course.
        $this->actingAs($lecturer)->post('/scores/store', [
            'subject_id' => $subject->id,
            'scores' => [1 => ['ca' => 10, 'exam' => 20]],
        ])->assertForbidden();
    }

    // --- Term / session control is MIS's ---
    public function test_mis_sets_active_term(): void
    {
        $this->actingAs($this->userWithRole('mis'))->post('/term', [
            'current_session' => '2026/2027',
            'current_term' => 'Second Semester',
            'term_start' => '2027-01-10',
            'term_end' => '2027-04-10',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('Second Semester', Setting::get('current_term'));
        $this->assertSame('2026/2027', Setting::get('current_session'));
    }

    public function test_non_mis_cannot_set_term(): void
    {
        $this->actingAs($this->userWithRole('lecturer'))->post('/term', [
            'current_session' => '2026/2027', 'current_term' => 'Second Semester',
            'term_start' => '2027-01-10', 'term_end' => '2027-04-10',
        ])->assertForbidden();
    }

    public function test_mis_can_clear_assignments(): void
    {
        $lecturer = $this->userWithRole('lecturer');
        $class = SchoolClass::create(['name' => 'UG1A', 'section' => 'UG', 'level' => 'UG', 'active' => true]);
        $subject = Subject::create(['name' => 'Anatomy']);
        $lecturer->classes()->sync([$class->id]);
        $lecturer->subjects()->sync([$subject->id]);

        $this->actingAs($this->userWithRole('mis'))->post('/term/clear-assignments')->assertRedirect();

        $this->assertCount(0, $lecturer->fresh()->classes);
        $this->assertCount(0, $lecturer->fresh()->subjects);
    }

    // --- Notifications page loads for every role ---
    public function test_notifications_page_loads_for_each_role(): void
    {
        foreach (['mis', 'bursar', 'lecturer', 'student', 'registrar'] as $role) {
            $user = $this->userWithRole($role);
            $this->actingAs($user)->get('/notifications')->assertOk();
        }
    }

    // --- Payslip view is gated to payroll access (bursar) ---
    public function test_payslip_view_accessible_to_bursar_only(): void
    {
        $staff = $this->userWithRole('lecturer');
        $this->actingAs($this->userWithRole('bursar'))->post('/payroll/'.$staff->id, [
            'month' => '2026-06', 'basic_salary' => 50000, 'allowances' => 0, 'tax' => 0,
        ])->assertRedirect();
        $slip = Payslip::where('user_id', $staff->id)->firstOrFail();

        $this->actingAs($this->userWithRole('bursar'))->get("/payroll/{$slip->id}/slip")->assertOk();
        // A lecturer has no payroll route access at all.
        $this->actingAs($staff)->get("/payroll/{$slip->id}/slip")->assertForbidden();
    }
}
