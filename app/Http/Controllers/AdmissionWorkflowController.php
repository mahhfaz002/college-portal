<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Applicant;
use App\Models\College;
use App\Models\Invoice;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Phase 3 admission workflow:
 *   submitted → (registrar offers) admitted → (applicant accepts) → acceptance
 *   fee → admission letter + registration fee → (paid) Student created, reg
 *   number assigned, dashboard unlocked → document upload → HOD approval →
 *   fully registered.
 */
class AdmissionWorkflowController extends Controller
{
    /** Required registration documents (key => label). */
    public const REQUIRED_DOCS = [
        'passport'        => 'Passport Photograph',
        'admission_letter'=> 'Admission Letter (printed/downloaded)',
        'receipt'         => 'Payment Receipt',
        'ssce'            => 'WAEC / NECO / SSCE Result',
        'fslc'            => 'First School Leaving Certificate (FSLC)',
        'indigene'        => 'Indigene Letter',
        'birth_cert'      => 'Birth Certificate',
        'acceptance_form' => 'Signed Admission Acceptance Form',
        'diploma'         => 'Diploma Certificate (Direct Entry only)',
    ];

    /* ---------------------------------------------------------------------
     | REGISTRAR — offer / decline admission
     |---------------------------------------------------------------------*/

    /**
     * Registrar admission review panel. Only applicants who have paid the
     * application fee AND completed their application (uploaded the required
     * documents → status 'submitted', or anything downstream) are shown.
     * Applicants still at 'awaiting_documents' / 'pending_payment' are hidden
     * from the registrar and admission officer until they finish.
     */
    public function reviewPanel()
    {
        $applicants = Applicant::with(['firstChoice.department', 'secondChoice', 'admittedProgram'])
            ->where('payment_status', 'paid')
            ->whereIn('application_status', self::REVIEWABLE_STATUSES)
            ->latest()->get();

        // Which of these applicants have already paid their registration fee —
        // a paid registration locks the admission (no more "change admission").
        $registeredIds = Invoice::whereIn('applicant_id', $applicants->pluck('id'))
            ->where('purpose', 'registration_fee')
            ->where('status', 'paid')
            ->pluck('applicant_id')->all();

        $programs = Program::with('department')->orderBy('name')->get();

        return view('admission.review', compact('applicants', 'programs', 'registeredIds'));
    }

    /** Statuses a completed application can be in (visible to registrar/officer). */
    public const REVIEWABLE_STATUSES = [
        'submitted', 'admitted', 'accepted', 'registered', 'offer_rejected', 'rejected',
    ];

    /** Offer admission into a chosen program (department derived from it). */
    public function offer(Request $request, Applicant $applicant)
    {
        $data = $request->validate([
            'program_id' => 'required|exists:programs,id',
        ]);

        $program = Program::findOrFail($data['program_id']);

        $applicant->update([
            'admitted_program_id' => $program->id,
            'application_status'  => 'admitted',
            'status'              => 'admitted',
            'admission_response'  => null,
            'admission_number'    => $applicant->admission_number ?: $this->admissionNumber($applicant),
        ]);

        ActivityLog::record("Offered admission to {$applicant->full_name} ({$program->name})", 'admission.offer');

        return back()->with('success', "Admission offered to {$applicant->full_name} for {$program->name}.");
    }

    /**
     * Change a previously offered admission to a different programme. Only
     * possible while the student has NOT paid the registration fee; once paid,
     * the admission is locked and the student must instead apply for a change
     * of course from their own dashboard.
     */
    public function changeAdmission(Request $request, Applicant $applicant)
    {
        // Locked once the registration fee is settled.
        if ($this->registrationPaid($applicant)) {
            return back()->with('error',
                "{$applicant->full_name} has already paid the registration fee and is registered. "
                ."The admission can no longer be changed here — the student should apply for a Change of Course from their dashboard.");
        }

        abort_unless(in_array($applicant->application_status, ['admitted', 'accepted'], true), 403,
            'Only an offered (not yet registered) admission can be changed.');

        $data = $request->validate(['program_id' => 'required|exists:programs,id']);
        $program = Program::findOrFail($data['program_id']);
        abort_unless((int) $program->college_id === (int) $applicant->college_id, 422);

        $applicant->update([
            'admitted_program_id' => $program->id,
            'application_status'  => 'admitted',
            'status'              => 'admitted',
            'admission_response'  => null,
        ]);

        ActivityLog::record("Changed admission for {$applicant->full_name} → {$program->name}", 'admission.change');

        return back()->with('success', "Admission changed to {$program->name} for {$applicant->full_name}.");
    }

