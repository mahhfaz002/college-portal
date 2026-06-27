<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\StudentDocument;

class ApplicationCredentialController extends Controller
{
    public function show(Applicant $applicant)
    {
        $applicant->load(['firstChoice.department', 'secondChoice', 'admittedProgram']);

        $documents = StudentDocument::where('applicant_id', $applicant->id)->get()->keyBy('type');

        return view('admission.view_credentials', compact('applicant', 'documents'));
    }
}
