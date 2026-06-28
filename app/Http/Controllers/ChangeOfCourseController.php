<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ChangeOfCourseRequest;
use App\Models\Invoice;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Change-of-course workflow (multi-stage):
 *
 *   student pays application fee
 *     → Academic Secretary processes + comments, forwards to NEW dept HOD
 *     → new HOD accepts/rejects (+comment), returns to AS
 *         reject → AS rejects to student (cites new HOD)
 *         accept → AS forwards to CURRENT dept HOD
 *     → current HOD accepts/rejects (+comment), returns to AS
 *         reject → AS rejects to student (cites current HOD)
 *         accept → AS forwards to Registrar
 *     → Registrar approves/rejects (final)
 *         approve → student sees "Approved", downloads acceptance letter,
 *                   pays the new-course registration fee → migrated.
 */
class ChangeOfCourseController extends Controller
{
    /* ============================ STUDENT ============================ */

    /** The application form + the student's own (paid) request history. */
    public function index()
    {
        $student = $this->student();
        abort_unless($student, 403, 'Only registered students can apply for a change of course.');

        $programs = Program::with('department')
            ->where('id', '!=', $student->program_id)
            ->orderBy('name')->get();

        // My Applications shows only applications the student actually PAID for and
        // submitted — never a stray unpaid (pending_payment) attempt.
        $requests = ChangeOfCourseRequest::with(['requestedProgram.department', 'currentProgram'])
            ->where('student_id', $student->id)
            ->where('status', '!=', 'pending_payment')
            ->latest()->get();

        $fee = (float) setting('change_of_course_fee', ChangeOfCourseRequest::FEE);

        return view('change_of_course.index', compact('student', 'programs', 'requests', 'fee'));
    }

    /** Submit the application → raise the fee invoice → gateway checkout. */
    public function store(Request $request)
    {
        $student = $this->student();
        abort_unless($student, 403);

        $data = $request->validate([
            'requested_program_id' => ['required', 'exists:programs,id', Rule::notIn([$student->program_id])],
            'reason'               => 'required|string|max:2000',
        ]);

        $program = Program::findOrFail($data['requested_program_id']);
        abort_unless((int) $program->college_id === (int) $student->college_id, 422);

        // Block only when a PAID application is still in the pipeline.
        $inProgress = ChangeOfCourseRequest::where('student_id', $student->id)
            ->whereIn('status', ChangeOfCourseRequest::PROCESSING_STATUSES)->first();
        if ($inProgress) {
            return redirect()->route('change-of-course.index')
                ->with('error', 'You already have a change-of-course application under review. Please wait for a decision before applying again.');
        }

        // Discard a stale unpaid attempt (+ void its invoice) before re-applying.
        ChangeOfCourseRequest::where('student_id', $student->id)
            ->where('status', 'pending_payment')->get()
            ->each(function ($stale) {
                if ($stale->invoice_id) {
                    Invoice::where('id', $stale->invoice_id)->where('status', 'pending')->update(['status' => 'cancelled']);
                }
                $stale->delete();
            });

        $fee = (float) setting('change_of_course_fee', ChangeOfCourseRequest::FEE);

        $cocr = ChangeOfCourseRequest::create([
            'college_id'           => $student->college_id,
            'student_id'           => $student->id,
            'user_id'              => auth()->id(),
            'current_program_id'   => $student->program_id,
            'requested_program_id' => $program->id,
            'reason'               => $data['reason'],
            'status'               => 'pending_payment',
        ]);

        $invoice = Invoice::create([
            'college_id'  => $student->college_id,
            'student_id'  => $student->id,
            'user_id'     => auth()->id(),
            'program_id'  => $program->id,
            'purpose'     => 'change_of_course',
            'description' => 'Change-of-course application fee — '.$program->name,
            'amount'      => $fee,
            'payer_email' => $student->email,
            'status'      => 'pending',
            'reference'   => PaystackService::reference('COC', $student->college_id),
        ]);

        $cocr->update(['invoice_id' => $invoice->id]);

        return redirect()->route('payments.checkout', $invoice);
    }

