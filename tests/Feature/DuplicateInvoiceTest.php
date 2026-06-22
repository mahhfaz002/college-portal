<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

/**
 * A paid fee must never leave a duplicate pending invoice behind (the stray
 * "Pay Now" the bursar/applicant saw).
 */
class DuplicateInvoiceTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bootCollege(['domain' => 'localhost']);
    }

    public function test_marking_paid_cancels_duplicate_pending_siblings(): void
    {
        $make = fn () => Invoice::create([
            'college_id' => $this->college->id, 'applicant_id' => 77, 'purpose' => 'acceptance_fee',
            'description' => 'Acceptance', 'amount' => 2000, 'payer_email' => 'a@gmail.com',
            'status' => 'pending', 'reference' => PaystackService::reference('ACC', $this->college->id),
        ]);
        $first  = $make();
        $second = $make(); // the accidental duplicate

        app(PaystackService::class)->markPaid($first, 'REF1', 'sandbox');

        $this->assertSame('paid', $first->fresh()->status);
        $this->assertSame('cancelled', $second->fresh()->status, 'Duplicate pending sibling should be cancelled');
    }

    public function test_cancelled_invoices_are_hidden_from_a_different_payer(): void
    {
        // Cancelling must be scoped to the SAME payer — never touch another applicant's fee.
        $mine = Invoice::create([
            'college_id' => $this->college->id, 'applicant_id' => 1, 'purpose' => 'acceptance_fee',
            'description' => 'x', 'amount' => 2000, 'payer_email' => 'm@gmail.com', 'status' => 'pending',
            'reference' => PaystackService::reference('ACC', $this->college->id),
        ]);
        $other = Invoice::create([
            'college_id' => $this->college->id, 'applicant_id' => 2, 'purpose' => 'acceptance_fee',
            'description' => 'x', 'amount' => 2000, 'payer_email' => 'o@gmail.com', 'status' => 'pending',
            'reference' => PaystackService::reference('ACC', $this->college->id),
        ]);

        app(PaystackService::class)->markPaid($mine, 'REF', 'sandbox');

        $this->assertSame('pending', $other->fresh()->status, "Another applicant's fee must be untouched");
    }
}
