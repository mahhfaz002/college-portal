<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Applicant;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApplicantController extends Controller
{
    /** Public application form. */
    public function showForm()
    {
        return view('admission.form');
    }

    /** Handle a public application submission. */
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'full_name'         => 'required|string|max:255',
            'date_of_birth'     => 'required|date',
            'gender'            => 'required|string|max:20',
            'parent_name'       => 'required|string|max:255',
            'parent_phone'      => 'required|string|max:50',
            'parent_email'      => 'required|email|max:255',
            'desired_class'     => 'required|string|max:100',
            'passport'          => 'required|file|mimes:jpg,jpeg,png|max:2048',
            'birth_certificate' => 'required|file|mimes:pdf,jpg,jpeg|max:2048',
            'indigene_letter'   => 'nullable|file|mimes:pdf,jpg,jpeg|max:2048',
        ]);

        $data = collect($validated)->except(['passport', 'birth_certificate', 'indigene_letter'])->all();
        $data['status'] = 'pending';

        // Passport kept as base64 so it survives deploys (no object storage).
        $pp = $request->file('passport');
        $data['passport'] = 'data:'.$pp->getMimeType().';base64,'.base64_encode(file_get_contents($pp->getRealPath()));

        // Other documents on the public disk (PDFs etc.).
        $data['birth_cert_path'] = $request->file('birth_certificate')->store('documents/certificates');
        if ($request->hasFile('indigene_letter')) {
            $data['indigene_letter_path'] = $request->file('indigene_letter')->store('documents/indigene');
        }

        Applicant::create($data);

        return back()->with('success', 'Application submitted successfully! Please expect our response via your email and/or phone contact.');
    }

    /** Registrar review panel. */
    public function index()
    {
        $applicants = Applicant::latest()->get();
        return view('admission.admin', compact('applicants'));
    }

    /**
     * Approve = admit: create a Student record with a unique admission number.
     */
    public function approve($id)
    {
        $applicant = Applicant::findOrFail($id);

        if ($applicant->status === 'admitted') {
            return back()->with('error', "{$applicant->full_name} has already been admitted.");
        }

        $admissionNumber = $this->generateAdmissionNumber();

        $student = Student::create([
            'full_name'        => $applicant->full_name,
            'admission_number' => $admissionNumber,
            'class_arm'        => $applicant->desired_class,
            'parent_phone'     => $applicant->parent_phone,
            'email'            => $applicant->parent_email,
            'fees_balance'     => 0,
            'photo'            => $applicant->passport, // base64 carries over to ID card
        ]);

        $applicant->update(['status' => 'admitted', 'admission_number' => $admissionNumber]);

        ActivityLog::record("Admitted applicant {$applicant->full_name} as {$admissionNumber}", 'admission.approve');

        return back()->with('success', "{$applicant->full_name} admitted. Admission No: {$admissionNumber}. Student record created.");
    }

    public function reject(Request $request, $id)
    {
        $applicant = Applicant::findOrFail($id);
        $applicant->update([
            'status' => 'rejected',
            'reason' => $request->input('reason'),
        ]);
        ActivityLog::record("Rejected applicant: {$applicant->full_name}", 'admission.reject');

        return back()->with('success', "{$applicant->full_name}'s application was rejected.");
    }

    /**
     * PREFIX/YEAR/SEQ — e.g. MAH/2026/014. Unique across students.
     */
    private function generateAdmissionNumber(): string
    {
        $prefix = Str::upper(setting('admission_prefix', setting('school_acronym', 'SCH')));
        $year = date('Y');

        $n = Student::count() + 1;
        do {
            $seq = str_pad((string) $n, 3, '0', STR_PAD_LEFT);
            $number = "{$prefix}/{$year}/{$seq}";
            $n++;
        } while (Student::where('admission_number', $number)->exists());

        return $number;
    }
}