    /** Decline an application. */
    public function decline(Request $request, Applicant $applicant)
    {
        $applicant->update([
            'application_status' => 'rejected',
            'status'             => 'rejected',
            'reason'             => $request->input('reason'),
        ]);
        ActivityLog::record("Declined application: {$applicant->full_name}", 'admission.decline');

        return back()->with('success', "{$applicant->full_name}'s application was declined.");
    }

    /* ---------------------------------------------------------------------
     | APPLICANT — accept / reject offer, reapply
     |---------------------------------------------------------------------*/

    /** Accept the offer → raise the acceptance-fee invoice and pay. */
    public function accept(Request $request)
    {
        $applicant = $this->myApplicant();
        abort_unless($applicant && $applicant->application_status === 'admitted', 403);

        // Already paid — don't raise (or charge) it again.
        if ($this->acceptancePaid($applicant)) {
            return redirect()->route('dashboard')->with('success', 'Your acceptance fee is already paid.');
        }

        $program = $applicant->admittedProgram;
        $applicant->update(['admission_response' => 'accepted']);

        // Idempotent: reuse an existing pending acceptance invoice instead of
        // creating a duplicate every time the applicant re-clicks "Accept".
        $invoice = Invoice::where('applicant_id', $applicant->id)
            ->where('purpose', 'acceptance_fee')
            ->where('status', 'pending')
            ->latest()->first()
            ?: $this->raiseInvoice($applicant, 'acceptance_fee',
                'Admission acceptance fee — '.$program->name, $program->acceptance_fee, $program);

        return redirect()->route('payments.checkout', $invoice);
    }

