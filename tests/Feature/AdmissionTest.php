<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function registrar(): User
    {
        return User::where('role', 'admin')->firstOrFail();
    }

    private function applicant(): Applicant
    {
        return Applicant::create([
            'full_name' => 'New Pupil', 'date_of_birth' => '2014-01-01', 'gender' => 'Male',
            'parent_name' => 'Parent', 'parent_phone' => '080', 'parent_email' => 'np@example.com',
            'desired_class' => 'JSS1A', 'status' => 'pending',
            'passport' => 'data:image/png;base64,AAAA',
        ]);
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
        $this->assertSame('JSS1A', $student->class_arm);
        $this->assertSame($applicant->passport, $student->photo); // base64 carried to ID card
        $this->assertStringStartsWith(setting('admission_prefix').'/', $student->admission_number);
    }

    public function test_admission_numbers_are_unique(): void
    {
        $a1 = $this->applicant();
        $a2 = Applicant::create([
            'full_name' => 'Second Pupil', 'date_of_birth' => '2014-02-02', 'gender' => 'Female',
            'parent_name' => 'P2', 'parent_phone' => '081', 'parent_email' => 'p2@example.com',
            'desired_class' => 'JSS1A', 'status' => 'pending',
        ]);

        $reg = $this->registrar();
        $this->actingAs($reg)->post("/admin/admissions/{$a1->id}/approve");
        $this->actingAs($reg)->post("/admin/admissions/{$a2->id}/approve");

        $this->assertNotSame($a1->fresh()->admission_number, $a2->fresh()->admission_number);
        $this->assertSame(2, Student::whereIn('admission_number', [
            $a1->fresh()->admission_number, $a2->fresh()->admission_number,
        ])->count());
    }

    public function test_teacher_cannot_admit(): void
    {
        $applicant = $this->applicant();
        $teacher = User::where('role', 'teacher')->firstOrFail();
        $this->actingAs($teacher)->post("/admin/admissions/{$applicant->id}/approve")->assertForbidden();
    }

    public function test_proprietor_can_view_but_not_admit(): void
    {
        $applicant = $this->applicant();
        $p = User::where('role', 'proprietor')->firstOrFail();
        $this->actingAs($p)->get('/admin/admissions')->assertOk();
        $this->actingAs($p)->post("/admin/admissions/{$applicant->id}/approve")->assertForbidden();
    }
}
