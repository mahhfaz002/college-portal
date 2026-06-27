<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

/**
 * The student Fees page must surface every settled payment with a downloadable
 * receipt — and a student must never reach another student's receipt.
 */
class StudentFeesReceiptTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bootCollege();
    }

    private function paidInvoiceForStudent(int $studentId): Invoice
    {
        return Invoice::create([
            'college_id'  => $this->college->id,
            'student_id'  => $studentId,
            'purpose'     => 'registration_fee',
            'description' => 'Registration fee',
            'amount'      => 25000,
            'status'      => 'paid',
            'paid_at'     => now(),
            'payer_email' => 'payer@example.com',
            'reference'   => PaystackService::reference('REG', $this->college->id),
        ]);
    }

    public function test_paid_invoice_appears_in_history_and_owner_can_open_receipt(): void
    {
        $student = $this->studentRecord(['email' => 'owner@gmail.com']);
        $user    = $this->userWithRole('student', ['email' => 'owner@gmail.com']);
        $invoice = $this->paidInvoiceForStudent($student->id);

        // Fees page lists the payment with a receipt link.
        $this->actingAs($user)->get('/my/fees')
            ->assertOk()
            ->assertSee('Registration fee')
            ->assertSee(route('invoices.receipt', $invoice));

        // Owner can open the receipt itself.
        $this->actingAs($user)->get(route('invoices.receipt', $invoice))->assertOk();
    }

    public function test_a_student_cannot_open_another_students_receipt(): void
    {
        $owner   = $this->studentRecord(['email' => 'owner2@gmail.com']);
        $invoice = $this->paidInvoiceForStudent($owner->id);

        // A different student must be denied — no cross-student leakage.
        $this->studentRecord(['email' => 'intruder@gmail.com']);
        $intruder = $this->userWithRole('student', ['email' => 'intruder@gmail.com']);

        $this->actingAs($intruder)->get(route('invoices.receipt', $invoice))->assertForbidden();
    }
}
