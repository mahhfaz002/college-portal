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
