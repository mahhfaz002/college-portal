<?php

namespace App\Http\Controllers;

use App\Models\CourseRegistration;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Support\Sections;
use Illuminate\Http\Request;

class CourseRegistrationController extends Controller
{
    public function index()
    {
        $student = Student::where('email', auth()->user()->email)->firstOrFail();
        $student->load(['program.department', 'department']);

        $term = setting('current_term', 'First Semester');
        $session = setting('current_session', '2025/2026');
        $isUG = optional($student->department)->section === Sections::UG;
        $maxUnits = $isUG ? ($student->program->max_credit_units ?? 24) : null;

        $this->autoRegisterCarryovers($student, $term, $session);

        $registrations = CourseRegistration::where('student_id', $student->id)
            ->where('term', $term)->where('session', $session)
            ->whereNull('dropped_at')
            ->with('subject')
            ->get();

        $registeredSubjectIds = $registrations->pluck('subject_id')->toArray();
        $registeredUnits = $registrations->sum(fn ($r) => $r->subject->course_unit ?? 0);

        $availableCourses = Subject::where('program_id', $student->program_id)
            ->where('level', $student->level)
            ->where('semester', $term)
            ->whereNotIn('id', $registeredSubjectIds)
            ->orderBy('course_code')
            ->get();

        return view('student.course_registration', compact(
            'student', 'registrations', 'availableCourses',
            'registeredUnits', 'maxUnits', 'isUG', 'term', 'session'
        ));
    }

    public function add(Request $request)
    {
        $request->validate([
            'subject_ids'   => 'required|array',
            'subject_ids.*' => 'exists:subjects,id',
        ]);

        $student = Student::where('email', auth()->user()->email)->firstOrFail();
        $student->load(['program.department', 'department']);

        $term = setting('current_term', 'First Semester');
        $session = setting('current_session', '2025/2026');
        $isUG = optional($student->department)->section === Sections::UG;
        $maxUnits = $isUG ? ($student->program->max_credit_units ?? 24) : null;

        $currentUnits = CourseRegistration::where('student_id', $student->id)
            ->where('term', $term)->where('session', $session)
            ->whereNull('dropped_at')
            ->join('subjects', 'subjects.id', '=', 'course_registrations.subject_id')
            ->sum('subjects.course_unit');

        $newCourses = Subject::whereIn('id', $request->subject_ids)->get();
        $newUnits = $newCourses->sum('course_unit');

        if ($maxUnits && ($currentUnits + $newUnits) > $maxUnits) {
            $remaining = $maxUnits - $currentUnits;
            return back()->with('error',
                "You have {$remaining} credit unit(s) remaining this semester. The selected course(s) total {$newUnits} units. Please choose course(s) with fewer credit units.");
        }

        foreach ($newCourses as $course) {
            CourseRegistration::firstOrCreate(
                [
                    'student_id' => $student->id,
                    'subject_id' => $course->id,
                    'term'       => $term,
                    'session'    => $session,
                ],
                [
                    'college_id'    => $student->college_id,
                    'is_carryover'  => false,
                    'registered_at' => now(),
                ]
            );
        }

        return back()->with('success', $newCourses->count() . ' course(s) registered successfully.');
    }

    public function drop(Request $request)
    {
        $request->validate([
            'registration_id' => 'required|exists:course_registrations,id',
        ]);

        $student = Student::where('email', auth()->user()->email)->firstOrFail();
        $reg = CourseRegistration::where('id', $request->registration_id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        if ($reg->is_carryover) {
            return back()->with('error', 'Carryover courses cannot be dropped.');
        }

        $reg->update(['dropped_at' => now()]);

        return back()->with('success', 'Course dropped successfully.');
    }

    private function autoRegisterCarryovers(Student $student, string $currentTerm, string $currentSession): void
    {
        $failedScores = Score::where('student_id', $student->id)
            ->where('grade', 'F')
            ->whereNotNull('transmitted_at')
            ->with('subject')
            ->get();

        foreach ($failedScores as $score) {
            $subject = $score->subject;
            if (!$subject) continue;

            $isSameSemesterType = $subject->semester === $currentTerm;
            if (!$isSameSemesterType) continue;

            $alreadyRegistered = CourseRegistration::where('student_id', $student->id)
                ->where('subject_id', $subject->id)
                ->where('term', $currentTerm)
                ->where('session', $currentSession)
                ->whereNull('dropped_at')
                ->exists();

            if ($alreadyRegistered) continue;

            $alreadyPassed = Score::where('student_id', $student->id)
                ->where('subject_id', $subject->id)
                ->where('grade', '!=', 'F')
                ->where('session', '!=', $score->session)
                ->whereNotNull('transmitted_at')
                ->exists();

            if ($alreadyPassed) continue;

            CourseRegistration::firstOrCreate(
                [
                    'student_id' => $student->id,
                    'subject_id' => $subject->id,
                    'term'       => $currentTerm,
                    'session'    => $currentSession,
                ],
                [
                    'college_id'    => $student->college_id,
                    'is_carryover'  => true,
                    'registered_at' => now(),
                ]
            );
        }
    }
}
