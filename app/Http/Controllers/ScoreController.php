<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Student;
use App\Models\Score;
use App\Models\Subject;
use App\Models\User;
use App\Notifications\ReportCardGenerated;
use Illuminate\Http\Request;

class ScoreController extends Controller
{
    /**
     * Course score-entry sheet (tertiary model).
     *
     * A course (Subject) belongs to a programme + level, so the students to be
     * scored are exactly that programme+level cohort. Lecturers may only score
     * courses assigned to them (subject_teacher pivot); oversight roles
     * (exam_officer / registrar / proprietor) may pick any course.
     */
    public function create(Request $request)
    {
        $user = auth()->user();
        $isLecturer = $user->role === 'lecturer';

        $subjects = $isLecturer
            ? $user->subjects()->with('program')->orderBy('name')->get()
            : Subject::with('program')->orderBy('name')->get();

        $selectedId = $request->query('subject_id') ?: $subjects->first()?->id;
        $subject = $selectedId ? $subjects->firstWhere('id', (int) $selectedId) : null;

        // A lecturer may only open a course assigned to them (the list above is
        // already scoped, so a missing match means an unauthorised id).
        if ($isLecturer && $selectedId && !$subject) {
            abort(403, 'You are not assigned to this course.');
        }

        // Students for the course = its programme + level cohort.
        $students = ($subject && $subject->program_id && $subject->level !== null)
            ? Student::where('program_id', $subject->program_id)
                ->where('level', $subject->level)
                ->orderBy('full_name')->get()
            : collect();

        return view('scores.create', [
            'subjects'        => $subjects,
            'selectedSubject' => $subject,
            'students'        => $students,
        ]);
    }

    public function store(Request $request)
    {
        $caMax = (int) setting('ca_max_score', 40);
        $examMax = (int) setting('exam_max_score', 60);

        $validated = $request->validate([
            'subject_id'    => 'required|exists:subjects,id',
            'scores'        => 'required|array',
            'scores.*.ca'   => "nullable|numeric|min:0|max:$caMax",
            'scores.*.exam' => "nullable|numeric|min:0|max:$examMax",
        ]);

        // Lecturers may only enter scores for courses assigned to them.
        $user = auth()->user();
        if ($user->role === 'lecturer' && !$user->subjects()->whereKey($validated['subject_id'])->exists()) {
            abort(403, 'You are not assigned to this course.');
        }

        $term = setting('current_term', 'First Semester');
        $session = setting('current_session', '2025/2026');

        $updatedStudentIds = [];

        foreach ($request->scores as $studentId => $data) {
            $hasCa = isset($data['ca']) && $data['ca'] !== '';
            $hasExam = isset($data['exam']) && $data['exam'] !== '';
            if ($hasCa || $hasExam) {
                Score::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'subject_id' => $validated['subject_id'],
                        'term'       => $term,
                        'session'    => $session,
                    ],
                    [
                        'ca_score'   => $data['ca'] ?? 0,
                        'exam_score' => $data['exam'] ?? 0,
                    ]
                );
                $updatedStudentIds[] = $studentId;
            }
        }

        // Notify linked student accounts that results were updated.
        if (!empty($updatedStudentIds)) {
            $students = Student::whereIn('id', $updatedStudentIds)->get();
            foreach ($students as $student) {
                $linked = User::where('email', $student->email)->first();
                $linked?->notify(new ReportCardGenerated());
            }
        }

        $subjectName = Subject::find($validated['subject_id'])?->name ?? 'subject';
        ActivityLog::record("Entered scores for {$subjectName}", 'scores.store');

        return redirect()->route('dashboard')->with('success', "Scores for {$subjectName} updated successfully!");
    }
}
