<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Department;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

/**
 * Apply form Section C: each subject carries its own grade, exam body
 * (WAEC/NECO/NABTEB), exam year and examination number — captured into
 * olevel_results.
 */
class ApplyResultsTableTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    public function test_application_stores_per_subject_exam_body_year_and_number(): void
    {
        $this->seed();
        $this->bootCollege(['domain' => 'localhost']);
        $dept = Department::create(['name' => 'Health', 'acronym' => 'HS', 'section' => 'UG']);
        $program = Program::create(['name' => 'Nursing', 'acronym' => 'NUR', 'department_id' => $dept->id, 'application_fee' => 1000]);

        $subjects = ['English Language', 'Mathematics', 'Physics', 'Chemistry', 'Biology'];
        $results = [];
        foreach ($subjects as $i => $s) {
            $results[$i] = [
                'subject' => $s, 'grade' => 'B2',
                'exam_type' => $i === 0 ? 'NABTEB' : 'WAEC',   // combined sittings
                'exam_year' => '2024', 'exam_number' => 'EX'.$i.'00'.$i,
            ];
        }

        $this->post('/apply', [
            'college_id' => $this->college->id,
            'first_name' => 'Aisha', 'surname' => 'Bello', 'address' => '1 Rd', 'phone' => '080',
            'email' => 'aisha.apply@gmail.com', 'date_of_birth' => '2005-01-01', 'gender' => 'Female',
            'first_choice_program_id' => $program->id,
            'guardian_name' => 'Bello', 'guardian_relationship' => 'Father', 'guardian_phone' => '081',
            'results' => $results,
            'passport' => UploadedFile::fake()->create('p.jpg', 80, 'image/jpeg'),
        ])->assertRedirect(); // → application-fee checkout

        $applicant = Applicant::withoutGlobalScopes()->where('email', 'aisha.apply@gmail.com')->firstOrFail();
        $stored = collect($applicant->olevel_results);

        $this->assertCount(5, $stored);
        // Subjects are stored title-cased now (first letter of each word), not ALL CAPS.
        $english = $stored->firstWhere('subject', 'English Language');
        $this->assertSame('NABTEB', $english['exam_type']);
        $this->assertSame('2024', (string) $english['exam_year']);
        $this->assertSame('EX0000', $english['exam_number']);
        // Headline columns fall back to the first graded row.
        $this->assertSame('NABTEB', $applicant->exam_type);
    }

    /**
     * Re-applying with an email that already has an account is rejected with a
     * clear, named error (the silent "back to the form" the live site showed).
     */
    public function test_duplicate_email_is_rejected_with_a_clear_error(): void
    {
        $this->seed();
        $this->bootCollege(['domain' => 'localhost']);
        $dept = Department::create(['name' => 'Health', 'acronym' => 'HS', 'section' => 'UG']);
        $program = Program::create(['name' => 'Nursing', 'acronym' => 'NUR', 'department_id' => $dept->id, 'application_fee' => 1000]);

        // An account already exists with this email (e.g. a prior paid applicant).
        \App\Models\User::factory()->role('applicant')->create(['email' => 'taken@gmail.com']);

        $results = [];
        foreach (['English Language', 'Mathematics', 'Physics', 'Chemistry', 'Biology'] as $i => $s) {
            $results[$i] = ['subject' => $s, 'grade' => 'B2', 'exam_type' => 'WAEC', 'exam_year' => '2024', 'exam_number' => 'EX'.$i];
        }

        $response = $this->from('/apply')->post('/apply', [
            'college_id' => $this->college->id,
            'first_name' => 'Aisha', 'surname' => 'Bello', 'address' => '1 Rd', 'phone' => '080',
            'email' => 'taken@gmail.com', 'date_of_birth' => '2005-01-01', 'gender' => 'Female',
            'first_choice_program_id' => $program->id,
            'guardian_name' => 'Bello', 'guardian_relationship' => 'Father', 'guardian_phone' => '081',
            'results' => $results,
        ]);

        // Bounced back to the form (302) with a named email error — not a 500,
        // not a silent reload, and no applicant/invoice created.
        $response->assertRedirect('/apply');
        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('applicants', ['email' => 'taken@gmail.com']);
    }
}
