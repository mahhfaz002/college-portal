<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Program;
use Illuminate\Http\Request;

/**
 * Programs sit under a department; each carries its application / acceptance /
 * registration fees which drive admissions & finance in later phases.
 */
class ProgramController extends Controller
{
    public function index(Request $request)
    {
        $departments = Department::orderBy('name')->get();
        $selectedDept = $request->query('department_id');

        $programs = Program::with('department')
            ->when($selectedDept, fn ($q) => $q->where('department_id', $selectedDept))
            ->orderBy('name')->get();

        return view('programs.index', compact('departments', 'programs', 'selectedDept'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'department_id'    => 'required|exists:departments,id',
            'name'             => 'required|string|max:255',
            'acronym'          => 'nullable|string|max:20',
            'application_fee'  => 'nullable|numeric|min:0',
            'acceptance_fee'   => 'nullable|numeric|min:0',
            'registration_fee' => 'nullable|numeric|min:0',
            'level_system'     => 'nullable|string|max:50',
            'duration_years'   => 'nullable|integer|min:1|max:7',
            'program_type'     => 'required|in:UG,DIP,CERT',
            'levels'           => 'required|integer|min:1|max:8',
            'id_format'        => 'nullable|string|max:120',
        ]);

        // Default student-ID format if the MIS officer left it blank.
        $data['id_format'] = $data['id_format'] ?: '{acronym}/{year}/{type}/{program}/{serial}';

        Program::create($data);

        return back()->with('success', 'Course of study created.');
    }

    public function update(Request $request, Program $program)
    {
        $data = $request->validate([
            'department_id'    => 'required|exists:departments,id',
            'name'             => 'required|string|max:255',
            'acronym'          => 'nullable|string|max:20',
            'application_fee'  => 'nullable|numeric|min:0',
            'acceptance_fee'   => 'nullable|numeric|min:0',
            'registration_fee' => 'nullable|numeric|min:0',
            'level_system'     => 'nullable|string|max:50',
            'duration_years'   => 'nullable|integer|min:1|max:7',
            'program_type'     => 'required|in:UG,DIP,CERT',
            'levels'           => 'required|integer|min:1|max:8',
            'id_format'        => 'nullable|string|max:120',
        ]);

        $program->update($data);

        return back()->with('success', 'Course of study updated.');
    }

    public function destroy(Program $program)
    {
        $program->delete();

        return back()->with('success', 'Program deleted.');
    }
}
