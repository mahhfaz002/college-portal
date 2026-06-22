<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Program;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

/**
 * The student course form lists ONLY the live courses for that student's own
 * programme and level — never another programme's, department's, or level's.
 */
class StudentCourseFormTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    public function test_course_form_shows_only_the_students_program_and_level_courses(): void
    {
        $this->seed();
        $this->bootCollege(['domain' => 'localhost']);

        $deptA = Department::create(['name' => 'Pharmacy Tech', 'acronym' => 'PT', 'section' => 'UG']);
        $deptB = Department::create(['name' => 'Community Health', 'acronym' => 'CH', 'section' => 'UG']);
        $progA = Program::create(['name' => 'PharmTech', 'acronym' => 'PT', 'department_id' => $deptA->id]);
        $progB = Program::create(['name' => 'CHEW', 'acronym' => 'CH', 'department_id' => $deptB->id]);

        $mine = fn ($name, $code) => Subject::create([
            'name' => $name, 'course_code' => $code, 'course_unit' => 3, 'college_id' => $this->college->id,
            'department_id' => $deptA->id, 'program_id' => $progA->id, 'level' => '100', 'semester' => 'First Semester',
        ]);
        $mine('Pharmaceutics I', 'PT101');
        $mine('Anatomy', 'PT102');
        // Decoys that must NOT appear:
        Subject::create(['name' => 'Advanced Pharmacy', 'course_code' => 'PT201', 'course_unit' => 3, 'college_id' => $this->college->id,
            'department_id' => $deptA->id, 'program_id' => $progA->id, 'level' => '200', 'semester' => 'First Semester']); // diff level
        Subject::create(['name' => 'Community Health I', 'course_code' => 'CH101', 'course_unit' => 3, 'college_id' => $this->college->id,
            'department_id' => $deptB->id, 'program_id' => $progB->id, 'level' => '100', 'semester' => 'First Semester']); // diff dept

        $student = Student::create([
            'full_name' => 'Aisha Bello', 'email' => 'aisha.stu@gmail.com', 'admission_number' => 'ADM/1',
            'registration_number' => 'ALB/2026/PT/0001', 'class_arm' => 'PharmTech', 'parent_phone' => '080',
            'fees_balance' => 0, 'college_id' => $this->college->id, 'department_id' => $deptA->id,
            'program_id' => $progA->id, 'level' => '100', 'registration_status' => 'registration_paid',
        ]);
        $user = User::factory()->role('student')->create(['email' => 'aisha.stu@gmail.com']);

        $res = $this->actingAs($user)->get(route('student.course-form'))->assertOk();
        $res->assertSee('Pharmaceutics I')->assertSee('Anatomy')->assertSee('ALB/2026/PT/0001');
        $res->assertDontSee('Advanced Pharmacy');   // different level
        $res->assertDontSee('Community Health I');  // different department

        // Total units = 2 courses × 3.
        $res->assertSee('Total Credit Units: 6');

        // PDF route works.
        $pdf = $this->actingAs($user)->get(route('student.course-form.pdf'))->assertOk();
        $this->assertStringContainsString('application/pdf', $pdf->headers->get('content-type'));
    }
}
