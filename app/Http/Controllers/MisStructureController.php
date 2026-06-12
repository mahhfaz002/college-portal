<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Program;
use App\Support\Sections;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * MIS academic-structure builder. A department is placed in a section
 * (UG/DIP/CERT); each department holds one or more courses of study, and each
 * course of study declares how many levels it runs.
 */
class MisStructureController extends Controller
{
    public function index()
    {
        $departments = Department::with('programs')
            ->orderBy('section')->orderBy('name')->get()
            ->groupBy('section');

        return view('structure.index', [
            'departmentsBySection' => $departments,
            'sections'             => Sections::ALL,
            'sectionLabels'        => Sections::LABELS,
        ]);
    }

    /** Create a department (in a section) together with its courses of study. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'section'              => ['required', 'in:'.implode(',', Sections::ALL)],
            'department_name'      => 'required|string|max:255',
            'department_acronym'   => 'nullable|string|max:20',
            'courses'                       => 'required|array|min:1',
            'courses.*.name'                => 'required|string|max:255',
            'courses.*.acronym'             => 'nullable|string|max:20',
            'courses.*.levels'              => 'required|integer|min:1|max:8',
            'courses.*.application_fee'     => 'nullable|numeric|min:0',
            'courses.*.acceptance_fee'      => 'nullable|numeric|min:0',
            'courses.*.registration_fee'    => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($data) {
            $dept = Department::create([
                'college_id' => current_college_id(),
                'name'       => $data['department_name'],
                'acronym'    => $data['department_acronym'] ?? null,
                'section'    => $data['section'],
            ]);

            foreach ($data['courses'] as $c) {
                Program::create([
                    'college_id'       => current_college_id(),
                    'department_id'    => $dept->id,
                    'name'             => $c['name'],
                    'acronym'          => $c['acronym'] ?? null,
                    'program_type'     => $data['section'],   // section drives the ID type
                    'levels'           => $c['levels'],
                    'duration_years'   => max(1, (int) ceil(((int) $c['levels']) / 2)),
                    'application_fee'  => $c['application_fee'] ?? 0,
                    'acceptance_fee'   => $c['acceptance_fee'] ?? 0,
                    'registration_fee' => $c['registration_fee'] ?? 0,
                ]);
            }
        });

        return redirect()->route('structure.index')
            ->with('success', 'Department and its course(s) of study created.');
    }

    /** Add a course of study to an existing department. */
    public function addCourse(Request $request, Department $department)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'acronym'          => 'nullable|string|max:20',
            'levels'           => 'required|integer|min:1|max:8',
            'application_fee'  => 'nullable|numeric|min:0',
            'acceptance_fee'   => 'nullable|numeric|min:0',
            'registration_fee' => 'nullable|numeric|min:0',
        ]);

        Program::create([
            'college_id'       => current_college_id(),
            'department_id'    => $department->id,
            'name'             => $data['name'],
            'acronym'          => $data['acronym'] ?? null,
            'program_type'     => $department->section ?? Sections::UG,
            'levels'           => $data['levels'],
            'duration_years'   => max(1, (int) ceil(((int) $data['levels']) / 2)),
            'application_fee'  => $data['application_fee'] ?? 0,
            'acceptance_fee'   => $data['acceptance_fee'] ?? 0,
            'registration_fee' => $data['registration_fee'] ?? 0,
        ]);

        return back()->with('success', 'Course of study added.');
    }

    public function updateCourse(Request $request, Program $program)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'acronym'          => 'nullable|string|max:20',
            'levels'           => 'required|integer|min:1|max:8',
            'application_fee'  => 'nullable|numeric|min:0',
            'acceptance_fee'   => 'nullable|numeric|min:0',
            'registration_fee' => 'nullable|numeric|min:0',
        ]);

        $program->update($data);

        return back()->with('success', 'Course of study updated.');
    }

    public function destroyCourse(Program $program)
    {
        $program->delete();

        return back()->with('success', 'Course of study deleted.');
    }

    public function destroyDepartment(Department $department)
    {
        $department->programs()->delete();
        $department->delete();

        return back()->with('success', 'Department and its courses deleted.');
    }
}
