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

    private Department $currentDept;
    private Department $newDept;
    private Program $current;
    private Program $target;
    private Student $student;
    private User $studentUser;
    private User $currentHod;
    private User $newHod;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bootCollege();

        $this->currentDept = Department::create(['name' => 'Nursing Sciences', 'acronym' => 'NS', 'section' => 'UG']);
        $this->newDept     = Department::create(['name' => 'Public Health', 'acronym' => 'PH', 'section' => 'UG']);

        $this->current = Program::create(['name' => 'Nursing', 'acronym' => 'NUR', 'department_id' => $this->currentDept->id]);
        $this->target  = Program::create(['name' => 'Community Health', 'acronym' => 'CH', 'department_id' => $this->newDept->id, 'registration_fee' => 40000]);

        $this->student = $this->studentRecord([
            'email' => 'coc.student@example.test', 'program_id' => $this->current->id,
            'department_id' => $this->currentDept->id, 'level' => '200',
        ]);
        $this->studentUser = $this->userWithRole('student', ['email' => 'coc.student@example.test']);

        // A HOD for each department (the linkage is User.department_id).
        $this->currentHod = $this->userWithRole('hod', ['email' => 'hod.current@example.test', 'department_id' => $this->currentDept->id]);
        $this->newHod     = $this->userWithRole('hod', ['email' => 'hod.new@example.test', 'department_id' => $this->newDept->id]);
    }

    private function applyAndPay(): ChangeOfCourseRequest
    {
        $this->actingAs($this->studentUser)->post('/change-of-course', [
            'requested_program_id' => $this->target->id,
            'reason' => 'Better fit for my career goals.',
        ])->assertRedirect();

        $req = ChangeOfCourseRequest::firstOrFail();
        $this->assertSame('pending_payment', $req->status);

        // Pay the application fee via the sandbox gateway → enters AS queue.
        $this->actingAs($this->studentUser)->get(route('payments.sandbox', Invoice::findOrFail($req->invoice_id)))->assertRedirect();
        $this->assertSame('secretary_review', $req->fresh()->status);

        return $req->fresh();
    }

    public function test_full_chain_approves_and_migrates_after_new_fee(): void
    {
        $req = $this->applyAndPay();
        $as  = $this->userWithRole('academic_secretary');

        // AS comments + forwards to the NEW department HOD.
        $this->actingAs($as)->post(route('change-of-course.forward-new-hod', $req), ['comment' => 'Forwarding for review.'])->assertRedirect();
        $this->assertSame('new_hod_review', $req->fresh()->status);

        // New HOD accepts.
        $this->actingAs($this->newHod)->post(route('change-of-course.hod-decide', $req), ['decision' => 'accept', 'comment' => 'We have space.'])->assertRedirect();
        $this->assertSame('new_hod_approved', $req->fresh()->status);

        // AS relays to the CURRENT department HOD.
        $this->actingAs($as)->post(route('change-of-course.relay-current-hod', $req))->assertRedirect();
        $this->assertSame('current_hod_review', $req->fresh()->status);

        // Current HOD clears the transfer.
        $this->actingAs($this->currentHod)->post(route('change-of-course.hod-decide', $req), ['decision' => 'accept', 'comment' => 'No objection.'])->assertRedirect();
        $this->assertSame('current_hod_approved', $req->fresh()->status);

        // AS forwards to the Registrar; Registrar approves.
        $this->actingAs($as)->post(route('change-of-course.forward-registrar', $req))->assertRedirect();
        $this->assertSame('registrar_review', $req->fresh()->status);

        $this->actingAs($this->userWithRole('registrar'))
            ->post(route('change-of-course.decide', $req), ['decision' => 'approve'])->assertRedirect();
        $this->assertSame('approved', $req->fresh()->status);

        // Not migrated yet — that happens only after the new registration fee is paid.
        $this->assertSame($this->current->id, $this->student->fresh()->program_id);

        // Acceptance letter is now available to the student.
        $this->actingAs($this->studentUser)->get(route('change-of-course.letter', $req))->assertOk();

        // Student pays the new-course registration fee → migrated + completed.
        $this->actingAs($this->studentUser)->get(route('change-of-course.pay-new-fee', $req))->assertRedirect();
        $newInvoice = Invoice::findOrFail($req->fresh()->new_registration_invoice_id);
        $this->actingAs($this->studentUser)->get(route('payments.sandbox', $newInvoice))->assertRedirect();

        $req = $req->fresh();
        $this->assertSame('completed', $req->status);
        $this->assertNotNull($req->migrated_at);
        $this->assertSame($this->target->id, $this->student->fresh()->program_id);
        $this->assertSame($this->newDept->id, $this->student->fresh()->department_id);
    }

    public function test_new_hod_rejection_closes_the_application_to_the_student(): void
    {
        $req = $this->applyAndPay();
        $as  = $this->userWithRole('academic_secretary');

        $this->actingAs($as)->post(route('change-of-course.forward-new-hod', $req), ['comment' => 'Review please.'])->assertRedirect();
        $this->actingAs($this->newHod)->post(route('change-of-course.hod-decide', $req), ['decision' => 'reject', 'comment' => 'Programme is full.'])->assertRedirect();
        $this->assertSame('new_hod_rejected', $req->fresh()->status);

        // AS rejects to the student citing the HOD reason.
        $this->actingAs($as)->post(route('change-of-course.reject-student', $req))->assertRedirect();
        $req = $req->fresh();
        $this->assertSame('rejected', $req->status);
        $this->assertSame('Programme is full.', $req->rejection_reason);
        $this->assertSame($this->current->id, $this->student->fresh()->program_id); // unchanged
    }

    public function test_hod_only_sees_and_acts_on_their_own_department(): void
    {
        $req = $this->applyAndPay();
        $as  = $this->userWithRole('academic_secretary');
        $this->actingAs($as)->post(route('change-of-course.forward-new-hod', $req), ['comment' => 'go'])->assertRedirect();

        // The CURRENT dept HOD must not be able to decide a NEW-dept review.
        $this->actingAs($this->currentHod)->post(route('change-of-course.hod-decide', $req), ['decision' => 'accept', 'comment' => 'x'])->assertForbidden();
    }

    public function test_role_gating(): void
    {
        $this->actingAs($this->userWithRole('lecturer'))->get('/change-of-course')->assertForbidden();
        $this->actingAs($this->studentUser)->get('/change-of-course/review')->assertForbidden();
        $this->actingAs($this->studentUser)->get('/change-of-course/approvals')->assertForbidden();
        $this->actingAs($this->studentUser)->get('/change-of-course/hod')->assertForbidden();
    }
}
