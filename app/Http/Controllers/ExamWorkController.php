<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamSubmission;
use App\Models\Score;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Teacher-facing exam work: authoring objective questions and grading
 * submissions for exams in the subjects they teach.
 */
class ExamWorkController extends Controller
{
    private function authorizeSubject(Exam $exam): void
    {
        $user = Auth::user();
        $teachesSubject = $user->subjects()->where('subjects.id', $exam->subject_id)->exists();
        $teachesClass = $user->classes()->whereIn('classes.name', $exam->class_arms)->exists();

        abort_unless($teachesSubject || $teachesClass, 403, 'You do not teach this exam\'s subject or classes.');
    }

    public function questions(Exam $exam)
    {
        $this->authorizeSubject($exam);
        $exam->load('subject', 'questions');
        return view('exams.questions', compact('exam'));
    }

    public function storeQuestion(Request $request, Exam $exam)
    {
        $this->authorizeSubject($exam);

        $data = $request->validate([
            'question_text' => 'required|string',
            'option_a' => 'required|string|max:255',
            'option_b' => 'required|string|max:255',
            'option_c' => 'nullable|string|max:255',
            'option_d' => 'nullable|string|max:255',
            'correct_option' => ['required', Rule::in(['a', 'b', 'c', 'd'])],
            'marks' => 'required|integer|min:1',
        ]);

        $exam->questions()->create($data + ['created_by' => auth()->id()]);

        return back()->with('success', 'Question added.');
    }

    public function deleteQuestion(ExamQuestion $question)
    {
        $this->authorizeSubject($question->exam);
        $question->delete();
        return back()->with('success', 'Question removed.');
    }

    /**
     * Grading sheet: students who submitted, with auto objective score.
     */
    public function grade(Exam $exam)
    {
        $this->authorizeSubject($exam);

        $submissions = ExamSubmission::where('exam_id', $exam->id)
            ->with('student')->get();

        // Existing scores for these students (so the teacher sees prior CA).
        $scores = Score::where('exam_id', $exam->id)->get()->keyBy('student_id');

        return view('exams.grade', compact('exam', 'submissions', 'scores'));
    }

    /**
     * Save CA/test marks; system totals + grades and forwards to officer.
     */
    public function saveGrades(Request $request, Exam $exam)
    {
        $this->authorizeSubject($exam);

        $request->validate([
            'ca'   => 'required|array',
            'exam' => 'required|array',
        ]);

        foreach ($request->input('ca') as $studentId => $ca) {
            $ca = (int) $ca;
            $examScore = (int) ($request->input("exam.$studentId", 0));
            $total = $ca + $examScore;
            $g = grade_for($total);

            Score::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'subject_id' => $exam->subject_id,
                    'term'       => $exam->term,
                    'session'    => $exam->session,
                ],
                [
                    'ca_score'   => $ca,
                    'exam_score' => $examScore,
                    'total'      => $total,
                    'grade'      => $g['grade'],
                    'exam_id'    => $exam->id,
                    'status'     => 'submitted',   // forwarded to exam officer
                ]
            );
        }

        $exam->update(['status' => 'grading']);

        return redirect()->route('dashboard')->with('success', 'Grades submitted to the Exam Officer for compilation.');
    }
}
