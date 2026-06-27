<?php

namespace Tests\Feature;

use App\Models\Payslip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

class PayslipWorkflowTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    private string $month = '2026-06';
    private User $bursar;       // runs payroll (manage_payroll)
    private User $provost;      // reviews & forwards (review_payroll)
    private User $proprietor;   // final approval (approve_payroll)
    private User $staffMember;  // the payslip subject

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bootCollege();
        $this->bursar = $this->userWithRole('bursar');
        $this->provost = $this->userWithRole('provost');
        $this->proprietor = $this->userWithRole('proprietor');
        $this->staffMember = $this->userWithRole('lecturer');
    }

    private function createSlip(): Payslip
    {
        $this->actingAs($this->bursar)->post('/payroll/'.$this->staffMember->id, [
            'month' => $this->month,
            'basic_salary' => 100000,
            'allowances' => 20000,
            'tax' => 5000,
            'deduction_nature' => ['Pension', 'Loan'],
            'deduction_amount' => [8000, 2000],
        ])->assertRedirect();

        return Payslip::where('user_id', $this->staffMember->id)->where('month', $this->month)->firstOrFail();
    }

    public function test_full_payslip_lifecycle(): void
    {
        // Bursar creates -> net = 100000 + 20000 - 10000 - 5000 = 105000
        $slip = $this->createSlip();
        $this->assertSame('draft', $slip->status);
        $this->assertEquals(105000, (float) $slip->net_salary);

        // Submit -> provost_review
        $this->actingAs($this->bursar)->post('/payroll-submit', ['month' => $this->month])->assertRedirect();
        $this->assertSame('provost_review', $slip->fresh()->status);

        // Provost queries back to the Bursar -> queried
        $this->actingAs($this->provost)->post("/payroll/{$slip->id}/provost-query", [
            'comment' => 'Pension figure looks wrong.',
        ])->assertRedirect();
        $this->assertSame('queried', $slip->fresh()->status);
        $this->assertSame('Pension figure looks wrong.', $slip->fresh()->provost_comment);

        // Bursar corrects (queried is editable) & resubmits
        $this->createSlip();
        $this->assertSame('draft', $slip->fresh()->status);
        $this->actingAs($this->bursar)->post('/payroll-submit', ['month' => $this->month])->assertRedirect();

        // Provost forwards to Proprietor -> proprietor_review
        $this->actingAs($this->provost)->post("/payroll/{$slip->id}/forward")->assertRedirect();
        $this->assertSame('proprietor_review', $slip->fresh()->status);

        // Proprietor gives final approval -> approved
        $this->actingAs($this->proprietor)->post("/payroll/{$slip->id}/approve")->assertRedirect();
        $this->assertSame('approved', $slip->fresh()->status);

        // Bursar initiates payment -> paid
        $this->actingAs($this->bursar)->post("/payroll/{$slip->id}/pay")->assertRedirect();
        $this->assertSame('paid', $slip->fresh()->status);
        $this->assertNotNull($slip->fresh()->paid_at);
    }

    public function test_reviewer_cannot_edit_amounts(): void
    {
        // The Provost reviewer can review but not author payslips (manage_payroll).
        $this->actingAs($this->provost)->post('/payroll/'.$this->staffMember->id, [
            'month' => $this->month, 'basic_salary' => 999,
        ])->assertForbidden();

        // ... and cannot access the bursar HR hub.
        $this->actingAs($this->provost)->get('/payroll')->assertForbidden();
    }

    public function test_bursar_cannot_approve(): void
    {
        $slip = $this->createSlip();
        $this->actingAs($this->bursar)->post("/payroll/{$slip->id}/forward")->assertForbidden();
        $this->actingAs($this->bursar)->get('/payroll-review')->assertForbidden();
    }

    public function test_only_approved_can_be_paid(): void
    {
        $slip = $this->createSlip(); // draft
        $this->actingAs($this->bursar)->post("/payroll/{$slip->id}/pay")->assertForbidden();
    }

    public function test_lecturer_has_no_payroll_access(): void
    {
        $lecturer = $this->userWithRole('lecturer');
        $this->actingAs($lecturer)->get('/payroll')->assertForbidden();
        $this->actingAs($lecturer)->get('/payroll-review')->assertForbidden();
    }
}
