<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Program;
use App\Models\ResultSubmission;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResultTransmissionController extends Controller
{
    public function index(Request $request)
    {
        $term = setting('current_term', 'First Semester');
        $session = setting('current_session', '2025/2026');

        $programs = Program::with('department')->orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        $filterProgram = $request->query('program_id');
        $filterLevel = $request->query('level');
        $filterSemester = $request->query('semester', $term);

        $courses = collect();

        if ($filterProgram) {
            $query = Subject::where('program_id', $filterProgram);
            if ($filterLevel) {
                $query->where('level', $filterLevel);
            }
            if ($filterSemester) {
                $query->where('semester', $filterSemester);
            }
            $courses = $query->with('program.department')->orderBy('level')->orderBy('course_code')->get();

            $submissions = ResultSubmission::where('term', $term)
                ->where('session', $session)
                ->whereIn('subject_id', $courses->pluck('id'))
                ->get()
                ->keyBy('subject_id');

            $courses = $courses->map(function ($course) use ($submissions) {
                $sub = $submissions->get($course->id);
                $course->result_status = $sub
                    ? ($sub->isTransmitted() ? 'transmitted' : 'submitted')
                    : 'pending';
                $course->submission = $sub;
                return $course;
            });
        }

        $levels = $filterProgram
            ? Subject::where('program_id', $filterProgram)->distinct()->pluck('level')->sort()->values()
            : collect();

        return view('results.officer_index', compact(
            'programs', 'departments', 'courses', 'levels',
            'filterProgram', 'filterLevel', 'filterSemester', 'term', 'session'
        ));
    }

    public function show(Subject $subject)
    {
        $term = setting('current_term', 'First Semester');
        $session = setting('current_session', '2025/2026');

        $submission = ResultSubmission::where('subject_id', $subject->id)
            ->where('term', $term)->where('session', $session)->first();

        abort_unless($submission, 404, 'No results have been submitted for this course yet.');
        abort_if($submission->isTransmitted(), 403, 'These results have already been transmitted and can no longer be edited.');

        $subject->load('program.department');

        $students = ($subject->program_id && $subject->level !== null)
            ? Student::where('program_id', $subject->program_id)
                ->where('level', $subject->level)
                ->orderBy('full_name')->get()
            : collect();

        $scores = Score::where('subject_id', $subject->id)
            ->where('term', $term)->where('session', $session)
            ->get()->keyBy('student_id');

        return view('results.officer_edit', compact('subject', 'students', 'scores', 'submission', 'term', 'session'));
    }

    public function save(Request $request, Subject $subject)
    {
        $term = setting('current_term', 'First Semester');
        $session = setting('current_session', '2025/2026');

        $submission = ResultSubmission::where('subject_id', $subject->id)
            ->where('term', $term)->where('session', $session)->first();

        abort_unless($submission && !$submission->isTransmitted(), 403);

        $caMax = (int) setting('ca_max_score', 40);
        $examMax = (int) setting('exam_max_score', 60);

        $request->validate([
            'scores'        => 'required|array',
            'scores.*.ca'   => "nullable|numeric|min:0|max:$caMax",
            'scores.*.exam' => "nullable|numeric|min:0|max:$examMax",
        ]);

        foreach ($request->scores as $studentId => $data) {
            $ca = (int) ($data['ca'] ?? 0);
            $exam = (int) ($data['exam'] ?? 0);
            $total = $ca + $exam;

            Score::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'subject_id' => $subject->id,
                    'term'       => $term,
                    'session'    => $session,
                ],
                [
                    'ca_score'   => $ca,
                    'exam_score' => $exam,
                    'total'      => $total,
                    'grade'      => grade_for($total)['grade'],
                ]
            );
        }

        ActivityLog::record("Edited results for {$subject->name}", 'results.officer_edit');

        return back()->with('success', 'Results saved. You can continue editing or transmit when ready.');
    }

    public function transmit(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'level'      => 'required|integer',
            'semester'   => 'required|string',
        ]);

        $term = setting('current_term', 'First Semester');
        $session = setting('current_session', '2025/2026');

        $subjects = Subject::where('program_id', $request->program_id)
            ->where('level', $request->level)
            ->where('semester', $request->semester)
            ->pluck('id');

        $submissions = ResultSubmission::whereIn('subject_id', $subjects)
            ->where('term', $term)->where('session', $session)
            ->where('status', 'submitted')
            ->get();

        if ($submissions->isEmpty()) {
            return back()->with('error', 'No submitted results found to transmit for this level.');
        }

        $now = now();
        $userId = auth()->id();

        ResultSubmission::whereIn('id', $submissions->pluck('id'))
            ->update([
                'status'         => 'transmitted',
                'transmitted_at' => $now,
                'transmitted_by' => $userId,
            ]);

        Score::whereIn('subject_id', $subjects)
            ->where('term', $term)->where('session', $session)
            ->whereNotNull('submitted_at')
            ->update([
                'transmitted_at' => $now,
                'status'         => 'published',
                'published_at'   => $now,
            ]);

        $program = Program::find($request->program_id);
        ActivityLog::record(
            "Transmitted results for {$program->name} L{$request->level} {$request->semester} ({$term}, {$session})",
            'results.transmit'
        );

        return back()->with('success', "Results transmitted successfully for {$program->name} Level {$request->level} {$request->semester}. Students can now pay to view their results.");
    }

    public function scan(ResultSubmission $submission)
    {
        abort_unless($submission->scan_path, 404);

        $disk = config('filesystems.documents', 'local');
        if (!Storage::disk($disk)->exists($submission->scan_path)) {
            $disk = 'public';
        }
        abort_unless(Storage::disk($disk)->exists($submission->scan_path), 404);

        return Storage::disk($disk)->download($submission->scan_path, 'result_scan_' . $submission->id . '.jpg');
    }
}
