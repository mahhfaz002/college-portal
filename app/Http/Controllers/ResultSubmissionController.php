<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ResultSubmission;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\Request;

class ResultSubmissionController extends Controller
{
    public function create(Subject $subject)
    {
        $user = auth()->user();
        abort_unless($user->subjects()->whereKey($subject->id)->exists(), 403,
            'This course is not assigned to you.');

        $term = setting('current_term', 'First Semester');
        $session = setting('current_session', '2025/2026');

        $existing = ResultSubmission::where('subject_id', $subject->id)
            ->where('term', $term)->where('session', $session)->first();

        if ($existing) {
            return redirect()->route('dashboard')
                ->with('error', 'Results for this course have already been submitted.');
        }

        $subject->load('program');

        $students = ($subject->program_id && $subject->level !== null)
            ? Student::where('program_id', $subject->program_id)
                ->where('level', $subject->level)
                ->orderBy('full_name')->get()
            : collect();

        $scores = Score::where('subject_id', $subject->id)
            ->where('term', $term)->where('session', $session)
            ->get()->keyBy('student_id');

        return view('results.lecturer_submit', compact('subject', 'students', 'scores', 'term', 'session'));
    }

    public function store(Request $request, Subject $subject)
    {
        $user = auth()->user();
        abort_unless($user->subjects()->whereKey($subject->id)->exists(), 403,
            'This course is not assigned to you.');

        $term = setting('current_term', 'First Semester');
        $session = setting('current_session', '2025/2026');

        $existing = ResultSubmission::where('subject_id', $subject->id)
            ->where('term', $term)->where('session', $session)->first();

        if ($existing) {
            return back()->with('error', 'Results for this course have already been submitted.');
        }

        $caMax = (int) setting('ca_max_score', 40);
        $examMax = (int) setting('exam_max_score', 60);

        $request->validate([
            'scores'        => 'required|array',
            'scores.*.ca'   => "nullable|numeric|min:0|max:$caMax",
            'scores.*.exam' => "nullable|numeric|min:0|max:$examMax",
            'scan'          => 'required|file|mimes:jpeg,jpg|max:5120',
        ]);

        $scanPath = $request->file('scan')->store('documents/result-scans', config('filesystems.documents'));

        foreach ($request->scores as $studentId => $data) {
            $hasCa = isset($data['ca']) && $data['ca'] !== '';
            $hasExam = isset($data['exam']) && $data['exam'] !== '';
            if ($hasCa || $hasExam) {
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
                        'ca_score'     => $ca,
                        'exam_score'   => $exam,
                        'total'        => $total,
                        'grade'        => grade_for($total)['grade'],
                        'status'       => 'submitted',
                        'submitted_by' => $user->id,
                        'submitted_at' => now(),
                    ]
                );
            }
        }

        ResultSubmission::create([
            'college_id'             => current_college_id(),
            'subject_id'             => $subject->id,
            'user_id'                => $user->id,
            'term'                   => $term,
            'session'                => $session,
            'scan_path'              => $scanPath,
            'submitted_at'           => now(),
            'physical_copy_deadline' => now()->addHours(72),
            'status'                 => 'submitted',
        ]);

        ActivityLog::record("Submitted results for {$subject->name} ({$term}, {$session})", 'results.submit');

        return redirect()->route('dashboard')
            ->with('success', 'Results submitted successfully. Please submit the physical copy to the Exam Officer within 72 hours.');
    }

}
