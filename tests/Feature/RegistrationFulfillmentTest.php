<?php

namespace Tests\Feature;

use App\Http\Controllers\GatewayPaymentController;
use App\Models\Applicant;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

/**
 * Paying the registration fee must create the Student (with a registration
 * number), promote the applicant account to a student, and mark the applicant
 * registered — the flow that was 500-ing on the callback because the student's
 * `photo` column overflowed with the base64 passport on MySQL.
 */
class RegistrationFulfillmentTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    public function test_paying_registration_fee_creates_student_and_promotes_account(): void
    {
        $this->seed();
        $this->bootCollege(['domain' => 'localhost']);

        $dept = Department::create(['name' => 'Science Lab Tech', 'acronym' => 'SLT', 'section' => 'UG']);
        $program = Program::create([
            'name' => 'ND Science Lab Tech', 'acronym' => 'SLT', 'department_id' => $dept->id,
        ]);

        // An applicant who has an account (from the application fee) and an offer.
        $user = User::factory()->role('applicant')->create(['email' => 'reg.applicant@gmail.com']);
        $bigPhoto = 'data:image/jpeg;base64,'.base64_encode(random_bytes(120_000)); // ~160KB — overflows VARCHAR(255)

        $applicant = Applicant::create([
            'college_id' => $this->college->id, 'user_id' => $user->id,
            'first_name' => 'Ismail', 'surname' => 'Nazir', 'full_name' => 'Nazir Ismail',
            'date_of_birth' => '2004-03-02', 'gender' => 'Male',
            'parent_name' => 'Nazir Sr', 'parent_phone' => '0800', 'parent_email' => 'p@gmail.com',
            'desired_class' => $program->name, 'phone' => '0801', 'address' => 'x',
            'email' => 'reg.applicant@gmail.com',
            'first_choice_program_id' => $program->id, 'admitted_program_id' => $program->id,
            'application_status' => 'accepted', 'payment_status' => 'paid', 'status' => 'pending',
            'passport' => $bigPhoto,
        ]);

        $invoice = Invoice::create([
            'college_id' => $this->college->id, 'applicant_id' => $applicant->id, 'user_id' => $user->id,
            'program_id' => $program->id, 'purpose' => 'registration_fee', 'description' => 'Reg fee',
            'amount' => 3000, 'payer_email' => $applicant->email, 'status' => 'pending',
            'reference' => PaystackService::reference('REG', $this->college->id),
        ]);

        // Same path the Paystack callback/webhook runs.
        app(GatewayPaymentController::class)
            ->processWebhookEvent('charge.success', ['reference' => $invoice->reference], $invoice);

        $student = Student::withoutGlobalScopes()->where('email', $applicant->email)->first();
        $this->assertNotNull($student, 'Student should be created on registration payment');
        $this->assertNotEmpty($student->registration_number);
        $this->assertSame($program->id, $student->program_id);
        $this->assertSame('student', $user->fresh()->role);
        $this->assertSame('registered', $applicant->fresh()->application_status);
    }
}