    /**
     * Acceptance letter — only after the Registrar has approved. Rendered as a
     * print-friendly HTML page (Print / Save as PDF), the same convention as the
     * admission letter, with the Registrar's e-signature embedded as a data URI.
     */
    public function letter(ChangeOfCourseRequest $changeOfCourse)
    {
        $this->authorizeStudentOrStaff($changeOfCourse);
        abort_unless($changeOfCourse->isApproved(), 404, 'The acceptance letter is available only after approval.');

        $changeOfCourse->load(['student', 'currentProgram.department', 'requestedProgram.department']);
        $college = $changeOfCourse->college_id
            ? \App\Models\College::withoutGlobalScopes()->find($changeOfCourse->college_id)
            : current_college();

        $registrar = User::withoutGlobalScopes()->where('id', $changeOfCourse->registrar_id)->first();
        $registrarSignature = $this->registrarSignatureDataUri((int) $changeOfCourse->college_id);

        return view('change_of_course.letter', [
            'cocr' => $changeOfCourse, 'college' => $college,
            'registrar' => $registrar, 'registrarSignature' => $registrarSignature,
        ]);
    }

    /** Base64 data URI of the college Registrar's signature PNG, or null. */
    private function registrarSignatureDataUri(int $collegeId): ?string
    {
        $registrar = User::withoutGlobalScopes()
            ->where('college_id', $collegeId)->where('role', 'registrar')
            ->whereNotNull('signature_path')->first();
        if (! $registrar) {
            return null;
        }
        $disk = config('filesystems.documents', 'local');
        try {
            if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($registrar->signature_path)) {
                return 'data:image/png;base64,'.base64_encode(\Illuminate\Support\Facades\Storage::disk($disk)->get($registrar->signature_path));
            }
        } catch (\Throwable $e) {
            // Non-fatal — fall back to the printed signature line.
        }
        return null;
    }

    /** Raise (or reuse) the new-course registration-fee invoice and go to pay. */
    public function payNewFee(ChangeOfCourseRequest $changeOfCourse)
    {
        $student = $this->student();
        abort_unless($student && $changeOfCourse->student_id === $student->id, 403);
        abort_unless($changeOfCourse->awaitingNewRegistrationFee(), 422, 'No registration fee is due for this application.');

        $invoice = self::ensureNewRegistrationInvoice($changeOfCourse);
        abort_unless($invoice, 422, 'Could not raise the registration invoice.');

        return redirect()->route('payments.checkout', $invoice);
    }

    /**
     * Ensure the new-course registration-fee invoice exists for an approved
     * request (idempotent). Used by payNewFee() AND the student Fees page, so the
     * fee surfaces under Fees automatically once the change of course is approved.
     */
    public static function ensureNewRegistrationInvoice(ChangeOfCourseRequest $cocr): ?Invoice
    {
        if (! $cocr->awaitingNewRegistrationFee()) {
            return null;
        }

        $existing = $cocr->new_registration_invoice_id
            ? Invoice::where('id', $cocr->new_registration_invoice_id)->whereIn('status', ['pending', 'paid'])->first()
            : null;
        if ($existing) {
            return $existing;
        }

        $program = Program::withoutGlobalScopes()->find($cocr->requested_program_id);
        $student = Student::withoutGlobalScopes()->find($cocr->student_id);
        if (! $program || ! $student) {
            return null;
        }

        $invoice = Invoice::create([
            'college_id'  => $student->college_id,
            'student_id'  => $student->id,
            'user_id'     => $cocr->user_id,
            'program_id'  => $program->id,
            'purpose'     => 'coc_registration',
            'description' => 'Registration fee (new course) — '.$program->name,
            'amount'      => $program->firstSemesterRegistrationFee(),
            'payer_email' => $student->email,
            'status'      => 'pending',
            'reference'   => PaystackService::reference('CRG', $student->college_id),
        ]);
        $cocr->update(['new_registration_invoice_id' => $invoice->id]);

        return $invoice;
    }

    /* ===================== ACADEMIC SECRETARY ===================== */

    /** Everything that needs the Academic Secretary's hand. */
    public function review()
    {
        $requests = ChangeOfCourseRequest::with(['student', 'currentProgram.department', 'requestedProgram.department'])
            ->whereIn('status', [
                'secretary_review',
                'new_hod_approved', 'new_hod_rejected',
                'current_hod_approved', 'current_hod_rejected',
            ])->latest()->get();

        return view('change_of_course.review', compact('requests'));
    }

    /** Read-only credentials of the applicant (AS / HOD / Registrar). */
    public function credentials(ChangeOfCourseRequest $changeOfCourse)
    {
        abort_unless(
            auth()->user()->hasRole('academic_secretary', 'hod', 'assistant_hod', 'registrar'),
            403
        );

        $changeOfCourse->load(['student.program.department', 'currentProgram.department', 'requestedProgram.department']);
        $student = $changeOfCourse->student;

        $scores = \App\Models\Score::with('exam.subject')
            ->where('student_id', $student->id ?? 0)->get();
        $documents = \App\Models\StudentDocument::where('student_id', $student->id ?? 0)->get();

        return view('change_of_course.credentials', compact('changeOfCourse', 'student', 'scores', 'documents'));
    }

    /** AS comments and forwards the fresh request to the NEW department's HOD. */
    public function forwardToNewHod(Request $request, ChangeOfCourseRequest $changeOfCourse)
    {
        abort_unless($changeOfCourse->status === 'secretary_review', 403, 'This request is not awaiting the first review.');
        $data = $request->validate(['comment' => 'required|string|max:1000']);

        $newDeptId = optional($changeOfCourse->requestedProgram)->department_id;
        if (! $this->departmentHasHod($newDeptId)) {
            return back()->with('error', 'The requested department has no Head of Department yet — ask the Registrar to assign one before forwarding.');
        }

        $changeOfCourse->update([
            'secretary_id'            => auth()->id(),
            'secretary_comment'       => $data['comment'],
            'status'                  => 'new_hod_review',
            'forwarded_to_new_hod_at' => now(),
        ]);
        ActivityLog::record("Forwarded change-of-course #{$changeOfCourse->id} to the new department HOD", 'coc.forward_new_hod');

        return back()->with('success', 'Comment saved and application forwarded to the new department HOD.');
    }

    /** After the new HOD accepted, AS forwards to the CURRENT department's HOD. */
    public function relayToCurrentHod(ChangeOfCourseRequest $changeOfCourse)
    {
        abort_unless($changeOfCourse->status === 'new_hod_approved', 403);

        $currentDeptId = optional($changeOfCourse->student)->department_id
            ?? optional($changeOfCourse->currentProgram)->department_id;
        if (! $this->departmentHasHod($currentDeptId)) {
            return back()->with('error', 'Your current department has no HOD assigned — ask the Registrar to assign one before forwarding.');
        }

        $changeOfCourse->update([
            'status'                      => 'current_hod_review',
            'forwarded_to_current_hod_at' => now(),
        ]);
        ActivityLog::record("Forwarded change-of-course #{$changeOfCourse->id} to the current department HOD", 'coc.forward_current_hod');

        return back()->with('success', 'Forwarded to your current department HOD for clearance.');
    }

    /** After the current HOD accepted, AS forwards to the Registrar. */
    public function forwardToRegistrar(ChangeOfCourseRequest $changeOfCourse)
    {
        abort_unless($changeOfCourse->status === 'current_hod_approved', 403);

        $changeOfCourse->update([
            'status'                    => 'registrar_review',
            'forwarded_to_registrar_at' => now(),
        ]);
        ActivityLog::record("Forwarded change-of-course #{$changeOfCourse->id} to the Registrar", 'coc.forward_registrar');

        return back()->with('success', 'Forwarded to the Registrar for final approval.');
    }

    /** AS rejects to the student, citing the HOD comment that caused it. */
    public function rejectToStudent(ChangeOfCourseRequest $changeOfCourse)
    {
        abort_unless(in_array($changeOfCourse->status, ['new_hod_rejected', 'current_hod_rejected'], true), 403);

        $stage  = $changeOfCourse->status === 'new_hod_rejected' ? 'new_hod' : 'current_hod';
        $reason = $stage === 'new_hod' ? $changeOfCourse->new_hod_comment : $changeOfCourse->current_hod_comment;

        $changeOfCourse->update([
            'status'           => 'rejected',
            'rejected_stage'   => $stage,
            'rejection_reason' => $reason,
            'decided_at'       => now(),
        ]);
        ActivityLog::record("Rejected change-of-course #{$changeOfCourse->id} to student ({$stage})", 'coc.reject');

        return back()->with('success', 'The application has been rejected and the student notified with the reason.');
    }

    /* =========================== HOD =========================== */

    /** The HOD's change-of-course queue (incoming to their dept, or outgoing from it). */
    public function hodQueue()
    {
        $deptId = auth()->user()->department_id;

        $incoming = ChangeOfCourseRequest::with(['student', 'currentProgram.department', 'requestedProgram.department'])
            ->where('status', 'new_hod_review')
            ->whereHas('requestedProgram', fn ($q) => $q->where('department_id', $deptId))
            ->latest()->get();

        $outgoing = ChangeOfCourseRequest::with(['student', 'currentProgram.department', 'requestedProgram.department'])
            ->where('status', 'current_hod_review')
            ->whereHas('student', fn ($q) => $q->where('department_id', $deptId))
            ->latest()->get();

        return view('change_of_course.hod', compact('incoming', 'outgoing'));
    }

    /** HOD accepts/rejects with a comment; routes the request back to the AS. */
    public function hodDecide(Request $request, ChangeOfCourseRequest $changeOfCourse)
    {
        $deptId = auth()->user()->department_id;
        $data = $request->validate([
            'decision' => ['required', Rule::in(['accept', 'reject'])],
            'comment'  => 'required|string|max:1000',
        ]);

        // Which HOD am I in this request — the new department's, or the current?
        if ($changeOfCourse->status === 'new_hod_review'
            && (int) optional($changeOfCourse->requestedProgram)->department_id === (int) $deptId) {
            $changeOfCourse->update([
                'new_hod_id'       => auth()->id(),
                'new_hod_decision' => $data['decision'],
                'new_hod_comment'  => $data['comment'],
                'new_hod_at'       => now(),
                'status'           => $data['decision'] === 'accept' ? 'new_hod_approved' : 'new_hod_rejected',
            ]);
        } elseif ($changeOfCourse->status === 'current_hod_review'
            && (int) optional($changeOfCourse->student)->department_id === (int) $deptId) {
            $changeOfCourse->update([
                'current_hod_id'       => auth()->id(),
                'current_hod_decision' => $data['decision'],
                'current_hod_comment'  => $data['comment'],
                'current_hod_at'       => now(),
                'status'               => $data['decision'] === 'accept' ? 'current_hod_approved' : 'current_hod_rejected',
            ]);
        } else {
            abort(403, 'This application is not awaiting your department.');
        }

        ActivityLog::record("HOD {$data['decision']}ed change-of-course #{$changeOfCourse->id}", 'coc.hod_decide');

        return back()->with('success', 'Your decision was recorded and returned to the Academic Secretary.');
    }

    /* ========================= REGISTRAR ========================= */

    public function approvals()
    {
        $requests = ChangeOfCourseRequest::with(['student', 'currentProgram.department', 'requestedProgram.department'])
            ->where('status', 'registrar_review')->latest()->get();

        return view('change_of_course.registrar', compact('requests'));
    }

    public function decide(Request $request, ChangeOfCourseRequest $changeOfCourse)
    {
        abort_unless($changeOfCourse->status === 'registrar_review', 403);

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'comment'  => 'nullable|string|max:1000',
            'reason'   => 'required_if:decision,reject|nullable|string|max:1000',
        ]);

        if ($data['decision'] === 'approve') {
            $changeOfCourse->update([
                'status'            => 'approved',
                'registrar_id'      => auth()->id(),
                'registrar_comment' => $data['comment'] ?? null,
                'decided_at'        => now(),
            ]);
            $msg = 'Change of course approved. The student can now download the acceptance letter and pay the new registration fee.';
        } else {
            $changeOfCourse->update([
                'status'           => 'rejected',
                'registrar_id'     => auth()->id(),
                'registrar_reason' => $data['reason'],
                'rejected_stage'   => 'registrar',
                'rejection_reason' => $data['reason'],
                'decided_at'       => now(),
            ]);
            $msg = 'Change-of-course application rejected.';
        }

        ActivityLog::record("Registrar {$data['decision']}d change-of-course #{$changeOfCourse->id}", 'coc.decide');

        return back()->with('success', $msg);
    }

    /* ========================== HELPERS ========================== */

    /** Mark the APPLICATION fee paid → enters the Academic Secretary queue. */
    public static function markPaidByInvoice(Invoice $invoice): void
    {
        $cocr = ChangeOfCourseRequest::where('invoice_id', $invoice->id)
            ->where('status', 'pending_payment')->first();
        $cocr?->update(['status' => 'secretary_review']);
    }

    /**
     * Mark the NEW-COURSE registration fee paid → migrate the student onto the
     * new course, deleting old registrations/bills but KEEPING results.
     */
    public static function markNewRegistrationPaid(Invoice $invoice): void
    {
        $cocr = ChangeOfCourseRequest::where('new_registration_invoice_id', $invoice->id)
            ->where('status', 'approved')->first();
        if (! $cocr) {
            return;
        }

        DB::transaction(function () use ($cocr) {
            $program = Program::withoutGlobalScopes()->find($cocr->requested_program_id);
            $student = Student::withoutGlobalScopes()->find($cocr->student_id);
            if (! $program || ! $student) {
                return;
            }

            // Migrate onto the new course of study. RESULTS (scores) are left
            // untouched; only the registration-side records are cleared so the
            // student re-registers afresh in the new department.
            if (\Illuminate\Support\Facades\Schema::hasTable('course_registrations')) {
                \App\Models\CourseRegistration::withoutGlobalScopes()->where('student_id', $student->id)->delete();
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('fee_bills')) {
                \App\Models\FeeBill::withoutGlobalScopes()->where('student_id', $student->id)->delete();
            }

            $student->update([
                'program_id'    => $program->id,
                'department_id' => $program->department_id,
                'class_arm'     => $program->name,
            ]);

            $cocr->update([
                'status'          => 'completed',
                'new_fee_paid_at' => now(),
                'migrated_at'     => now(),
            ]);
        });

        ActivityLog::create([
            'college_id'  => $cocr->college_id,
            'user_id'     => $cocr->user_id,
            'action'      => 'coc.migrated',
            'description' => "Student #{$cocr->student_id} migrated to program #{$cocr->requested_program_id} after paying the new registration fee",
        ]);
    }

    private function departmentHasHod(?int $departmentId): bool
    {
        if (! $departmentId) {
            return false;
        }
        return User::where('department_id', $departmentId)
            ->whereIn('role', ['hod', 'assistant_hod'])->exists();
    }

    private function authorizeStudentOrStaff(ChangeOfCourseRequest $cocr): void
    {
        $user = auth()->user();
        $isOwner = $user->role === 'student'
            && optional(Student::where('email', $user->email)->first())->id === $cocr->student_id;
        $isStaff = $user->hasRole('academic_secretary', 'registrar', 'hod', 'assistant_hod', 'proprietor');
        abort_unless($isOwner || $isStaff, 403);
    }

    private function student(): ?Student
    {
        return Student::where('email', auth()->user()->email)->first();
    }
}
