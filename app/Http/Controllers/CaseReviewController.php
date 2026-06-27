<?php

namespace App\Http\Controllers;

use App\Models\StudentAffairsCase;
use Illuminate\Http\Request;

class CaseReviewController extends Controller
{
    public function registrarCases()
    {
        $cases = StudentAffairsCase::with('student', 'loggedByUser')
            ->whereIn('status', ['pending_registrar', 'pending_provost', 'resolved', 'forwarded_to_student'])
            ->latest()->get();

        return view('affairs.registrar_cases', compact('cases'));
    }

    public function registrarResolve(Request $request, StudentAffairsCase $case)
    {
        $data = $request->validate([
            'registrar_resolution' => 'required|string|max:2000',
        ]);

        $case->update([
            'registrar_resolution' => $data['registrar_resolution'],
            'status'               => 'resolved',
            'resolved_by'          => auth()->id(),
            'resolution_date'      => now(),
            'final_resolution'     => $data['registrar_resolution'],
        ]);

        return back()->with('success', 'Case resolved.');
    }

    public function forwardToProvost(StudentAffairsCase $case)
    {
        abort_unless($case->status === 'pending_registrar', 403);
        $case->update([
            'status'                  => 'pending_provost',
            'forwarded_to_provost_at' => now(),
        ]);

        return back()->with('success', 'Case forwarded to the Provost.');
    }

    public function provostCases()
    {
        $cases = StudentAffairsCase::with('student', 'loggedByUser')
            ->whereIn('status', ['pending_provost', 'resolved'])
            ->where(function ($q) {
                $q->whereNotNull('forwarded_to_provost_at');
            })
            ->latest()->get();

        return view('affairs.provost_cases', compact('cases'));
    }

    public function provostResolve(Request $request, StudentAffairsCase $case)
    {
        $data = $request->validate([
            'provost_resolution' => 'required|string|max:2000',
        ]);

        $case->update([
            'provost_resolution' => $data['provost_resolution'],
            'final_resolution'   => $data['provost_resolution'],
            'status'             => 'resolved',
            'resolved_by'        => auth()->id(),
            'resolution_date'    => now(),
        ]);

        return back()->with('success', 'Case resolved by Provost.');
    }

    public function notifyStudent(StudentAffairsCase $case)
    {
        abort_unless($case->status === 'resolved', 403);
        $case->update([
            'status'              => 'forwarded_to_student',
            'student_notified_at' => now(),
        ]);

        return back()->with('success', 'Resolution forwarded to the student.');
    }

    /** Printable disciplinary notice for the student (owner only). */
    public function printForStudent(StudentAffairsCase $case)
    {
        $student = \App\Models\Student::where('email', auth()->user()->email)->first();
        abort_unless($student && $case->student_id === $student->id && $case->student_notified_at, 403);

        $case->load('student');
        $college = \App\Models\College::withoutGlobalScopes()->find($case->college_id);

        return view('affairs.case_print', compact('case', 'college', 'student'));
    }
}