    /** Reject the offer → advised to reapply. */
    public function rejectOffer(Request $request)
    {
        $applicant = $this->myApplicant();
        abort_unless($applicant && $applicant->application_status === 'admitted', 403);

        $applicant->update([
            'admission_response' => 'rejected',
            'application_status' => 'offer_rejected',
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Admission offer rejected. You may reapply for another program below.');
    }

    /** Reapply for another program → new application-fee invoice. */
    public function reapply(Request $request)
    {
        $applicant = $this->myApplicant();
        abort_unless($applicant, 403);

        $data = $request->validate(['program_id' => 'required|exists:programs,id']);
        $program = Program::findOrFail($data['program_id']);
        abort_unless((int) $program->college_id === (int) $applicant->college_id, 422);

        $applicant->update([
            'first_choice_program_id' => $program->id,
            'admitted_program_id'     => null,
            'admission_response'      => null,
            'application_status'      => 'pending_payment',
            'payment_status'          => 'unpaid',
            'status'                  => 'pending',
            'desired_class'           => $program->name,
        ]);

        $invoice = $this->raiseInvoice($applicant, 'application_fee',
            'Application fee (reapplication) — '.$program->name, $program->application_fee, $program);

        return redirect()->route('payments.checkout', $invoice);
    }

    /* ---------------------------------------------------------------------
     | DOCUMENTS — printable admission letter / acceptance form, upload
     |---------------------------------------------------------------------*/

    /** Printable admission letter — only after the acceptance fee is paid. */
    public function admissionLetter()
    {
        $applicant = $this->myApplicant();
        abort_unless($applicant && $this->acceptancePaid($applicant), 403);

        $college = College::withoutGlobalScopes()->find($applicant->college_id);
        $program = $applicant->admittedProgram;

        return view('admission.letter', compact('applicant', 'college', 'program'));
    }

    /** Blank admission acceptance form to print, sign and re-upload. */
    public function acceptanceForm()
    {
        $applicant = $this->myApplicant();
        abort_unless($applicant && $this->acceptancePaid($applicant), 403);

        $college = College::withoutGlobalScopes()->find($applicant->college_id);
        $program = $applicant->admittedProgram;

        return view('admission.acceptance_form', compact('applicant', 'college', 'program'));
    }

    /** Registration document-upload page (after registration fee is paid). */
    public function registration()
    {
        $student = $this->myStudent();
        abort_unless($student, 403);

        $existing = StudentDocument::where('student_id', $student->id)->get()->keyBy('type');

        return view('registration.documents', [
            'student'  => $student,
            'required' => self::REQUIRED_DOCS,
            'existing' => $existing,
        ]);
    }

    /** Store uploaded documents and submit for HOD review. */
    public function storeDocuments(Request $request)
    {
        $student = $this->myStudent();
        abort_unless($student, 403);

        $request->validate(
            collect(self::REQUIRED_DOCS)->mapWithKeys(fn ($l, $k) => [
                "docs.$k" => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
            ])->all()
        );

        foreach ((array) $request->file('docs') as $type => $file) {
            if (!$file) continue;
            $path = $file->store('documents/registration', config('filesystems.documents'));
            StudentDocument::updateOrCreate(
                ['student_id' => $student->id, 'type' => $type],
                [
                    'college_id'    => $student->college_id,
                    'applicant_id'  => $student->applicant_id,
                    'label'         => self::REQUIRED_DOCS[$type] ?? $type,
                    'path'          => $path,
                    'original_name' => $file->getClientOriginalName(),
                ]
            );
        }

        if ($request->boolean('submit_for_review')) {
            $student->update(['registration_status' => 'pending_hod']);
            ActivityLog::record("Submitted registration documents for {$student->full_name}", 'registration.submit');
        }

        return redirect()->route('registration.documents')
            ->with('success', $request->boolean('submit_for_review')
                ? 'Documents submitted to your Head of Department for review.'
                : 'Documents saved. Submit when you are ready.');
    }

    /* ---------------------------------------------------------------------
     | HOD — review / approve registrations
     |---------------------------------------------------------------------*/

    public function hodRegistrations()
    {
        $deptId = auth()->user()->department_id;
        $programIds = Program::where('department_id', $deptId)->pluck('id');

        // Portable ordering (works on SQLite & MySQL): pending first, then
        // returned, then already-registered.
        $order = ['pending_hod' => 0, 'documents_rejected' => 1, 'registered' => 2];
        $students = Student::whereIn('program_id', $programIds)
            ->whereIn('registration_status', array_keys($order))
            ->with(['program', 'department'])
            ->get()
            ->sortBy(fn ($s) => $order[$s->registration_status] ?? 9)
            ->values();

        $docs = StudentDocument::whereIn('student_id', $students->pluck('id'))->get()->groupBy('student_id');

        return view('registration.hod', compact('students', 'docs'));
    }

    public function hodApprove(Student $student)
    {
        $this->authorizeHodDept($student);
        $student->update(['registration_status' => 'registered']);
        ActivityLog::record("Approved registration: {$student->full_name} ({$student->registration_number})", 'registration.approve');

        return back()->with('success', "{$student->full_name} is now fully registered.");
    }

    public function hodReject(Request $request, Student $student)
    {
        $this->authorizeHodDept($student);
        $student->update(['registration_status' => 'documents_rejected']);
        ActivityLog::record("Rejected registration documents: {$student->full_name}", 'registration.reject');

        return back()->with('success', "{$student->full_name}'s documents were returned for correction.");
    }

    /* ---------------------------------------------------------------------
     | Helpers
     |---------------------------------------------------------------------*/

    private function myApplicant(): ?Applicant
    {
        return Applicant::where('user_id', auth()->id())->latest()->first();
    }

    private function myStudent(): ?Student
    {
        return Student::where('email', auth()->user()->email)->first();
    }

    private function acceptancePaid(Applicant $applicant): bool
    {
        return Invoice::where('applicant_id', $applicant->id)
            ->where('purpose', 'acceptance_fee')->where('status', 'paid')->exists();
    }

    private function registrationPaid(Applicant $applicant): bool
    {
        return Invoice::where('applicant_id', $applicant->id)
            ->where('purpose', 'registration_fee')->where('status', 'paid')->exists();
    }

    private function authorizeHodDept(Student $student): void
    {
        $deptId = auth()->user()->department_id;
        abort_unless($student->department_id === $deptId, 403, 'Student is not in your department.');
    }

    private function raiseInvoice(Applicant $applicant, string $purpose, string $desc, $amount, Program $program): Invoice
    {
        return Invoice::create([
            'college_id'  => $applicant->college_id,
            'applicant_id'=> $applicant->id,
            'user_id'     => $applicant->user_id,
            'program_id'  => $program->id,
            'purpose'     => $purpose,
            'description' => $desc,
            'amount'      => $amount,
            'payer_email' => $applicant->email,
            'status'      => 'pending',
            'reference'   => PaystackService::reference(strtoupper(substr($purpose, 0, 3))),
        ]);
    }

    private function admissionNumber(Applicant $applicant): string
    {
        $college = College::withoutGlobalScopes()->find($applicant->college_id);
        $prefix = strtoupper($college->acronym ?? 'COL');
        $year = date('Y');
        $n = Applicant::where('college_id', $applicant->college_id)->whereNotNull('admission_number')->count() + 1;
        do {
            $no = "{$prefix}/ADM/{$year}/".str_pad((string) $n, 4, '0', STR_PAD_LEFT);
            $n++;
        } while (Applicant::where('admission_number', $no)->exists());

        return $no;
    }
}
