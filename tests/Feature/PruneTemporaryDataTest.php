<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\ChangeOfCourseRequest;
use App\Models\Invoice;
use App\Models\Program;
use App\Models\Department;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

class PruneTemporaryDataTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    private function applicant(array $attrs, ?string $createdAt = null): Applicant
    {
        $a = Applicant::create(array_merge([
            'college_id' => $this->college->id,
            'full_name' => 'Temp Applicant', 'first_name' => 'Temp', 'surname' => 'Applicant',
            'email' => 'a@example.test', 'phone' => '080',
            'date_of_birth' => '2004-01-01', 'gender' => 'Male',
            'parent_name' => 'P', 'parent_phone' => '081', 'parent_email' => 'p@example.test',
            'desired_class' => 'Nursing', 'status' => 'pending', 'payment_status' => 'unpaid',
            'application_status' => 'pending_payment',
        ], $attrs));
        if ($createdAt) {
            Applicant::withoutGlobalScopes()->where('id', $a->id)->update(['created_at' => $createdAt]);
        }
        return $a->fresh();
    }

    public function test_clears_only_stale_unpaid_temporary_data(): void
    {
        $this->seed();
        $this->bootCollege();
        $old = now()->subHours(72);

        // Stale unpaid application (3 days old, no payment) → removed.
        $stale = $this->applicant(['email' => 'stale@gmail.com'], $old);
        // Fresh unpaid application (just now) → kept.
        $fresh = $this->applicant(['email' => 'fresh@gmail.com']);
        // Old but PAID application → kept.
        $paid  = $this->applicant(['email' => 'paid@gmail.com', 'payment_status' => 'paid'], $old);

        // Old cancelled invoice → removed; recent cancelled invoice → kept.
        $oldCancelled = Invoice::create(['college_id' => $this->college->id, 'purpose' => 'application_fee', 'description' => 'App fee', 'amount' => 1000, 'status' => 'cancelled', 'reference' => PaystackService::reference('X', $this->college->id)]);
        Invoice::withoutGlobalScopes()->where('id', $oldCancelled->id)->update(['updated_at' => $old]);
        $newCancelled = Invoice::create(['college_id' => $this->college->id, 'purpose' => 'application_fee', 'description' => 'App fee', 'amount' => 1000, 'status' => 'cancelled', 'reference' => PaystackService::reference('Y', $this->college->id)]);

        // An old PAID invoice must never be deleted.
        $paidInvoice = Invoice::create(['college_id' => $this->college->id, 'purpose' => 'registration_fee', 'description' => 'Reg fee', 'amount' => 5000, 'status' => 'paid', 'reference' => PaystackService::reference('Z', $this->college->id)]);
        Invoice::withoutGlobalScopes()->where('id', $paidInvoice->id)->update(['updated_at' => $old, 'created_at' => $old]);

        // Abandoned change-of-course request (pending payment, 3 days old) → removed with its invoice.
        $dept = Department::create(['name' => 'H', 'acronym' => 'H', 'section' => 'UG']);
        $prog = Program::create(['name' => 'N', 'acronym' => 'N', 'department_id' => $dept->id]);
        $student = $this->studentRecord(['program_id' => $prog->id, 'department_id' => $dept->id]);
        $cocInvoice = Invoice::create(['college_id' => $this->college->id, 'student_id' => $student->id, 'purpose' => 'change_of_course', 'description' => 'COC fee', 'amount' => 25000, 'status' => 'pending', 'reference' => PaystackService::reference('COC', $this->college->id)]);
        $coc = ChangeOfCourseRequest::create(['college_id' => $this->college->id, 'student_id' => $student->id, 'current_program_id' => $prog->id, 'requested_program_id' => $prog->id, 'reason' => 'x', 'status' => 'pending_payment', 'invoice_id' => $cocInvoice->id]);
        ChangeOfCourseRequest::withoutGlobalScopes()->where('id', $coc->id)->update(['created_at' => $old]);

        $this->artisan('data:prune-temporary')->assertSuccessful();

        // Removed:
        $this->assertDatabaseMissing('applicants', ['id' => $stale->id]);
        $this->assertDatabaseMissing('invoices', ['id' => $oldCancelled->id]);
        $this->assertDatabaseMissing('change_of_course_requests', ['id' => $coc->id]);
        $this->assertDatabaseMissing('invoices', ['id' => $cocInvoice->id]);

        // Preserved:
        $this->assertDatabaseHas('applicants', ['id' => $fresh->id]);
        $this->assertDatabaseHas('applicants', ['id' => $paid->id]);
        $this->assertDatabaseHas('invoices', ['id' => $newCancelled->id]);
        $this->assertDatabaseHas('invoices', ['id' => $paidInvoice->id]);
        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }

    public function test_dry_run_deletes_nothing(): void
    {
        $this->seed();
        $this->bootCollege();
        $stale = $this->applicant(['email' => 'stale2@gmail.com'], now()->subHours(72));

        $this->artisan('data:prune-temporary --dry-run')->assertSuccessful();

        $this->assertDatabaseHas('applicants', ['id' => $stale->id]);
    }
}
