<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Applicant;
use App\Models\StudentDocument;
use Illuminate\Http\Request;

class ApplicationSubmissionController extends Controller
{
    public const REQUIRED_DOCS = [
        'jamb_result' => 'JAMB Result',
        'ssce'        => 'SSCE Certificate (WAEC / NECO / NABTEB)',
        'passport'    => 'Passport Photograph',
    ];

    public function show()
    {
        $applicant = Applicant::where('user_id', auth()->id())->latest()->first();
        abort_unless($applicant && $applicant->application_status === 'awaiting_documents', 403);

        $applicant->load(['firstChoice.department', 'secondChoice']);

        $existing = StudentDocument::where('applicant_id', $applicant->id)->get()->keyBy('type');

        return view('admission.submit_application', [
            'applicant' => $applicant,
            'required'  => self::REQUIRED_DOCS,
            'existing'  => $existing,
        ]);
    }

    public function store(Request $request)
    {
        $applicant = Applicant::where('user_id', auth()->id())->latest()->first();
        abort_unless($applicant && $applicant->application_status === 'awaiting_documents', 403);

        $request->validate([
            'docs.jamb_result' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'docs.ssce'        => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'docs.passport'    => 'required|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        foreach ((array) $request->file('docs') as $type => $file) {
            if (!$file) continue;
            $path = $file->store('documents/applications', config('filesystems.documents'));
            StudentDocument::updateOrCreate(
                ['applicant_id' => $applicant->id, 'type' => $type],
                [
                    'college_id'    => $applicant->college_id,
                    'student_id'    => null,
                    'label'         => self::REQUIRED_DOCS[$type] ?? $type,
                    'path'          => $path,
                    'original_name' => $file->getClientOriginalName(),
                ]
            );
        }

        $applicant->update([
            'application_status'     => 'submitted',
            'documents_submitted_at' => now(),
        ]);

        ActivityLog::record("Submitted application with documents: {$applicant->full_name}", 'application.submit');

        return redirect()->route('dashboard')
            ->with('success', 'Application submitted successfully. Your application is now under review by the admission committee.');
    }
}
