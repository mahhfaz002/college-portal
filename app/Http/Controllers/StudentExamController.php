<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamSubmission;
use App\Models\ResultQuery;
use App\Models\Score;
use App\Models\Student;
use App\Support\Eligibility;
use Illuminate\Http\Request;

class StudentExamController extends Controller
{
    private function student(): Student
    {
        $student = Student::where('email', auth()->user()->email)->first();
        abort_unless($student, 403, 'No student record is linked to this account.');
        return $student;
    }

    /**
     * Exams the student may take right now.
     */
    public function available()
    {
        $student = $this->student();

        $exams = Exam::where('status', 'released')
            ->with('subject')
            ->get()
            ->filter(fn ($e) => in_array($student->class_arm, $e->class_arms, true)
                && Eligibility::evaluate($student, $e)['eligible']);

        $submittedIds = ExamSubmission::where('student_id', $student->id)->pluck('exam_id');

        return view('exams.available', compact('exams', 'submittedIds', 'student'));
    }

    /**
     * Password gate + question paper.
     */
    public function take(Request $request, Exam $exam)
    {
        $student = $this->student();
        $this->guard($exam, $student);

        $unlocked = session()->get("exam_unlocked_{$exam->id}") === true;

        if (!$unlocked) {
            return view('exams.take', ['exam' => $exam, 'unlocked' => false, 'questions' => collect()]);
        }

        $exam->load('questions');
        return view('exams.take', ['exam' => $exam, 'unlocked' => true, 'questions' => $exam->questions]);
    }

    public function unlock(Request $request, Exam $exam)
    {
        $student = $this->student();
        $this->guard($exam, $student);

        $request->validate(['access_password' => 'required|string']);

        if ($request->input('access_password') !== $exam->access_password) {
            return back()->with('error', 'Incorrect exam password. Please ask the Exam Officer.');
        }

        session()->put("exam_unlocked_{$exam->id}", true);
        return redirect()->route('myexams.take', $exam);
    }

    /**
     * Auto-grade objective answers and record the submission.
     */
    public function submit(Request $request, Exam $exam)
    {
        $student = $this->student();
        $this->guard($exam, $student);

        abort_unless(session()->get("exam_unlocked_{$exam->id}") === true, 403, 'Exam is locked.');

        if (ExamSubmission::where('exam_id', $exam->id)->where('student_id', $student->id)->exists()) {
            return redirect()->route('myexams.available')->with('error', 'You have already submitted this exam.');
        }

        $answers = $request->input('answers', []);
        $score = 0;
        $max = 0;
        foreach ($exam->questions as $q) {
            $max += $q->marks;
            if (($answers[$q->id] ?? null) === $q->correct_option) {
                $score += $q->marks;
            }
        }

        ExamSubmission::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'answers' => $answers,
            'objective_score' => $score,
            'max_score' => $max,
            'submitted_at' => now(),
        ]);

        session()->forget("exam_unlocked_{$exam->id}");

        return redirect()->route('myexams.available')->with('success', 'Exam submitted. Your result will be released after grading & approval.');
    }

    /**
     * Student raises a query on a published result.
     */
    public function query(Request $request, Score $score)
    {
        $student = $this->student();
        abort_unless($score->student_id === $student->id, 403);

        $request->validate(['message' => 'required|string|max:1000']);

        ResultQuery::create([
            'student_id' => $student->id,
            'score_id' => $score->id,
            'message' => $request->input('message'),
            'status' => 'open',
        ]);

        return back()->with('success', 'Your query has been sent to the Exam Officer.');
    }

    private function guard(Exam $exam, Student $student): void
    {
        abort_unless($exam->status === 'released', 403, 'This exam is not currently open.');
        abort_unless(in_array($student->class_arm, $exam->class_arms, true), 403, 'This exam is not for your class.');
        abort_unless(Eligibility::evaluate($student, $exam)['eligible'], 403, 'You are not eligible to sit this exam.');
    }
}
