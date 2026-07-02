<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Program;
use App\Models\ResultSubmission;
use App\Models\Score;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

class CarryoverReconcileTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    public function test_carryover_pass_replaces_original_failed_grade_and_notifies(): void
    {
        $this->seed();
        $this->bootCollege();
        \App\Models\Setting::set('current_term', 'First Semester');
        \App\Models\Setting::set('current_session', '2025/2026');

        $dept = Department::create(['name' => 'Health', 'acronym' => 'H', 'section' => 'UG']);
        $prog = Program::create(['name' => 'Nursing', 'acronym' => 'N', 'department_id' => $dept->id]);
        $subject = Subject::create(['name' => 'Anatomy', 'course_code' => 'AN101', 'program_id' => $prog->id, 'level' => 100, 'semester' => 'First Semester']);

        $student = $this->studentRecord(['program_id' => $prog->id, 'department_id' => $dept->id, 'level' => 100, 'email' => 'carry@x.test']);
        $studentUser = $this->userWithRole('student', ['email' => 'carry@x.test']);

        // ORIGINAL failed attempt (an earlier session).
        $original = Score::create([
            'student_id' => $student->id, 'subject_id' => $subject->id,
            'term' => 'First Semester', 'session' => '2024/2025',
            'ca_score' => 10, 'exam_score' => 15, 'total' => 25, 'grade' => 'F', 'status' => 'published',
        ]);
        Score::where('id', $original->id)->update(['created_at' => now()->subYear()]);

        // The carryover REWRITE — passed, submitted this current semester.
        $rewrite = Score::create([
            'student_id' => $student->id, 'subject_id' => $subject->id,
            'term' => 'First Semester', 'session' => '2025/2026',
            'ca_score' => 30, 'exam_score' => 45, 'total' => 75, 'grade' => 'B', 'status' => 'submitted',
            'submitted_by' => 1, 'submitted_at' => now(),
        ]);

        ResultSubmission::create([
            'college_id' => $this->college->id, 'subject_id' => $subject->id, 'user_id' => 1,
            'term' => 'First Semester', 'session' => '2025/2026', 'status' => 'submitted', 'submitted_at' => now(),
        ]);

        Notification::fake();

        $this->actingAs($this->userWithRole('exam_officer'))->post(route('results.officer.transmit'), [
            'program_id' => $prog->id, 'level' => 100, 'semester' => 'First Semester',
        ])->assertRedirect();

        // The original failed record now carries the new passing result…
        $original->refresh();
        $this->assertSame('B', $original->grade);
        $this->assertEquals(75, (int) $original->total);

        // …and the duplicate carryover score was removed.
        $this->assertDatabaseMissing('scores', ['id' => $rewrite->id]);

        // The student was notified to check their results.
        Notification::assertSentTo($studentUser, \App\Notifications\CarryoverResultUpdated::class);
    }

    public function test_carryover_still_failed_keeps_F_for_re_registration(): void
    {
        $this->seed();
        $this->bootCollege();
        \App\Models\Setting::set('current_term', 'First Semester');
        \App\Models\Setting::set('current_session', '2025/2026');

        $dept = Department::create(['name' => 'Health', 'acronym' => 'H', 'section' => 'UG']);
        $prog = Program::create(['name' => 'Nursing', 'acronym' => 'N', 'department_id' => $dept->id]);
        $subject = Subject::create(['name' => 'Pharmacology', 'course_code' => 'PH201', 'program_id' => $prog->id, 'level' => 100, 'semester' => 'First Semester']);
        $student = $this->studentRecord(['program_id' => $prog->id, 'department_id' => $dept->id, 'level' => 100, 'email' => 'carry2@x.test']);

        $original = Score::create(['student_id' => $student->id, 'subject_id' => $subject->id, 'term' => 'First Semester', 'session' => '2024/2025', 'ca_score' => 5, 'exam_score' => 10, 'total' => 15, 'grade' => 'F', 'status' => 'published']);
        Score::where('id', $original->id)->update(['created_at' => now()->subYear()]);

        Score::create(['student_id' => $student->id, 'subject_id' => $subject->id, 'term' => 'First Semester', 'session' => '2025/2026', 'ca_score' => 10, 'exam_score' => 20, 'total' => 30, 'grade' => 'F', 'status' => 'submitted', 'submitted_by' => 1, 'submitted_at' => now()]);
        ResultSubmission::create(['college_id' => $this->college->id, 'subject_id' => $subject->id, 'user_id' => 1, 'term' => 'First Semester', 'session' => '2025/2026', 'status' => 'submitted', 'submitted_at' => now()]);

        $this->actingAs($this->userWithRole('exam_officer'))->post(route('results.officer.transmit'), [
            'program_id' => $prog->id, 'level' => 100, 'semester' => 'First Semester',
        ])->assertRedirect();

        // Still F (the rewrite was also failed) — stays a carryover.
        $this->assertSame('F', $original->fresh()->grade);
    }
}
