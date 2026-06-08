<?php

namespace Tests\Feature;

use App\Models\Payslip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayslipWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private string $month = '2026-06';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function as(string $role): User
    {
        return User::where('role', $role)->firstOrFail();
    }

    private function staff(): User
    {
        return User::where('role', 'teacher')->firstOrFail();
    }

    private function createSlip(): Payslip
    {
        $this->actingAs($this->as('accountant'))->post('/payroll/'.$this->staff()->id, [
            'month' => $this->month,
            'basic_salary' => 100000,
            'allowances' => 20000,
            'tax' => 5000,
            'deduction_nature' => ['Pension', 'Loan'],
            'deduction_amount' => [8000, 2000],
        ])->assertRedirect();

        return Payslip::where('user_id', $this->staff()->id)->where('month', $this->month)->firstOrFail();
    }

    public function test_full_payslip_lifecycle(): void
    {
        // Bursar creates -> net = 100000 + 20000 - 10000 - 5000 = 105000
        $slip = $this->createSlip();
        $this->assertSame('draft', $slip->status);
        $this->assertEquals(105000, (float) $slip->net_salary);

        // Submit -> submitted
        $this->actingAs($this->as('accountant'))->post('/payroll-submit', ['month' => $this->month])->assertRedirect();
        $this->assertSame('submitted', $slip->fresh()->status);

        // Principal flags with a comment -> flagged
        $this->actingAs($this->as('principal'))->post("/payroll/{$slip->id}/flag", [
            'flag_comment' => 'Pension figure looks wrong.',
        ])->assertRedirect();
        $this->assertSame('flagged', $slip->fresh()->status);
        $this->assertSame('Pension figure looks wrong.', $slip->fresh()->flag_comment);

        // Bursar corrects (flagged is editable) & resubmits
        $this->createSlip(); // overwrites -> draft, clears comment
        $this->assertSame('draft', $slip->fresh()->status);
        $this->actingAs($this->as('accountant'))->post('/payroll-submit', ['month' => $this->month])->assertRedirect();

        // Principal approves
        $this->actingAs($this->as('principal'))->post("/payroll/{$slip->id}/approve")->assertRedirect();
        $this->assertSame('approved', $slip->fresh()->status);

        // Bursar initiates payment -> paid
        $this->actingAs($this->as('accountant'))->post("/payroll/{$slip->id}/pay")->assertRedirect();
        $this->assertSame('paid', $slip->fresh()->status);
        $this->assertNotNull($slip->fresh()->paid_at);
    }

    public function test_principal_cannot_edit_amounts(): void
    {
        $this->actingAs($this->as('principal'))->post('/payroll/'.$this->staff()->id, [
            'month' => $this->month, 'basic_salary' => 999,
        ])->assertForbidden();

        // ... and cannot access the bursar HR hub.
        $this->actingAs($this->as('principal'))->get('/payroll')->assertForbidden();
    }

    public function test_bursar_cannot_approve(): void
    {
        $slip = $this->createSlip();
        $this->actingAs($this->as('accountant'))->post("/payroll/{$slip->id}/approve")->assertForbidden();
        $this->actingAs($this->as('accountant'))->get('/payroll-review')->assertForbidden();
    }

    public function test_only_approved_can_be_paid(): void
    {
        $slip = $this->createSlip(); // draft
        $this->actingAs($this->as('accountant'))->post("/payroll/{$slip->id}/pay")->assertForbidden();
    }

    public function test_ict_has_no_payroll_access(): void
    {
        $this->actingAs($this->as('ict'))->get('/payroll')->assertForbidden();
        $this->actingAs($this->as('ict'))->get('/payroll-review')->assertForbidden();
    }
}
