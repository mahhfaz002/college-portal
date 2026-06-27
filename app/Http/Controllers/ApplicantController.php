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
        // STRICT host-based tenancy: the apply form belongs to the college that
        // owns THIS domain (bound by SetCollegeContext) — never "the first college".
        $college = current_college();
        if (!$college && app()->isLocal()) {
            $college = \App\Models\College::where('is_active', true)->orderBy('id')->first();
        }
        abort_unless($college, 404);

        $programs = \App\Models\Program::withoutGlobalScopes()
            ->where('college_id', $college->id)->with('department')->orderBy('name')->get();

        return view('admission.form', compact('college', 'programs'));
    }

    /**
     * Handle a public application (Phase 2): create the applicant, raise the
     * application-fee invoice for the first-choice program, and send the
     * candidate to the payment gateway. The account is created only AFTER the
     * application fee is confirmed (see GatewayPaymentController).
     */
    public function submit(Request $request)
    {
        $rules = [
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
            // Section C — O'Level results (per-subject: grade, exam body, year, exam no.)
            'results'              => 'required|array|min:5',
            'results.*.subject'    => 'nullable|string|max:60',
            'results.*.grade'      => 'nullable|in:A1,B2,B3,C4,C5,C6,D7,E8,F9',
            'results.*.exam_type'  => 'nullable|in:WAEC,NECO,NABTEB',
            'results.*.exam_year'  => 'nullable|digits:4',
            'results.*.exam_number'=> 'nullable|string|max:60',
        ];

        $messages = [
            // A returning applicant whose fee is already paid has an account —
            // they must log in, not re-apply. Make that explicit.
            'email.unique'              => 'An account already exists with this email address. If you have applied before, please log in instead — or use a different email to start a new application.',
            'second_choice_program_id.different' => 'Your second-choice programme must be different from your first choice.',
            'date_of_birth.before'      => 'Your date of birth must be a date in the past.',
        ];

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            // DIAGNOSTIC: record exactly which rule rejected the submission so a
            // silent "back to the form" can be traced to a precise field. The
            // applicant also sees these errors loudly on the form itself.
            \Log::warning('Apply form rejected at validation', [
                'host'               => $request->getHost(),
                'email'              => $request->input('email'),
                'email_already_user' => \App\Models\User::withoutGlobalScopes()->where('email', $request->input('email'))->exists(),
                'first_choice'       => $request->input('first_choice_program_id'),
                'second_choice'      => $request->input('second_choice_program_id'),
                'dob'                => $request->input('date_of_birth'),
                'errors'             => $validator->errors()->toArray(),
            ]);

            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        // STRICT tenancy: bind the application to the college that owns THIS
        // domain, ignoring any college_id supplied by a tampered form. The
        // chosen programme is then verified to belong to that college below.
        if ($hostCollege = current_college()) {
            $validated['college_id'] = $hostCollege->id;
        }

        // Keep only fully-filled result rows (subject + grade), capturing the
        // exam body, year and examination number PER subject (combined sittings).
        $results = collect($validated['results'] ?? [])
            ->map(fn ($r) => [
                // Title-case each word ("computer science" → "Computer Science"),
                // not ALL CAPS — the form auto-capitalises the first letter of
                // every word and the stored value matches.
                'subject'     => ucwords(strtolower(trim($r['subject'] ?? ''))),
                'grade'       => $r['grade'] ?? null,
                'exam_type'   => $r['exam_type'] ?? null,
                'exam_year'   => $r['exam_year'] ?? null,
                'exam_number' => trim($r['exam_number'] ?? ''),
            ])
            ->filter(fn ($r) => $r['subject'] !== '' && $r['grade'])
            ->values()->all();

        // Back-compat: the applicant's headline exam_type/exam_year columns take
        // the values from the first graded subject (per-subject detail lives in
        // olevel_results).
        $primaryExamType = $results[0]['exam_type'] ?? null;
        $primaryExamYear = $results[0]['exam_year'] ?? null;

        $program = \App\Models\Program::withoutGlobalScopes()->findOrFail($validated['first_choice_program_id']);

        // Guard: chosen program must belong to the chosen college.
        abort_unless((int) $program->college_id === (int) $validated['college_id'], 422, 'Program does not belong to the selected college.');

        // The passport photograph is no longer collected on this public form — it
        // is uploaded (with JAMB/SSCE) from the applicant account after the
        // application fee is paid (see ApplicationSubmissionController). Keeping
        // it off the public form keeps this POST small so it can never be
        // truncated past PHP's upload limits (the cause of the silent reload).
        $passport = null;

        // Create the applicant and its application-fee invoice atomically: if the
        // invoice can't be raised we must not leave an orphaned applicant behind
        // (which would also burn the unique email). On any failure the applicant
        // is returned to the form with their input intact and a clear message,
        // never a blank reload or a 500.
        try {
            $invoice = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $program, $results, $primaryExamType, $primaryExamYear, $passport) {
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
                    'exam_type'      => $primaryExamType,
                    'exam_year'      => $primaryExamYear,
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
                return \App\Models\Invoice::create([
                    'college_id'  => $validated['college_id'],
                    'applicant_id'=> $applicant->id,
                    'program_id'  => $program->id,
                    'purpose'     => 'application_fee',
                    'description' => 'Application fee — '.$program->name,
                    'amount'      => max(0, (float) $program->application_fee),
                    'payer_email' => $applicant->email,
                    'status'      => 'pending',
                    'reference'   => \App\Services\PaystackService::reference('APP', (int) $validated['college_id']),
                ]);
            });
        } catch (\Throwable $e) {
            \Log::error('Application submission failed', ['email' => $validated['email'] ?? null, 'error' => $e->getMessage()]);

            return back()->withInput()->with('error',
                'We could not start your application just now. Please try again in a moment. '
                .'If this keeps happening, contact the college admissions office.');
        }

        return redirect()->route('payments.checkout', ['invoice' => $invoice->id]);
    }

    /** Registrar review panel. */
    public function index()
    {
        $applicants = Applicant::with(['firstChoice', 'secondChoice', 'admittedProgram'])->latest()->get();
        // Courses of study the registrar can offer admission into.
        $programs = \App\Models\Program::with('department')->orderBy('name')->get();

        return view('admission.admin', compact('applicants', 'programs'));
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
