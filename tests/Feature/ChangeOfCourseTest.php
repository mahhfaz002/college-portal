<?php

namespace Tests\Feature;

use App\Models\ChangeOfCourseRequest;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

class ChangeOfCourseTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    private Program $current;
    private Program $target;
    private Student $student;
    private User $studentUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bootCollege();

        $dept = Department::create(['name' => 'Health Sciences', 'acronym' => 'HS', 'section' => 'UG']);
        $this->current = Program::create(['name' => 'Nursing', 'acronym' => 'NUR', 'department_id' => $dept->id]);
        $this->target  = Program::create(['name' => 'Public Health', 'acronym' => 'PH', 'department_id' => $dept->id]);

        $this->student = $this->studentRecord([
            'email' => 'coc.student@example.test', 'program_id' => $this->current->id,
            'department_id' => $dept->id, 'level' => '200',
        ]);
        $this->studentUser = $this->userWithRole('student', ['email' => 'coc.student@example.test']);
    }

    public function test_full_change_of_course_flow_moves_student_to_new_course(): void
    {
        // 1. Student applies → request created (pending payment) + fee invoice.
        $this->actingAs($this->studentUser)->post('/change-of-course', [
            'requested_program_id' => $this->target->id,
            'reason' => 'Better fit for my career goals.',
        ])->assertRedirect();

        $req = ChangeOfCourseRequest::firstOrFail();
        $this->assertSame('pending_payment', $req->status);
        $this->assertNotNull($req->invoice_id);

        $invoice = Invoice::findOrFail($req->invoice_id);
        $this->assertEquals(ChangeOfCourseRequest::FEE, (float) $invoice->amount);

        // 2. Pay via the sandbox gateway (no live keys in tests) → forwarded for review.
        $this->actingAs($this->studentUser)->get(route('payments.sandbox', $invoice))->assertRedirect();
        $this->assertSame('under_review', $req->fresh()->status);

        // 3. Academic Secretary recommends.
        $this->actingAs($this->userWithRole('academic_secretary'))
            ->post("/change-of-course/{$req->id}/recommend", ['decision' => 'recommend', 'note' => 'Good case.'])
            ->assertRedirect();
        $this->assertSame('recommended', $req->fresh()->status);

        // 4. Registrar approves → student moved onto the new course.
        $this->actingAs($this->userWithRole('registrar'))
            ->post("/change-of-course/{$req->id}/decide", ['decision' => 'approve'])
            ->assertRedirect();

        $this->assertSame('approved', $req->fresh()->status);
        $this->assertSame($this->target->id, $this->student->fresh()->program_id);
    }

    public function test_registrar_can_reject_with_reason(): void
    {
        $req = ChangeOfCourseRequest::create([
            'college_id' => $this->college->id, 'student_id' => $this->student->id,
            'current_program_id' => $this->current->id, 'requested_program_id' => $this->target->id,
            'reason' => 'x', 'status' => 'recommended',
        ]);

        $this->actingAs($this->userWithRole('registrar'))
            ->post("/change-of-course/{$req->id}/decide", ['decision' => 'reject'])
            ->assertSessionHasErrors('reason'); // reason required to reject

        $this->actingAs($this->userWithRole('registrar'))
            ->post("/change-of-course/{$req->id}/decide", ['decision' => 'reject', 'reason' => 'Quota full.'])
            ->assertRedirect();

        $this->assertSame('rejected', $req->fresh()->status);
        $this->assertSame('Quota full.', $req->fresh()->registrar_reason);
        // Student stays on their original course.
        $this->assertSame($this->current->id, $this->student->fresh()->program_id);
    }

    public function test_role_gating(): void
    {
        // Non-students cannot open the application page.
        $this->actingAs($this->userWithRole('lecturer'))->get('/change-of-course')->assertForbidden();
        // Students cannot reach the academic-review or registrar screens.
        $this->actingAs($this->studentUser)->get('/change-of-course/review')->assertForbidden();
        $this->actingAs($this->studentUser)->get('/change-of-course/approvals')->assertForbidden();
    }
}
