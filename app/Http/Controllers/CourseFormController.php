<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Subject;

/**
 * The student's own course (registration) form: the courses for THEIR programme
 * and level, pulled live from the database — so any add / edit / removal of a
 * course by the Academic Secretary reflects immediately, and a student never
 * sees another department's or level's courses.
 */
class CourseFormController extends Controller
{
    /** Web view of the course form (with a Download PDF button). */
    public function show()
    {
        return view('student.course_form', $this->data());
    }

    /** Downloadable PDF of the same course form. */
    public function pdf()
    {
        $data = $this->data();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('student.course_form_pdf', $data);

        $reg = preg_replace('/[^A-Za-z0-9]+/', '_', (string) ($data['student']->registration_number ?: 'course-form'));

        return $pdf->download("Course_Form_{$reg}.pdf");
    }

    /**
     * Build the form payload. Courses are scoped to the student's OWN programme
     * AND level (a programme belongs to exactly one department, so this can never
     * leak another department's courses), grouped by semester for the total.
     */
    private function data(): array
    {
        $student = Student::where('email', auth()->user()->email)->firstOrFail();
        $student->loadMissing(['program.department', 'department']);

        $courses = Subject::where('program_id', $student->program_id)
            ->where('level', $student->level)
            ->orderBy('semester')
            ->orderBy('course_code')
            ->orderBy('name')
            ->get(['id', 'name', 'course_code', 'course_unit', 'semester', 'level']);

        $bySemester = $courses->groupBy(fn ($c) => $c->semester ?: 'First Semester');

        return [
            'student'     => $student,
            'college'     => \App\Models\College::withoutGlobalScopes()->find($student->college_id),
            'bySemester'  => $bySemester,
            'totalUnits'  => (int) $courses->sum('course_unit'),
            'session'     => setting('current_session', date('Y').'/'.(date('Y') + 1)),
        ];
    }
}
