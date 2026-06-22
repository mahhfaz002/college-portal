<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Exam;
use App\Models\Program;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

/**
 * Covers the exam lifecycle that survives in the college platform:
 * officer creates → (questions) → release → lecturer grades → officer approves,
 * plus eligibility overrides and access control. The removed online
 * exam-taking flow (student unlock/submit/auto-grade, student-raised result
 * queries) is intentionally not covered. Question authoring via the UI is now
 * gated behind an active Exam Mode cycle, so the lifecycle test seeds a question
 * directly as a fixture.
 */
class ExamWorkflowTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    private User $officer;
    private User $lecturer;
    private Subject $course;
    private Student $student;
    private User $studentUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bootCollege();

        $department = Department::create(['name' => 'Health Sciences', 'acronym' => 'HS', 'section' => 'UG']);
        $program = Program::create(['name' => 'Nursing', 'acronym' => 'NUR', 'department_id' => $department->id]);

        $this->officer = $this->userWithRole('exam_officer');
        $this->lecturer = $this->userWithRole('lecturer');

        $this->course = Subject::create([
            'name' => 'Anatomy', 'course_code' => 'NUR101',
            'department_id' => $department->id, 'program_id' => $program->id, 'level' => '100',
        ]);
        $this->course->teachers()->attach($this->lecturer->id);

        $this->student = $this->studentRecord([
            'class_arm' => 'UG1', 'program_id' => $program->id, 'level' => '100',
            'email' => 'pupil@test.local', 'fees_balance' => 0,
        ]);
        $this->studentUser = $this->userWithRole('student', ['email' => 'pupil@test.local']);
    }

    private function createExam(string $title = 'First Semester Anatomy'): Exam
    {
        $this->actingAs($this->officer)->post('/exams', [
            'subject_id' => $this->course->id,
            'title' => $title,
            'duration_minutes' => 30,
            'level' => '100',
        ])->assertRedirect();

        return Exam::latest('id')->firstOrFail();
    }

    public function test_exam_lifecycle_release_grade_approve(): void
    {
        $exam = $this->createExam();

        // A question is required before release. (UI authoring is Exam-Mode gated;
        // seed one directly to exercise the officer/grading lifecycle.)
        $exam->questions()->create([
            'question_text' => '2 + 2 = ?', 'option_a' => '4', 'option_b' => '5',
            'correct_option' => 'a', 'marks' => 5, 'type' => 'objective', 'created_by' => $this->lecturer->id,
        ]);

        // Officer releases.
        $this->actingAs($this->officer)->post("/exams/{$exam->id}/release", [
            'access_password' => 'open123',
        ])->assertRedirect();
        $this->assertSame('released', $exam->fresh()->status);

        // Course lecturer grades (CA 30 + exam 5 = 35) -> forwarded to officer.
        $this->actingAs($this->lecturer)->post("/exams/{$exam->id}/grade", [
            'ca' => [$this->student->id => 30],
            'exam' => [$this->student->id => 5],
        ])->assertRedirect();

        $score = Score::where('exam_id', $exam->id)->where('student_id', $this->student->id)->firstOrFail();
        $this->assertSame('submitted', $score->status);
        $this->assertSame(35, (int) $score->total);

        // Officer approves -> published.
        $this->actingAs($this->officer)->post("/exams/{$exam->id}/approve")->assertRedirect();
        $this->assertSame('published', $score->fresh()->status);
    }

    public function test_exam_cannot_release_without_questions(): void
    {
        $exam = $this->createExam('Empty');

        $this->actingAs($this->officer)->post("/exams/{$exam->id}/release", ['access_password' => 'pw12'])
            ->assertRedirect();
        $this->assertSame('draft', $exam->fresh()->status); // blocked, still draft
    }

    public function test_ineligible_student_can_be_admitted_by_officer(): void
    {
        $exam = $this->createExam('Eligibility');

        // A fees debtor in the same class arm is ineligible by default.
        $debtor = $this->studentRecord(['class_arm' => 'UG1', 'fees_balance' => 50000]);
        $this->assertFalse($debtor->feesCleared());

        $this->actingAs($this->officer)->post("/exams/{$exam->id}/eligibility/{$debtor->id}", [
            'status' => 'eligible', 'reason' => 'Board resolution',
        ])->assertRedirect();

        $this->assertDatabaseHas('exam_eligibilities', [
            'exam_id' => $exam->id, 'student_id' => $debtor->id, 'status' => 'eligible',
        ]);
    }

    public function test_access_control(): void
    {
        // Lecturer is not an exam-admin role.
        $this->actingAs($this->lecturer)->get('/exams')->assertForbidden();
        $this->actingAs($this->lecturer)->get('/exams/create')->assertForbidden();

        // Students cannot reach the officer exam list.
        $this->actingAs($this->studentUser)->get('/exams')->assertForbidden();

        // The exam officer owns the lifecycle.
        $this->actingAs($this->officer)->get('/exams')->assertOk();
        $this->actingAs($this->officer)->get('/exams/create')->assertOk();
    }
}
