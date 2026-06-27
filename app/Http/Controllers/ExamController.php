<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamEligibility;
use App\Models\ExamSubmission;
use App\Models\ResultQuery;
use App\Models\SchoolClass;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Support\Eligibility;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExamController extends Controller
{
    /**
     * Exam listing (officer/oversight). Role-aware entry point.
     */
    public function index()
    {
        $exams = Exam::with('subject')->withCount('questions', 'submissions')->latest()->get();
        return view('exams.index', compact('exams'));
    }

    public function create()
    {
        // College levels in use (e.g. 100, 200, ND I…), drawn from the courses.
        $levels = Subject::query()->whereNotNull('level')->distinct()
            ->orderBy('level')->pluck('level')->filter()->values();

        return view('exams.create', [
            'subjects' => Subject::orderBy('name')->get(),
            'levels'   => $levels,
            'term'     => setting('current_term', ''),
            'session'  => setting('current_session', ''),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject_id'       => 'required|exists:subjects,id',
            'title'            => 'required|string|max:255',
            'duration_minutes' => 'required|integer|min:5',
            'level'            => 'required|string|max:20',
        ]);

        $exam = Exam::create([
            'subject_id'       => $data['subject_id'],
            'title'            => $data['title'],
            'term'             => setting('current_term', ''),
            'session'          => setting('current_session', ''),
            'level'            => $data['level'],
            'class_arms'       => [],
            'duration_minutes' => $data['duration_minutes'],
            'status'           => 'draft',
            'created_by'       => auth()->id(),
        ]);

        return redirect()->route('exams.show', $exam)->with('success', 'Exam created. Teachers can now author questions; review eligibility before release.');
    }

    /**
     * Officer view: eligibility lists + lifecycle controls.
     */
    public function show(Exam $exam)
    {
        $exam->load('subject', 'questions');

        // The exam's cohort = students of the course's programme at the exam level
        // (college terms), not legacy "class arms".
        $level = $exam->level ?: optional($exam->subject)->level;
        $students = Student::query()
            ->when(optional($exam->subject)->program_id, fn ($q, $pid) => $q->where('program_id', $pid))
            ->when($level, fn ($q, $lvl) => $q->where('level', $lvl))
            ->orderBy('full_name')->get();

        $overrides = ExamEligibility::where('exam_id', $exam->id)->get()->keyBy('student_id');

        $rows = $students->map(function ($s) use ($exam, $overrides) {
            return ['student' => $s] + Eligibility::evaluate($s, $exam, $overrides->get($s->id));
        });

        $eligible   = $rows->filter(fn ($r) => $r['eligible'])->values();
        $ineligible = $rows->reject(fn ($r) => $r['eligible'])->values();

        return view('exams.show', compact('exam', 'eligible', 'ineligible'));
    }

    /**
     * Toggle a student's eligibility (admit / block) for this exam.
     */
    public function toggleEligibility(Request $request, Exam $exam, Student $student)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['eligible', 'blocked'])],
            'reason' => 'nullable|string|max:255',
        ]);

        ExamEligibility::updateOrCreate(
            ['exam_id' => $exam->id, 'student_id' => $student->id],
            ['status' => $data['status'], 'reason' => $data['reason'] ?? null, 'decided_by' => auth()->id()]
        );

        $verb = $data['status'] === 'eligible' ? 'admitted to' : 'blocked from';
        return back()->with('success', "{$student->full_name} {$verb} this exam.");
    }

    /**
     * Open the exam for grading. Online exam-taking (and its access password)
     * was retired — exams are now conducted offline from the downloaded CBT/theory
     * papers, so this simply unlocks the course lecturer's grading once questions
     * exist. No password.
     */
    public function release(Request $request, Exam $exam)
    {
        if ($exam->questions()->count() === 0) {
            return back()->with('error', 'Cannot open for grading: no questions have been authored yet.');
        }

        $exam->update(['status' => 'released']);

        return back()->with('success', 'Exam opened for grading. The course lecturer can now enter scores.');
    }

    public function close(Exam $exam)
    {
        $exam->update(['status' => 'grading']);
        return back()->with('success', 'Exam closed. Course lecturer can now grade submissions.');
    }

    /**
     * Compile results for board review: list scores tied to this exam.
     */
    public function compile(Exam $exam)
    {
        $exam->load('subject');
        $scores = Score::where('exam_id', $exam->id)
            ->with('student')
            ->get()
            ->sortByDesc('total')
            ->values();

        return view('exams.compile', compact('exam', 'scores'));
    }

    /**
     * Live edit of a single result during the board meeting.
     */
    public function updateScore(Request $request, Score $score)
    {
        $data = $request->validate([
            'ca_score'   => 'required|integer|min:0',
            'exam_score' => 'required|integer|min:0',
        ]);

        $total = $data['ca_score'] + $data['exam_score'];
        $score->update([
            'ca_score'   => $data['ca_score'],
            'exam_score' => $data['exam_score'],
            'total'      => $total,
            'grade'      => grade_for($total)['grade'],
            'status'     => 'compiled',
        ]);

        return back()->with('success', 'Result updated.');
    }

    /**
     * Approve & publish all results for this exam to students.
     */
    public function approve(Exam $exam)
    {
        Score::where('exam_id', $exam->id)->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
        $exam->update(['status' => 'published']);

        return redirect()->route('exams.show', $exam)->with('success', 'All results approved and published to students.');
    }

    /**
     * Officer inbox of student result queries.
     */
    public function queries()
    {
        $queries = ResultQuery::with(['student', 'score.subject'])->latest()->get();
        return view('exams.queries', compact('queries'));
    }

    public function resolveQuery(Request $request, ResultQuery $query)
    {
        $data = $request->validate([
            'resolution' => 'required|string|max:1000',
            'ca_score'   => 'nullable|integer|min:0',
            'exam_score' => 'nullable|integer|min:0',
        ]);

        // Optionally amend the queried score.
        if ($query->score && ($request->filled('ca_score') || $request->filled('exam_score'))) {
            $ca = $request->input('ca_score', $query->score->ca_score);
            $ex = $request->input('exam_score', $query->score->exam_score);
            $total = $ca + $ex;
            $query->score->update([
                'ca_score' => $ca, 'exam_score' => $ex, 'total' => $total,
                'grade' => grade_for($total)['grade'], 'status' => 'published', 'published_at' => now(),
            ]);
        }

        $query->update([
            'resolution' => $data['resolution'],
            'status' => 'resolved',
            'resolved_by' => auth()->id(),
        ]);

        return back()->with('success', 'Query resolved'.($query->score ? ' and result updated' : '').'.');
    }
}
