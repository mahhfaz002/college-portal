<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Exam;
use App\Models\ExamSubmission;
use App\Models\ResultQuery;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $officer;
    private User $teacher;
    private Subject $maths;
    private Student $student;
    private User $studentUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->officer = User::where('role', 'exam_officer')->firstOrFail();
        $this->teacher = User::where('role', 'teacher')->firstOrFail(); // teaches Mathematics (seeded)
        $this->maths = Subject::where('name', 'Mathematics')->firstOrFail();

        // An eligible student: fees cleared + present today, with a login.
        $this->student = Student::create([
            'full_name' => 'Test Pupil', 'admission_number' => 'TST/2026/999',
            'class_arm' => 'JSS1A', 'parent_phone' => '080', 'fees_balance' => 0,
            'email' => 'pupil@test.local',
        ]);
        Attendance::create([
            'student_id' => $this->student->id,
            'attendance_date' => now()->toDateString(), 'status' => 'present',
        ]);
        $this->studentUser = User::create([
            'name' => 'Test Pupil', 'email' => 'pupil@test.local',
            'password' => bcrypt('password'), 'role' => 'student', 'must_change_password' => false,
        ]);
    }

    public function test_full_exam_lifecycle(): void
    {
        // 1. Officer creates the exam.
        $this->actingAs($this->officer)->post('/exams', [
            'subject_id' => $this->maths->id,
            'title' => 'First Term Maths',
            'duration_minutes' => 30,
            'class_arms' => ['JSS1A'],
        ])->assertRedirect();

        $exam = Exam::firstOrFail();

        // 2. Teacher authors a question.
        $this->actingAs($this->teacher)->post("/exams/{$exam->id}/questions", [
            'question_text' => '2 + 2 = ?', 'option_a' => '4', 'option_b' => '5',
            'correct_option' => 'a', 'marks' => 5,
        ])->assertRedirect();
        $question = $exam->questions()->firstOrFail();

        // 3. Officer releases with a password.
        $this->actingAs($this->officer)->post("/exams/{$exam->id}/release", [
            'access_password' => 'open123',
        ])->assertRedirect();
        $this->assertSame('released', $exam->fresh()->status);

        // 4. Student unlock: wrong password fails, right one works.
        $this->actingAs($this->studentUser)
            ->post("/my-exams/{$exam->id}/unlock", ['access_password' => 'nope'])
            ->assertRedirect();
        $this->assertFalse(session()->get("exam_unlocked_{$exam->id}", false));

        // 5. Student submits (objective auto-grade = 5/5).
        $this->actingAs($this->studentUser)
            ->withSession(["exam_unlocked_{$exam->id}" => true])
            ->post("/my-exams/{$exam->id}/submit", ['answers' => [$question->id => 'a']])
            ->assertRedirect();

        $sub = ExamSubmission::where('exam_id', $exam->id)->where('student_id', $this->student->id)->firstOrFail();
        $this->assertSame(5, $sub->objective_score);

        // 6. Teacher grades (CA 30 + exam 5 = 35) -> forwarded.
        $this->actingAs($this->teacher)->post("/exams/{$exam->id}/grade", [
            'ca' => [$this->student->id => 30],
            'exam' => [$this->student->id => 5],
        ])->assertRedirect();

        $score = Score::where('exam_id', $exam->id)->where('student_id', $this->student->id)->firstOrFail();
        $this->assertSame('submitted', $score->status);
        $this->assertSame(35, (int) $score->total);

        // 7. Officer approves -> published.
        $this->actingAs($this->officer)->post("/exams/{$exam->id}/approve")->assertRedirect();
        $this->assertSame('published', $score->fresh()->status);

        // 8. Student raises a query.
        $this->actingAs($this->studentUser)->post("/results/{$score->id}/query", [
            'message' => 'Please recheck my CA.',
        ])->assertRedirect();
        $query = ResultQuery::firstOrFail();
        $this->assertSame('open', $query->status);

        // 9. Officer resolves (and amends the score).
        $this->actingAs($this->officer)->post("/exam-queries/{$query->id}/resolve", [
            'resolution' => 'Rechecked, CA corrected.',
            'ca_score' => 35, 'exam_score' => 5,
        ])->assertRedirect();
        $this->assertSame('resolved', $query->fresh()->status);
        $this->assertSame(40, (int) $score->fresh()->total);
    }

    public function test_exam_cannot_release_without_questions(): void
    {
        $this->actingAs($this->officer)->post('/exams', [
            'subject_id' => $this->maths->id, 'title' => 'Empty', 'duration_minutes' => 30, 'class_arms' => ['JSS1A'],
        ]);
        $exam = Exam::firstOrFail();

        $this->actingAs($this->officer)->post("/exams/{$exam->id}/release", ['access_password' => 'pw12'])
            ->assertRedirect();
        $this->assertSame('draft', $exam->fresh()->status); // blocked, still draft
    }

    public function test_ineligible_student_can_be_admitted_by_officer(): void
    {
        $this->actingAs($this->officer)->post('/exams', [
            'subject_id' => $this->maths->id, 'title' => 'X', 'duration_minutes' => 30, 'class_arms' => ['JSS1A'],
        ]);
        $exam = Exam::firstOrFail();

        // A debtor in JSS1A (seeded Fatima has 50,000 balance).
        $debtor = Student::where('class_arm', 'JSS1A')->where('fees_balance', '>', 0)->firstOrFail();
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
        // Teacher cannot create exams.
        $this->actingAs($this->teacher)->get('/exams/create')->assertForbidden();
        // Student cannot view the officer exam list.
        $this->actingAs($this->studentUser)->get('/exams')->assertForbidden();
        // Proprietor can view but not create.
        $prop = User::where('role', 'proprietor')->firstOrFail();
        $this->actingAs($prop)->get('/exams')->assertOk();
        $this->actingAs($prop)->get('/exams/create')->assertForbidden();
    }
}
