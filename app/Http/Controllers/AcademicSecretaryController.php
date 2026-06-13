<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Program;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Academic Secretary screens that read/curate the academic structure built
 * elsewhere (departments & courses of study by MIS; courses by the secretary's
 * own Create-Courses builder):
 *   - coursesList : read-only list of created courses, cascade-filtered.
 *   - departments : read-only browse of departments → courses of study → courses.
 *   - assign      : course-centric assignment of courses to lecturers (batch).
 *   - staff       : lecturer-centric view of teaching staff + their courses,
 *                   with an assign-course action.
 *
 * Teaching staff = lecturers + HODs (assistant HODs), scoped by department.
 */
class AcademicSecretaryController extends Controller
{
    private const TEACHING_ROLES = ['lecturer', 'hod', 'assistant_hod'];

    /** Programmes mapped for the cascading selectors (type → dept → programme → level). */
    private function programsForJs()
    {
        return Program::with('department')->orderBy('name')->get()->map(fn ($p) => [
            'id'        => $p->id,
            'name'      => $p->name,
            'type'      => $p->program_type ?? 'UG',
            'levels'    => (int) ($p->levels ?: 1),
            'dept_id'   => $p->department_id,
            'dept_name' => $p->department->name ?? '',
        ])->values();
    }

    /** Courses mapped for client-side filtering/listing. */
    private function subjectsForJs()
    {
        return Subject::whereNotNull('program_id')
            ->orderBy('level')->orderBy('course_code')
            ->get(['id', 'name', 'course_code', 'course_unit', 'department_id', 'program_id', 'level', 'semester'])
            ->map(fn ($s) => [
                'id'          => $s->id,
                'name'        => $s->name,
                'course_code' => $s->course_code,
                'course_unit' => $s->course_unit,
                'dept_id'     => $s->department_id,
                'program_id'  => $s->program_id,
                'level'       => (string) $s->level,
                'semester'    => $s->semester,
            ])->values();
    }

    /** Teaching staff mapped for the dropdowns/lists. */
    private function lecturersForJs()
    {
        return User::whereIn('role', self::TEACHING_ROLES)
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'department_id'])
            ->map(fn ($u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'role'       => $u->role,
                'dept_id'    => $u->department_id,
            ])->values();
    }

    /** subject_id => [user_id, ...] for the current assignments. */
    private function assignmentsMap(): array
    {
        return \Illuminate\Support\Facades\DB::table('subject_teacher')
            ->get()->groupBy('subject_id')
            ->map(fn ($rows) => $rows->pluck('user_id')->all())
            ->toArray();
    }

    /** REQ 1 — read-only list of created courses with the cascade filter. */
    public function coursesList()
    {
        return view('academic.courses', [
            'programs'    => $this->programsForJs(),
            'departments' => Department::orderBy('name')->get(['id', 'name', 'section']),
            'subjects'    => $this->subjectsForJs(),
            'semesters'   => \App\Support\Semesters::ALL,
        ]);
    }

    /** REQ 2 — browse departments → courses of study → courses (read-only). */
    public function departments()
    {
        $departments = Department::orderBy('name')->get()->map(function ($d) {
            $programs = Program::where('department_id', $d->id)->orderBy('name')->get()->map(function ($p) {
                $courses = Subject::where('program_id', $p->id)
                    ->orderBy('level')->orderBy('course_code')
                    ->get(['id', 'name', 'course_code', 'course_unit', 'level']);
                return [
                    'id'      => $p->id,
                    'name'    => $p->name,
                    'type'    => $p->program_type ?? 'UG',
                    'courses' => $courses,
                ];
            });
            return [
                'id'       => $d->id,
                'name'     => $d->name,
                'acronym'  => $d->acronym,
                'section'  => $d->section,
                'programs' => $programs,
            ];
        });

        return view('academic.departments', ['departments' => $departments]);
    }

    /** REQ 3 — course-centric assignment screen (batch). */
    public function assign()
    {
        return view('academic.assign', [
            'programs'    => $this->programsForJs(),
            'departments' => Department::orderBy('name')->get(['id', 'name', 'section']),
            'subjects'    => $this->subjectsForJs(),
            'lecturers'   => $this->lecturersForJs(),
            'assignments' => $this->assignmentsMap(),
        ]);
    }

    /** REQ 4 — lecturer-centric staff screen with assign-course action. */
    public function staff()
    {
        $lecturers = User::whereIn('role', self::TEACHING_ROLES)
            ->with(['subjects.program', 'subjects.department', 'departmentModel'])
            ->orderBy('name')->get();

        return view('academic.staff', [
            'lecturers'   => $lecturers,
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'programs'    => $this->programsForJs(),
            'subjects'    => $this->subjectsForJs(),
        ]);
    }
}
