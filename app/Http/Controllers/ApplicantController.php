<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Applicant;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Support\Sections;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ApplicantController extends Controller
{
    /** Public application form (Sections A–D, program choices per college). */
    public function showForm()
    {
        $colleges = \App\Models\College::where('is_active', true)->orderBy('name')->get();
        $college  = $colleges->first();

        $programs = $college
            ? \App\Models\Program::withoutGlobalScopes()
                ->where('college_id', $college->id)->with('department')->orderBy('name')->get()
            : collect();

        return view('admission.form', compact('colleges', 'college', 'programs'));
    }

    /**
     * Handle a public application (Phase 2): create the applicant, raise the
     * application-fee invoice for the first-choice program, and send the
     * candidate to the payment gateway. The account is created only AFTER the
     * application fee is confirmed (see GatewayPaymentController).
     */
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'college_id'    => 'required|exists:colleges,id',
            // Section A — applicant bio
            'first_name'    => 'required|string|max:100',
            'surname'       => 'required|string|max:100',
            'other_name'    => 'nullable|string|max:100',
            'address'       => 'required|string|max:255',
            'phone'         => 'required|string|max:50',
            'email'         => 'required|email|max:255|unique:users,email',
            'date_of_birth' => 'required|date|before:today',
            'gender'        => 'required|string|max:20',
            // Program choices
            'first_choice_program_id'  => 'required|exists:programs,id',
            'second_choice_program_id' => 'nullable|exists:programs,id|different:first_choice_program_id',
            // Section B — parent / guardian
            'guardian_name'         => 'required|string|max:255',
            'guardian_relationship' => 'required|string|max:100',
            'guardian_phone'        => 'required|string|max:50',
            'guardian_email'        => 'nullable|email|max:255',
            'guardian_address'      => 'nullable|string|max:255',
            'guardian_occupation'   => 'nullable|string|max:150',
            // Section C — O'Level results
            'exam_type'            => 'required|in:WAEC,NECO',
            'exam_year'            => 'required|digits:4',
            'results'              => 'required|array|min:5',
            'results.*.subject'    => 'nullable|string|max:60',
            'results.*.grade'      => 'nullable|in:A1,B2,B3,C4,C5,C6,D7,E8,F9',
            // Section D — passport
            'passport'             => 'required|file|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Keep only fully-filled result rows (subject + grade).
        $results = collect($validated['results'] ?? [])
            ->map(fn ($r) => ['subject' => strtoupper(trim($r['subject'] ?? '')), 'grade' => $r['grade'] ?? null])
            ->filter(fn ($r) => $r['subject'] !== '' && $r['grade'])
            ->values()->all();

        $program = \App\Models\Program::withoutGlobalScopes()->findOrFail($validated['first_choice_program_id']);

        // Guard: chosen program must belong to the chosen college.
        abort_unless((int) $program->college_id === (int) $validated['college_id'], 422, 'Program does not belong to the selected college.');

        $pp = $request->file('passport');
        $passport = 'data:'.$pp->getMimeType().';base64,'.base64_encode(file_get_contents($pp->getRealPath()));

        $applicant = Applicant::create([
            'college_id'   => $validated['college_id'],
            'first_name'   => $validated['first_name'],
            'surname'      => $validated['surname'],
            'other_name'   => $validated['other_name'] ?? null,
            'full_name'    => trim($validated['surname'].' '.$validated['first_name'].' '.($validated['other_name'] ?? '')),
            'address'      => $validated['address'],
            'phone'        => $validated['phone'],
            'email'        => $validated['email'],
            'date_of_birth'=> $validated['date_of_birth'],
            'gender'       => $validated['gender'],
            'first_choice_program_id'  => $validated['first_choice_program_id'],
            'second_choice_program_id' => $validated['second_choice_program_id'] ?? null,
            'guardian_name'         => $validated['guardian_name'],
            'guardian_relationship' => $validated['guardian_relationship'],
            'guardian_phone'        => $validated['guardian_phone'],
            'guardian_email'        => $validated['guardian_email'] ?? null,
            'guardian_address'      => $validated['guardian_address'] ?? null,
            'guardian_occupation'   => $validated['guardian_occupation'] ?? null,
            'exam_type'      => $validated['exam_type'],
            'exam_year'      => $validated['exam_year'],
            'olevel_results' => $results,
            'passport'     => $passport,
            'status'       => 'pending',
            'application_status' => 'pending_payment',
            'payment_status'     => 'unpaid',
            // legacy not-null columns kept satisfied
            'parent_name'  => $validated['guardian_name'],
            'parent_phone' => $validated['guardian_phone'],
            'parent_email' => $validated['guardian_email'] ?? $validated['email'],
            'desired_class'=> $program->name,
        ]);

        // Raise the application-fee invoice and head to the gateway.
        $invoice = \App\Models\Invoice::create([
            'college_id'  => $validated['college_id'],
            'applicant_id'=> $applicant->id,
            'program_id'  => $program->id,
            'purpose'     => 'application_fee',
            'description' => 'Application fee — '.$program->name,
            'amount'      => $program->application_fee,
            'payer_email' => $applicant->email,
            'status'      => 'pending',
            'reference'   => \App\Services\PaystackService::reference('APP'),
        ]);

        return redirect()->route('payments.initialize', ['invoice' => $invoice->id]);
    }

    /** ICT: show the admission application form. */
    public function createApplication()
    {
        return view('admission.apply', [
            'sections' => Sections::ALL,
            'classes'  => SchoolClass::orderBy('section')->orderBy('name')->get(),
        ]);
    }

    /**
     * ICT: store a new admission application (section + class + documents).
     * FSLC required for secondary, junior WAEC for senior secondary.
     */
    public function storeApplication(Request $request)
    {
        $isSenior = $request->input('section') === Sections::SENIOR;
        $isSecondary = in_array($request->input('section'), [Sections::JUNIOR, Sections::SENIOR], true);

        $validated = $request->validate([
            'full_name'      => 'required|string|max:255',
            'address'        => 'required|string|max:255',
            'date_of_birth'  => 'required|date|before:today',
            'gender'         => 'required|string|max:20',
            'parent_name'    => 'required|string|max:255',
            'parent_phone'   => 'required|string|max:50',
            'parent_email'   => 'required|email|max:255',
            'section'        => ['required', Rule::in(Sections::ALL)],
            'desired_class'  => 'required|string|max:100',
            'passport'           => 'required|file|mimes:jpg,jpeg,png|max:2048',
            'birth_certificate'  => 'required|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'indigene_letter'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'fslc'               => ($isSecondary ? 'required' : 'nullable').'|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'junior_waec'        => ($isSenior ? 'required' : 'nullable').'|file|mimes:pdf,jpg,jpeg,png|max:4096',
        ]);

        $data = collect($validated)->except(['passport', 'birth_certificate', 'indigene_letter', 'fslc', 'junior_waec'])->all();
        $data['status'] = 'pending';

        $pp = $request->file('passport');
        $data['passport'] = 'data:'.$pp->getMimeType().';base64,'.base64_encode(file_get_contents($pp->getRealPath()));
        $disk = config('filesystems.documents');
        $data['birth_cert_path'] = $request->file('birth_certificate')->store('documents/certificates', $disk);
        if ($request->hasFile('indigene_letter')) $data['indigene_letter_path'] = $request->file('indigene_letter')->store('documents/indigene', $disk);
        if ($request->hasFile('fslc')) $data['fslc_path'] = $request->file('fslc')->store('documents/fslc', $disk);
        if ($request->hasFile('junior_waec')) $data['junior_waec_path'] = $request->file('junior_waec')->store('documents/jwaec', $disk);

        $applicant = Applicant::create($data);
        ActivityLog::record("Created admission application for {$applicant->full_name}", 'admission.apply');

        return redirect()->route('admission.apply')->with('success', "Application for {$applicant->full_name} submitted to the Registrar for approval.");
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
            'section'          => $applicant->section ?? Sections::fromClassName($applicant->desired_class),
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
