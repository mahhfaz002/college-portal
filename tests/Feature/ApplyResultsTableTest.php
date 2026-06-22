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
        $english = $stored->firstWhere('subject', 'ENGLISH LANGUAGE');
        $this->assertSame('NABTEB', $english['exam_type']);
        $this->assertSame('2024', (string) $english['exam_year']);
        $this->assertSame('EX0000', $english['exam_number']);
        // Headline columns fall back to the first graded row.
        $this->assertSame('NABTEB', $applicant->exam_type);
    }
}
