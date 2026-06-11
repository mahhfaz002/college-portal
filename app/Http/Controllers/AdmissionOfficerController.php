<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Program;
use Illuminate\Http\Request;

/**
 * Admission Officer — shares the applicant approval queue with the Registrar.
 * Sees paid applications awaiting a decision, can approve (offer admission) or
 * reject, and reviews the accepted / rejected lists. Offer/decline actions are
 * handled by AdmissionWorkflowController (manage_admissions capability).
 */
class AdmissionOfficerController extends Controller
{
    public function dashboard(Request $request)
    {
        $paid = Applicant::with(['firstChoice.department', 'secondChoice', 'admittedProgram'])
            ->where('payment_status', 'paid');

        $queue    = (clone $paid)->whereIn('application_status', ['submitted', 'offer_rejected'])->latest()->get();
        $accepted = (clone $paid)->whereIn('application_status', ['admitted', 'accepted', 'registered'])->latest()->get();
        $rejected = (clone $paid)->whereIn('application_status', ['rejected'])->latest()->get();

        $programs = Program::with('department')->orderBy('name')->get();

        $stats = [
            'queue'    => $queue->count(),
            'accepted' => $accepted->count(),
            'rejected' => $rejected->count(),
        ];

        return view('dashboards.admission_officer', compact('queue', 'accepted', 'rejected', 'programs', 'stats'));
    }
}
