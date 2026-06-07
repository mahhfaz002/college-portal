<?php

namespace Tests\Feature;

use App\Models\FeeBill;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeBillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function bursar(): User
    {
        return User::where('role', 'accountant')->firstOrFail();
    }

    public function test_bursar_can_bill_a_student_and_balance_grows(): void
    {
        $student = Student::first();
        $before = (float) $student->fees_balance;

        $this->actingAs($this->bursar())->post('/fees/student', [
            'student_id' => $student->id,
            'title' => 'First Term Tuition',
            'amount' => 30000,
        ])->assertRedirect();

        $this->assertDatabaseHas('fee_bills', ['student_id' => $student->id, 'title' => 'First Term Tuition']);
        $this->assertEquals($before + 30000, (float) $student->fresh()->fees_balance);
    }

    public function test_class_wide_fee_bills_every_student_in_class(): void
    {
        $count = Student::where('class_arm', 'JSS1A')->count();

        $this->actingAs($this->bursar())->post('/fees/class', [
            'title' => 'WAEC Fee',
            'amount' => 15000,
            'classes' => ['JSS1A'],
        ])->assertRedirect();

        $this->assertSame($count, FeeBill::where('title', 'WAEC Fee')->count());
    }

    public function test_payment_clears_bill_and_generates_receipt(): void
    {
        $student = Student::first();
        $bill = FeeBill::create([
            'student_id' => $student->id, 'title' => 'Tuition',
            'amount' => 20000, 'amount_paid' => 0, 'status' => 'unpaid',
        ]);
        $student->increment('fees_balance', 20000);

        $resp = $this->actingAs($this->bursar())->post("/students/{$student->id}/pay", [
            'amount' => 20000,
            'payment_method' => 'Cash',
            'fee_bill_id' => $bill->id,
        ]);

        // Redirects to the auto-generated receipt.
        $resp->assertRedirect();
        $this->assertStringContainsString('/receipt', $resp->headers->get('Location'));

        $bill->refresh();
        $this->assertSame('paid', $bill->status);
        $this->assertEquals(0.0, (float) $bill->balance);
    }

    public function test_proprietor_can_view_fees_but_not_bill(): void
    {
        $p = User::where('role', 'proprietor')->firstOrFail();
        $this->actingAs($p)->get('/fees')->assertOk();
        $this->actingAs($p)->post('/fees/student', [
            'student_id' => Student::first()->id, 'title' => 'X', 'amount' => 100,
        ])->assertForbidden();
    }

    public function test_teacher_cannot_access_fees(): void
    {
        $t = User::where('role', 'teacher')->firstOrFail();
        $this->actingAs($t)->get('/fees')->assertForbidden();
    }
}
