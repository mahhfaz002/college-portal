<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

/**
 * Registrar manages the academic structure: departments (and their programs).
 * All queries are college-scoped automatically via the Department model.
 */
class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::withCount('programs')->orderBy('name')->get();

        return view('departments.index', compact('departments'));
    }

    /**
     * Read-only browse: departments → courses of study → courses. Used by
     * oversight roles (e.g. the Proprietor) who view but never edit structure.
     * Reuses the academic departments accordion view.
     */
    public function browse()
    {
        $departments = Department::orderBy('name')->get()->map(function ($d) {
            $programs = \App\Models\Program::where('department_id', $d->id)->orderBy('name')->get()->map(function ($p) {
                $courses = \App\Models\Subject::where('program_id', $p->id)
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

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'acronym'     => 'nullable|string|max:20',
            'description' => 'nullable|string|max:255',
        ]);

        Department::create($data);

        return back()->with('success', 'Department created.');
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'acronym'     => 'nullable|string|max:20',
            'description' => 'nullable|string|max:255',
        ]);

        $department->update($data);

        return back()->with('success', 'Department updated.');
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return back()->with('success', 'Department deleted.');
    }
}
