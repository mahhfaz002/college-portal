<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Invoice;
use App\Models\Subject;
use App\Models\User;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

class CourseAndInvoiceActionsTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bootCollege(['domain' => 'localhost']);
    }

    public function test_same_course_title_is_allowed_across_programmes(): void
    {
        Subject::create(['name' => 'Anatomy', 'course_code' => 'ANA101', 'course_unit' => 2,
            'program_id' => 1, 'level' => '100', 'semester' => 'First', 'college_id' => $this->college->id]);

        // A different programme using the same course title must NOT 500.
        Subject::create(['name' => 'Anatomy', 'course_code' => 'ANA201', 'course_unit' => 2,
            'program_id' => 2, 'level' => '100', 'semester' => 'First', 'college_id' => $this->college->id]);

        $this->assertSame(2, Subject::where('name', 'Anatomy')->count());
    }

    private function applicantInvoice(string $status = 'pending'): array
    {
        $user = User::factory()->role('applicant')->create();
        $applicant = Applicant::create([
            'college_id' => $this->college->id, 'user_id' => $user->id,
            'first_name' => 'A', 'surname' => 'B', 'full_name' => 'B A', 'email' => $user->email,
            'phone' => '080', 'address' => 'x', 'date_of_birth' => '2005-01-01', 'gender' => 'Male',
            'parent_name' => 'P', 'parent_phone' => '080', 'parent_email' => 'p@gmail.com', 'desired_class' => 'N',
            'application_status' => 'admitted', 'payment_status' => 'unpaid', 'status' => 'pending',
        ]);
        $invoice = Invoice::create([
            'college_id' => $this->college->id, 'applicant_id' => $applicant->id, 'user_id' => $user->id,
            'purpose' => 'acceptance_fee', 'description' => 'Acceptance', 'amount' => 2000,
            'payer_email' => $user->email, 'status' => $status,
            'reference' => PaystackService::reference('ACC', $this->college->id),
        ]);

        return [$user, $invoice];
    }

    public function test_owner_can_cancel_then_delete_an_unpaid_invoice(): void
    {
        [$user, $invoice] = $this->applicantInvoice();

        $this->actingAs($user)->post(route('invoices.cancel', $invoice))->assertRedirect();
        $this->assertSame('cancelled', $invoice->fresh()->status);

        $this->actingAs($user)->delete(route('invoices.destroy', $invoice))->assertRedirect();
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
    }

    public function test_a_paid_invoice_cannot_be_deleted(): void
    {
        [$user, $invoice] = $this->applicantInvoice('paid');

        $this->actingAs($user)->delete(route('invoices.destroy', $invoice))->assertStatus(422);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'paid']);
    }

    public function test_a_stranger_cannot_delete_someone_elses_invoice(): void
    {
        [, $invoice] = $this->applicantInvoice();
        $stranger = User::factory()->role('applicant')->create();

        $this->actingAs($stranger)->delete(route('invoices.destroy', $invoice))->assertForbidden();
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }
}
