<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Exam;
use App\Models\Program;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

class ExamLevelInstructionsTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    private Subject $course;
    private User $officer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bootCollege(['domain' => 'localhost']);
        $dept = Department::create(['name' => 'Health', 'acronym' => 'HS', 'section' => 'UG']);
        $program = Program::create(['name' => 'Nursing', 'acronym' => 'NUR', 'department_id' => $dept->id]);
        $this->course = Subject::create([
            'name' => 'Anatomy', 'course_code' => 'ANA101', 'course_unit' => 2,
            'program_id' => $program->id, 'department_id' => $dept->id, 'level' => '100', 'semester' => 'First',
        ]);
        $this->officer = $this->userWithRole('exam_officer');
    }

    public function test_exam_is_created_by_level_not_class(): void
    {
        $this->actingAs($this->officer)->post('/exams', [
            'subject_id' => $this->course->id, 'title' => 'First Semester Anatomy',
            'duration_minutes' => 60, 'level' => '100',
        ])->assertRedirect();

        $exam = Exam::latest('id')->first();
        $this->assertSame('100', $exam->level);
        $this->assertSame([], $exam->class_arms);
    }

    public function test_create_requires_a_level(): void
    {
        $this->actingAs($this->officer)->post('/exams', [
            'subject_id' => $this->course->id, 'title' => 'X', 'duration_minutes' => 60,
        ])->assertSessionHasErrors('level');
    }

    public function test_paper_is_blocked_until_hod_approves_and_release_time_arrives(): void
    {
        $exam = Exam::create([
            'college_id' => $this->college->id, 'subject_id' => $this->course->id,
            'title' => 'Anatomy', 'term' => 'First', 'session' => '2025/2026', 'level' => '100',
            'class_arms' => [], 'duration_minutes' => 60, 'status' => 'submitted',
            'instructions_objective' => 'Shade the correct option on your OMR sheet.',
            'instructions_theory' => 'Answer ANY FOUR questions.', 'created_by' => $this->officer->id,
        ]);
        $exam->questions()->create([
            'question_text' => '2 + 2 = ?', 'option_a' => '4', 'option_b' => '5',
            'correct_option' => 'a', 'marks' => 1, 'type' => 'objective', 'created_by' => $this->officer->id,
        ]);
        $exam->questions()->create([
            'question_text' => 'Describe the heart.', 'option_a' => '', 'option_b' => '', 'correct_option' => 'a',
            'marks' => 10, 'type' => 'theory', 'theory_number' => 1, 'created_by' => $this->officer->id,
        ]);

        // SUBMITTED but not yet HOD-approved → officer cannot access it.
        $this->actingAs($this->officer)->get(route('exams.papers.print', $exam))->assertNotFound();

        // Approved but scheduled for a FUTURE release → still blocked.
        $exam->update(['status' => 'approved', 'release_at' => now()->addDay()]);
        $this->actingAs($this->officer)->get(route('exams.papers.print', $exam))->assertNotFound();

        // Release time reached → now downloadable, with the custom instructions.
        $exam->update(['release_at' => now()->subMinute()]);
        $this->actingAs($this->officer)->get(route('exams.papers.print', $exam))
            ->assertOk()
            ->assertSee($this->college->name)
            ->assertSee('Shade the correct option on your OMR sheet.')
            ->assertSee('Answer ANY FOUR questions.');
    }

    public function test_hod_approval_can_schedule_a_future_release(): void
    {
        $dept = \App\Models\Department::create(['name' => 'D2', 'acronym' => 'D2', 'section' => 'UG']);
        $hod = $this->userWithRole('hod', ['department_id' => $this->course->department_id]);
        $exam = Exam::create([
            'college_id' => $this->college->id, 'subject_id' => $this->course->id,
            'title' => 'Anatomy', 'term' => 'First', 'session' => '2025/2026', 'level' => '100',
            'class_arms' => [], 'duration_minutes' => 60, 'status' => 'submitted',
            'submitted_at' => now(), 'created_by' => $this->officer->id,
        ]);

        $this->actingAs($hod)->post(route('hod.exam-reviews.approve', $exam), [
            'release_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $exam->refresh();
        $this->assertSame('approved', $exam->status);
        $this->assertNotNull($exam->release_at);
        $this->assertFalse($exam->isReleasedToOfficer(), 'A future-scheduled paper is not released yet');
    }
}
