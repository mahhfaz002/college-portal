<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

class PruneStaleApplicantsTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    private function applicant(array $attrs): Applicant
    {
        return Applicant::create(array_merge([
            'college_id'   => $this->college->id,
            'full_name'    => 'Test Applicant',
            'first_name'   => 'Test', 'surname' => 'Applicant',
            'email'        => 'a@example.test',
            'phone'        => '080',
            'date_of_birth'=> '2004-01-01',
            'gender'       => 'Male',
            'parent_name'  => 'P', 'parent_phone' => '081', 'parent_email' => 'p@example.test',
            'desired_class'=> 'Nursing',
            'status'       => 'pending',
            'payment_status' => 'unpaid',
            'application_status' => 'pending_payment',
        ], $attrs));
    }

    public function test_prunes_stale_applicants_but_keeps_students_staff_and_paid(): void
    {
        $this->seed();
        $this->bootCollege();

        // A real full student (with a matching student login).
        $student = $this->studentRecord(['email' => 'realstudent@gmail.com']);
        $studentUser = $this->userWithRole('student', ['email' => 'realstudent@gmail.com']);
        // The student also has their original applicant row — must be preserved.
        $studentApplicant = $this->applicant(['email' => 'realstudent@gmail.com', 'payment_status' => 'paid']);

        // Staff + superadmin — never in scope.
        $registrar = $this->userWithRole('registrar', ['email' => 'registrar@college.test']);

        // A stale, unpaid applicant + its applicant login — SHOULD be removed.
        $stale     = $this->applicant(['email' => 'stale@gmail.com']);
        $staleUser = $this->userWithRole('applicant', ['email' => 'stale@gmail.com']);

        // A paid applicant who never became a student — preserved by default.
        $paid = $this->applicant(['email' => 'paid@gmail.com', 'payment_status' => 'paid']);

        $this->artisan('applicants:prune-stale --force')->assertSuccessful();

        // Removed:
        $this->assertDatabaseMissing('applicants', ['id' => $stale->id]);
        $this->assertDatabaseMissing('users', ['id' => $staleUser->id]);

        // Preserved:
        $this->assertDatabaseHas('applicants', ['id' => $studentApplicant->id]);
        $this->assertDatabaseHas('applicants', ['id' => $paid->id]); // paid kept by default
        $this->assertDatabaseHas('users', ['id' => $studentUser->id]);
        $this->assertDatabaseHas('users', ['id' => $registrar->id]);
        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }

    public function test_dry_run_deletes_nothing(): void
    {
        $this->seed();
        $this->bootCollege();

        $stale = $this->applicant(['email' => 'stale2@gmail.com']);

        $this->artisan('applicants:prune-stale')->assertSuccessful();

        $this->assertDatabaseHas('applicants', ['id' => $stale->id]); // untouched
    }
}
