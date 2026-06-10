<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentAffairsCase;
use Illuminate\Http\Request;

/**
 * Student Affairs — log and track student welfare, disciplinary and complaint
 * cases. (Basic Phase 5 feature set; extendable later.)
 */
class StudentAffairsController extends Controller
{
    public function dashboard(Request $request)
    {
        $cases = StudentAffairsCase::latest()->get();
        $students = Student::orderBy('full_name')->get(['id', 'full_name']);

        $stats = [
            'open'        => $cases->where('status', 'open')->count(),
            'resolved'    => $cases->where('status', 'resolved')->count(),
            'disciplinary'=> $cases->where('category', 'disciplinary')->count(),
            'total'       => $cases->count(),
        ];

        return view('dashboards.student_affairs', compact('cases', 'students', 'stats'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id'   => 'nullable|exists:students,id',
            'student_name' => 'nullable|string|max:150',
            'category'     => 'required|in:disciplinary,welfare,complaint',
            'description'  => 'required|string|max:2000',
        ]);

        if (!empty($data['student_id'])) {
            $data['student_name'] = optional(Student::find($data['student_id']))->full_name;
        }
        $data['college_id'] = current_college_id();
        $data['logged_by']  = auth()->id();
        $data['status']     = 'open';
        StudentAffairsCase::create($data);

        return back()->with('success', 'Case logged.');
    }

    public function resolve(StudentAffairsCase $case)
    {
        $case->update(['status' => 'resolved']);
        return back()->with('success', 'Case marked resolved.');
    }

    public function destroy(StudentAffairsCase $case)
    {
        $case->delete();
        return back()->with('success', 'Case deleted.');
    }
}
