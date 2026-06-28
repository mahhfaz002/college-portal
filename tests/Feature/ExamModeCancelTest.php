<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamCycle;
use App\Models\ExamQuestion;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

class ExamModeCancelTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    public function test_cancelling_exam_mode_clears_unsubmitted_draft_question_sets(): void
    {
        $this->seed();
        $this->bootCollege();

        $cycle = ExamCycle::create([
            'college_id' => $this->college->id, 'title' => 'First Semester',
            'exam_start_at' => now()->addDays(10), 'submission_deadline_at' => now()->addDays(5),
            'status' => 'active', 'created_by' => 1,
        ]);

        $subject = Subject::create(['name' => 'Anatomy', 'course_code' => 'AN101']);

        // An unsubmitted draft set the lecturer is still working on.
        $draft = Exam::create(['subject_id' => $subject->id, 'exam_cycle_id' => $cycle->id, 'title' => 'Anatomy', 'class_arms' => 'all', 'duration_minutes' => 30, 'status' => 'draft']);
        ExamQuestion::create(['exam_id' => $draft->id, 'question_text' => 'Q1', 'option_a' => '4', 'option_b' => '5', 'correct_option' => 'a', 'marks' => 5, 'type' => 'objective', 'created_by' => 1]);

        // A SUBMITTED set must survive (finished work).
        $submitted = Exam::create(['subject_id' => $subject->id, 'exam_cycle_id' => $cycle->id, 'title' => 'Anatomy 2', 'class_arms' => 'all', 'duration_minutes' => 30, 'status' => 'submitted']);

        $this->actingAs($this->userWithRole('exam_officer'))
            ->post(route('exam-mode.close', $cycle))->assertRedirect();

        $this->assertSame('closed', $cycle->fresh()->status);
        $this->assertDatabaseMissing('exams', ['id' => $draft->id]);
        $this->assertDatabaseMissing('exam_questions', ['exam_id' => $draft->id]);
        $this->assertDatabaseHas('exams', ['id' => $submitted->id]); // kept
    }
}
