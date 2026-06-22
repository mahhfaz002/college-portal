<?php

namespace Tests\Feature;

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
 * The backfill promotes applicants who paid the registration fee but were left
 * as applicants by the old callback 500 — and leaves everyone else alone.
 */
class PromotePaidRegistrationsTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    private Program $program;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bootCollege(['domain' => 'localhost']);
        $dept = Department::create(['name' => 'Health', 'acronym' => 'HS', 'section' => 'UG']);
        $this->program = Program::create(['name' => 'Nursing', 'acronym' => 'NUR', 'department_id' => $dept->id]);
    }

    private function paidApplicant(string $email): Applicant
    {
        $user = User::factory()->role('applicant')->create(['email' => $email]);
        $applicant = Applicant::create([
            'college_id' => $this->college->id, 'user_id' => $user->id,
            'first_name' => 'A', 'surname' => 'B', 'full_name' => 'B A',
            'date_of_birth' => '2005-01-01', 'gender' => 'Male',
            'parent_name' => 'P', 'parent_phone' => '080', 'parent_email' => 'p@gmail.com',
            'desired_class' => $this->program->name, 'phone' => '081', 'address' => 'x', 'email' => $email,
            'first_choice_program_id' => $this->program->id, 'admitted_program_id' => $this->program->id,
            'application_status' => 'accepted', 'payment_status' => 'paid', 'status' => 'pending',
            'passport' => 'data:image/jpeg;base64,'.base64_encode(random_bytes(90_000)),
        ]);
        Invoice::create([
            'college_id' => $this->college->id, 'applicant_id' => $applicant->id, 'user_id' => $user->id,
            'program_id' => $this->program->id, 'purpose' => 'registration_fee', 'description' => 'Reg',
            'amount' => 3000, 'payer_email' => $email, 'status' => 'paid',
            'reference' => PaystackService::reference('REG', $this->college->id), 'paid_at' => now(),
        ]);

        return $applicant;
    }

    public function test_backfill_promotes_stranded_paid_applicants(): void
    {
        $stranded = $this->paidApplicant('stranded@gmail.com');

        $this->artisan('students:promote-paid-registrations')->assertExitCode(0);

        $student = Student::withoutGlobalScopes()->where('email', 'stranded@gmail.com')->first();
        $this->assertNotNull($student);
        $this->assertNotEmpty($student->registration_number);
        $this->assertSame('student', $stranded->user->fresh()->role);
        $this->assertSame('registered', $stranded->fresh()->application_status);
    }

    public function test_backfill_is_idempotent_and_skips_unpaid(): void
    {
        $this->paidApplicant('paid@gmail.com');
        // An applicant who has NOT paid the registration fee — must stay an applicant.
        $unpaidUser = User::factory()->role('applicant')->create(['email' => 'unpaid@gmail.com']);
        Applicant::create([
            'college_id' => $this->college->id, 'user_id' => $unpaidUser->id,
            'first_name' => 'U', 'surname' => 'P', 'full_name' => 'P U', 'date_of_birth' => '2005-01-01',
            'gender' => 'Male', 'parent_name' => 'P', 'parent_phone' => '080', 'parent_email' => 'p2@gmail.com',
            'desired_class' => $this->program->name, 'phone' => '082', 'address' => 'x', 'email' => 'unpaid@gmail.com',
            'first_choice_program_id' => $this->program->id, 'admitted_program_id' => $this->program->id,
            'application_status' => 'admitted', 'payment_status' => 'unpaid', 'status' => 'pending',
        ]);

        $this->artisan('students:promote-paid-registrations')->assertExitCode(0);
        $this->artisan('students:promote-paid-registrations')->assertExitCode(0); // re-run: idempotent

        $this->assertSame(1, Student::withoutGlobalScopes()->count(), 'Only the paid applicant becomes a student, once');
        $this->assertSame('applicant', $unpaidUser->fresh()->role);
    }
}
