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

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id'      => 'nullable|exists:students,id',
            'student_name'    => 'nullable|string|max:150',
            'category'        => 'required|in:disciplinary,welfare,complaint',
            'description'     => 'required|string|max:2000',
            'recommendation'  => 'nullable|string|max:2000',
            'penalty_type'    => 'nullable|string|max:255',
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
            'registration_number' => 'required|string',
            'checklist'           => 'required|array',
            'notes'               => 'nullable|string|max:2000',
        ]);

        $student = Student::where('registration_number', $data['registration_number'])->first();
        if (!$student) {
            return back()->with('error', 'Student not found with that registration number.');
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
