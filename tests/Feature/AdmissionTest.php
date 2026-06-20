<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

class AdmissionTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bootCollege();
    }

    private function registrar(): User
    {
        return $this->userWithRole('registrar');
    }

    private function applicant(array $attrs = []): Applicant
    {
        return Applicant::create(array_merge([
            'full_name' => 'New Applicant', 'date_of_birth' => '2004-01-01', 'gender' => 'Male',
            'parent_name' => 'Parent', 'parent_phone' => '080', 'parent_email' => 'np@example.test',
            'desired_class' => 'UG1', 'status' => 'pending',
            'passport' => 'data:image/png;base64,AAAA',
        ], $attrs));
    }

    public function test_registrar_can_view_admissions(): void
    {
        $this->actingAs($this->registrar())->get('/admin/admissions')->assertOk();
    }

    public function test_admitting_creates_student_with_unique_id_and_photo(): void
    {
        $applicant = $this->applicant();

        $this->actingAs($this->registrar())
            ->post("/admin/admissions/{$applicant->id}/approve")
            ->assertRedirect();

        $applicant->refresh();
        $this->assertSame('admitted', $applicant->status);
        $this->assertNotNull($applicant->admission_number);

        $student = Student::where('admission_number', $applicant->admission_number)->first();
        $this->assertNotNull($student);
        $this->assertSame('UG1', $student->class_arm);
        $this->assertSame($applicant->passport, $student->photo); // base64 carried to ID card
        $this->assertStringStartsWith(setting('admission_prefix').'/', $student->admission_number);
    }

    public function test_admission_numbers_are_unique(): void
    {
        $a1 = $this->applicant();
        $a2 = $this->applicant(['full_name' => 'Second Applicant', 'parent_email' => 'p2@example.test']);

        $reg = $this->registrar();
        $this->actingAs($reg)->post("/admin/admissions/{$a1->id}/approve");
        $this->actingAs($reg)->post("/admin/admissions/{$a2->id}/approve");

        $this->assertNotSame($a1->fresh()->admission_number, $a2->fresh()->admission_number);
        $this->assertSame(2, Student::whereIn('admission_number', [
            $a1->fresh()->admission_number, $a2->fresh()->admission_number,
        ])->count());
    }

    public function test_lecturer_cannot_admit(): void
    {
        $applicant = $this->applicant();
        $this->actingAs($this->userWithRole('lecturer'))
            ->post("/admin/admissions/{$applicant->id}/approve")->assertForbidden();
    }

    public function test_proprietor_can_view_but_not_admit(): void
    {
        $applicant = $this->applicant();
        $proprietor = $this->userWithRole('proprietor');

        $this->actingAs($proprietor)->get('/admin/admissions')->assertOk();
        $this->actingAs($proprietor)->post("/admin/admissions/{$applicant->id}/approve")->assertForbidden();
    }
}
