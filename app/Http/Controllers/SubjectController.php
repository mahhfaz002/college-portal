<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Program;
use App\Models\Subject;
use Illuminate\Http\Request;

/**
 * Courses (formerly Subjects). The Registrar picks a department -> program, then
 * lists / adds / edits / deletes courses, each with a title, code and unit.
 */
class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $departments  = Department::orderBy('name')->get();
        $selectedDept = $request->query('department_id');
        $selectedProg = $request->query('program_id');

        $programs = Program::when($selectedDept, fn ($q) => $q->where('department_id', $selectedDept))
            ->orderBy('name')->get();

        $subjects = Subject::with(['department', 'program', 'teachers'])
            ->when($selectedDept, fn ($q) => $q->where('department_id', $selectedDept))
            ->when($selectedProg, fn ($q) => $q->where('program_id', $selectedProg))
            ->orderBy('name')->get();

        return view('subjects.index', compact(
            'subjects', 'departments', 'programs', 'selectedDept', 'selectedProg'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'course_code'   => 'nullable|string|max:50',
            'course_unit'   => 'nullable|integer|min:0|max:12',
            'department_id' => 'nullable|exists:departments,id',
            'program_id'    => 'nullable|exists:programs,id',
        ]);

        Subject::create($data);

        return back()->with('success', 'Course added successfully!');
    }

    public function update(Request $request, Subject $subject)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'course_code'   => 'nullable|string|max:50',
            'course_unit'   => 'nullable|integer|min:0|max:12',
            'department_id' => 'nullable|exists:departments,id',
            'program_id'    => 'nullable|exists:programs,id',
        ]);

        $subject->update($data);

        return back()->with('success', 'Course updated!');
    }

    public function destroy(Subject $subject)
    {
        $subject->delete();

        return back()->with('success', 'Course removed!');
    }
}
