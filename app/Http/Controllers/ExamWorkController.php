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

    /**
     * Lecturer "Set Exam Questions": their assigned courses (with level &
     * course of study) and, for the active exam cycle, each course's question
     * set status. ?open={exam} opens that course's authoring popup.
     */
    public function myExams()
    {
        $user = Auth::user();
        $subjects = $user->subjects()->with('program', 'department')->orderBy('name')->get();
        $cycle = \App\Models\ExamCycle::active();

        $exams = $cycle
            ? Exam::where('exam_cycle_id', $cycle->id)
                ->whereIn('subject_id', $subjects->pluck('id'))
                ->withCount([
                    'questions',
                    'questions as objective_count' => fn ($q) => $q->where('type', 'objective'),
                    'questions as theory_count' => fn ($q) => $q->where('type', 'theory'),
                ])->get()->keyBy('subject_id')
            : collect();

        // The course whose popup should be open (after an action redirect).
        $openExam = null;
        if (request('open')) {
            $openExam = Exam::with(['questions' => fn ($q) => $q->orderBy('type')->orderBy('theory_number')->orderBy('id'), 'subject.program'])
                ->find(request('open'));
            if ($openExam && ! $user->subjects()->where('subjects.id', $openExam->subject_id)->exists()) {
                $openExam = null; // not theirs
            }
        }

        return view('exams.my', compact('subjects', 'cycle', 'exams', 'openExam'));
    }

    /** Find-or-create the question set for one assigned course in the active cycle. */
    public function openCourse(\App\Models\Subject $subject)
    {
        abort_unless(Auth::user()->subjects()->where('subjects.id', $subject->id)->exists(), 403, 'This course is not assigned to you.');

        $cycle = \App\Models\ExamCycle::active();
        abort_unless($cycle, 403, 'Exam Mode is not active yet.');

        $exam = Exam::firstOrCreate(
            ['subject_id' => $subject->id, 'exam_cycle_id' => $cycle->id],
            [
                'title'      => $subject->name.' — '.$cycle->title,
                'term'       => setting('current_term', ''),
                'session'    => setting('current_session', ''),
                'level'      => $subject->level,
                'class_arms' => [],
                'status'     => 'draft',
                'created_by' => auth()->id(),
            ]
        );

        return redirect()->route('exams.my', ['open' => $exam->id]);
    }

    public function questions(Exam $exam)
    {
        $this->authorizeSubject($exam);
        $exam->load('subject', 'questions');
        return view('exams.questions', compact('exam'));
    }

    /** Save OPTIONAL per-section instructions printed on the question paper. */
    public function storeInstructions(Request $request, Exam $exam)
    {
        $this->authorizeSubject($exam);
        $this->assertEditable($exam);

        $data = $request->validate([
            'instructions_objective' => 'nullable|string|max:1000',
            'instructions_theory'    => 'nullable|string|max:1000',
        ]);

        $exam->update([
            'instructions_objective' => $data['instructions_objective'] ?: null,
            'instructions_theory'    => $data['instructions_theory'] ?: null,
        ]);

        return redirect()->route('exams.my', ['open' => $exam->id])->with('success', 'Section instructions saved.');
    }

    /** Add a theory question (numbered 1–10, text only). */
    public function storeTheory(Request $request, Exam $exam)
    {
        $this->authorizeSubject($exam);
        $this->assertEditable($exam);

        $data = $request->validate([
            'theory_number' => 'required|integer|min:1|max:10',
            'question_text' => 'required|string',
        ]);

        $exam->questions()->updateOrCreate(
            ['type' => 'theory', 'theory_number' => $data['theory_number']],
            [
                'question_text'  => $data['question_text'],
                'option_a' => '', 'option_b' => '', 'correct_option' => 'a',
                'marks' => 1, 'created_by' => auth()->id(),
            ]
        );

        return redirect()->route('exams.my', ['open' => $exam->id])->with('success', 'Theory question saved.');
    }

    /** Download the blank CSV template for bulk question upload. */
    public function template()
    {
        $headers = ['QUESTION', 'OPTION A', 'OPTION B', 'OPTION C', 'OPTION D', 'CORRECT ANSWER'];
        $sample  = ['What is the powerhouse of the cell?', 'Nucleus', 'Mitochondria', 'Ribosome', 'Golgi body', 'B'];

        $csv = implode(',', $headers)."\n".implode(',', array_map(fn ($c) => '"'.$c.'"', $sample))."\n";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="exam_questions_template.csv"',
        ]);
    }

    /** Import questions from an uploaded CSV. */
    public function importCsv(Request $request, Exam $exam)
    {
        $this->authorizeSubject($exam);
        $this->assertEditable($exam);

        $request->validate(['csv' => 'required|file|mimes:csv,txt|max:2048']);

        $rows = array_map('str_getcsv', file($request->file('csv')->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        if (empty($rows)) {
            return back()->with('error', 'The CSV is empty.');
        }

        // Drop the header row if present.
        $first = array_map(fn ($c) => strtoupper(trim((string) $c)), $rows[0]);
        if (in_array('QUESTION', $first, true)) {
            array_shift($rows);
        }

        $map = ['A' => 'a', 'B' => 'b', 'C' => 'c', 'D' => 'd'];
        $count = 0;
        foreach ($rows as $r) {
            $q = trim($r[0] ?? '');
            if ($q === '') continue;
            $correct = strtoupper(trim($r[5] ?? 'A'));
            $exam->questions()->create([
                'question_text'  => $q,
                'option_a'       => trim($r[1] ?? ''),
                'option_b'       => trim($r[2] ?? ''),
                'option_c'       => trim($r[3] ?? '') ?: null,
                'option_d'       => trim($r[4] ?? '') ?: null,
                'correct_option' => $map[$correct] ?? 'a',
                'type'           => 'objective',
                'marks'          => 1,
                'created_by'     => auth()->id(),
            ]);
            $count++;
        }

        return redirect()->route('exams.my', ['open' => $exam->id])->with('success', "$count question(s) imported. Review/edit, then submit.");
    }

    /** Forward the questions to the exam officer and lock further editing. */
    public function submitToOfficer(Exam $exam)
    {
        $this->authorizeSubject($exam);
        if ($exam->questions()->count() < 1) {
            return back()->with('error', 'Add at least one question before submitting.');
        }
        $exam->update(['submitted_at' => now(), 'status' => 'submitted']);

        return redirect()->route('exams.my')->with('success', 'Questions submitted for HOD review. They are now locked.');
    }

    /** Editing is allowed only while unsubmitted, within an active cycle, before the deadline. */
    private function assertEditable(Exam $exam): void
    {
        abort_if($exam->isLocked(), 403, 'Questions already submitted — editing is locked.');

        $cycle = $exam->exam_cycle_id ? \App\Models\ExamCycle::find($exam->exam_cycle_id) : null;
        abort_unless($cycle && $cycle->isActive(), 403, 'Exam Mode is not active.');
        abort_if(now()->greaterThan($cycle->submission_deadline_at), 403, 'The question-submission deadline has passed.');
    }

    public function storeQuestion(Request $request, Exam $exam)
    {
        $this->authorizeSubject($exam);
        $this->assertEditable($exam);

        $data = $request->validate([
            'question_text' => 'required|string',
            'option_a' => 'required|string|max:255',
            'option_b' => 'required|string|max:255',
            'option_c' => 'nullable|string|max:255',
            'option_d' => 'nullable|string|max:255',
            'correct_option' => ['required', Rule::in(['a', 'b', 'c', 'd'])],
            'marks' => 'required|integer|min:1',
            'question_id' => 'nullable|integer',   // present when editing
        ]);

        $payload = collect($data)->except('question_id')->all() + ['type' => 'objective'];

        // Edit-in-place when a question_id is supplied, else create.
        if (!empty($data['question_id']) && ($q = $exam->questions()->find($data['question_id']))) {
            $q->update($payload);
        } else {
            $exam->questions()->create($payload + ['created_by' => auth()->id()]);
        }

        return redirect()->route('exams.my', ['open' => $exam->id])->with('success', 'Objective question saved.');
    }

    public function deleteQuestion(ExamQuestion $question)
    {
        $this->authorizeSubject($question->exam);
        $this->assertEditable($question->exam);
        $examId = $question->exam_id;
        $question->delete();
        return redirect()->route('exams.my', ['open' => $examId])->with('success', 'Question removed.');
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

        // Prefer the student's department grading scheme (set by the HOD);
        // fall back to the global default bands.
        foreach ($request->input('ca') as $studentId => $ca) {
            $ca = (int) $ca;
            $examScore = (int) ($request->input("exam.$studentId", 0));
            $total = $ca + $examScore;

            $deptId = Student::where('id', $studentId)->value('department_id');
            $band = $deptId ? \App\Models\GradingScheme::gradeFor($deptId, $total) : null;
            $gradeLetter = $band ? $band->grade : grade_for($total)['grade'];

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
                    'grade'      => $gradeLetter,
                    'exam_id'    => $exam->id,
                    'status'     => 'submitted',   // forwarded to exam officer
                ]
            );
        }

        $exam->update(['status' => 'grading']);

        return redirect()->route('dashboard')->with('success', 'Grades submitted to the Exam Officer for compilation.');
    }
}
