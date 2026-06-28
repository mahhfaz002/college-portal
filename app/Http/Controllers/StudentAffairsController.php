<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentAffairsCase;
use App\Models\StudentAffairsRegister;
use Illuminate\Http\Request;

class StudentAffairsController extends Controller
{
    public function dashboard(Request $request)
    {
        $cases = StudentAffairsCase::with('student')->latest()->get();
        $tab = $request->query('tab', 'cases');

        $stats = [
            'draft'            => $cases->where('status', 'draft')->count(),
            'open'             => $cases->where('status', 'open')->count(),
            'pending_registrar'=> $cases->where('status', 'pending_registrar')->count(),
            'pending_provost'  => $cases->where('status', 'pending_provost')->count(),
            'resolved'         => $cases->where('status', 'resolved')->count(),
            'total'            => $cases->count(),
        ];

        $registerCount = StudentAffairsRegister::count();
        $departments = Department::orderBy('name')->get();
        $programs = Program::orderBy('name')->get();

        $registerEntries = collect();
        if ($tab === 'register') {
            $registerEntries = StudentAffairsRegister::with('student.program', 'student.department')
                ->latest('registered_at')->get();
        }

        $students = collect();
        if ($tab === 'students') {
            $query = Student::with(['program', 'department'])->orderBy('full_name');
            if ($request->filled('program_id')) {
                $query->where('program_id', $request->query('program_id'));
            }
            if ($request->filled('department_id')) {
                $query->where('department_id', $request->query('department_id'));
            }
            if ($request->filled('level')) {
                $query->where('level', $request->query('level'));
            }
            $students = $query->get();

            $registeredIds = StudentAffairsRegister::pluck('student_id')->toArray();
            $students = $students->map(function ($s) use ($registeredIds) {
                $s->sa_registered = in_array($s->id, $registeredIds);
                return $s;
            });
        }

        return view('dashboards.student_affairs', compact(
            'cases', 'stats', 'tab', 'registerCount', 'departments', 'programs',
            'registerEntries', 'students'
        ));
    }

    /**
     * Typeahead search across THIS college's students (the global CollegeScope
     * keeps it strictly within the logged-in user's college — no leakage).
     */
    public function searchStudents(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $students = Student::with(['program', 'department'])
            ->where(function ($w) use ($q) {
                $w->where('full_name', 'like', "%{$q}%")
                  ->orWhere('registration_number', 'like', "%{$q}%")
                  ->orWhere('admission_number', 'like', "%{$q}%");
            })
            ->orderBy('full_name')->limit(20)->get();

        return response()->json($students->map(fn ($s) => [
            'id'         => $s->id,
            'name'       => $s->full_name,
            'reg'        => $s->registration_number ?? $s->admission_number ?? '—',
            'department' => optional($s->department)->name,
            'program'    => optional($s->program)->name,
            'level'      => $s->level,
        ]));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_ids'    => 'required|array|min:1',
            'student_ids.*'  => 'integer',
            'category'       => 'required|in:disciplinary,welfare,complaint',
            'description'    => 'required|string|max:2000',
            'recommendation' => 'nullable|string|max:2000',
        ]);

        // Resolve through the college-scoped query so only this college's
        // students are ever attached to a case (drops any foreign id).
        $students = Student::whereIn('id', $data['student_ids'])->get();
        abort_if($students->isEmpty(), 422, 'Select at least one valid student.');

        StudentAffairsCase::create([
            'college_id'     => current_college_id(),
            'student_id'     => $students->first()->id,                 // primary (back-compat)
            'student_name'   => $students->pluck('full_name')->implode(', '),
            'student_ids'    => $students->pluck('id')->values()->all(),
            'category'       => $data['category'],
            'description'    => $data['description'],
            'recommendation' => $data['recommendation'] ?? null,
            'logged_by'      => auth()->id(),
            'status'         => 'open',
        ]);

        return back()->with('success', 'Case logged.');
    }

    public function submitToRegistrar(StudentAffairsCase $case)
    {
        abort_unless(in_array($case->status, ['open', 'draft']), 403);
        $case->update([
            'status'                   => 'pending_registrar',
            'forwarded_to_registrar_at' => now(),
        ]);

        return back()->with('success', 'Case forwarded to the Registrar for review.');
    }

    public function registerStudent(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|integer',
            'checklist'  => 'required|array',
            'notes'      => 'nullable|string|max:2000',
        ]);

        // College-scoped lookup → a student from another college resolves to null.
        $student = Student::find($data['student_id']);
        if (!$student) {
            return back()->with('error', 'Student not found in your college.');
        }

        StudentAffairsRegister::updateOrCreate(
            ['student_id' => $student->id, 'college_id' => current_college_id()],
            [
                'registered_by' => auth()->id(),
                'checklist'     => $data['checklist'],
                'notes'         => $data['notes'],
                'registered_at' => now(),
            ]
        );

        return back()->with('success', "{$student->full_name} registered in the Student Affairs register.");
    }

    public function editRegister(Request $request, StudentAffairsRegister $entry)
    {
        $data = $request->validate([
            'checklist' => 'required|array',
            'notes'     => 'nullable|string|max:2000',
        ]);

        $entry->update([
            'checklist' => $data['checklist'],
            'notes'     => $data['notes'],
        ]);

        return back()->with('success', 'Register entry updated.');
    }

    public function destroyRegister(StudentAffairsRegister $entry)
    {
        $entry->delete();
        return back()->with('success', 'Register entry removed.');
    }

    public function resolve(StudentAffairsCase $case)
    {
        $case->update(['status' => 'resolved', 'resolved_by' => auth()->id(), 'resolution_date' => now()]);
        return back()->with('success', 'Case marked resolved.');
    }

    public function destroy(StudentAffairsCase $case)
    {
        $case->delete();
        return back()->with('success', 'Case deleted.');
    }
}
