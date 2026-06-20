<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ChangeOfCourseRequest;
use App\Models\Invoice;
use App\Models\Program;
use App\Models\Student;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Change-of-course workflow:
 *   student applies (+ reason) → pays the application fee → Academic Secretary
 *   recommends → Registrar approves/rejects. The student tracks progress on
 *   their dashboard.
 */
class ChangeOfCourseController extends Controller
{
    /* ----------------------------- Student ----------------------------- */

    /** The application form + the student's own request history (tracking). */
    public function index()
    {
        $student = $this->student();
        abort_unless($student, 403, 'Only registered students can apply for a change of course.');

        $programs = Program::with('department')
            ->where('id', '!=', $student->program_id)
            ->orderBy('name')->get();

        $requests = ChangeOfCourseRequest::with(['requestedProgram', 'currentProgram'])
            ->where('student_id', $student->id)->latest()->get();

        return view('change_of_course.index', compact('student', 'programs', 'requests'));
    }

    /** Submit the application → raise the fee invoice → gateway checkout. */
    public function store(Request $request)
    {
        $student = $this->student();
        abort_unless($student, 403);

        $data = $request->validate([
            'requested_program_id' => [
                'required', 'exists:programs,id',
                Rule::notIn([$student->program_id]),   // must differ from current course
            ],
            'reason' => 'required|string|max:2000',
        ]);

        $program = Program::findOrFail($data['requested_program_id']);
        abort_unless((int) $program->college_id === (int) $student->college_id, 422);

        // One open application at a time.
        $open = ChangeOfCourseRequest::where('student_id', $student->id)
            ->whereNotIn('status', ['approved', 'rejected'])->first();
        if ($open) {
            return redirect()->route('change-of-course.index')
                ->with('error', 'You already have a change-of-course application in progress.');
        }

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
            'amount'      => ChangeOfCourseRequest::FEE,
            'payer_email' => $student->email,
            'status'      => 'pending',
            'reference'   => PaystackService::reference('COC', $student->college_id),
        ]);

        $cocr->update(['invoice_id' => $invoice->id]);

        return redirect()->route('payments.checkout', $invoice);
    }

    /* ------------------------ Academic Secretary ----------------------- */

    /** Paid requests awaiting the Academic Secretary's recommendation. */
    public function review()
    {
        $requests = ChangeOfCourseRequest::with(['student', 'currentProgram', 'requestedProgram'])
            ->where('status', 'under_review')->latest()->get();

        return view('change_of_course.review', compact('requests'));
    }

    public function recommend(Request $request, ChangeOfCourseRequest $changeOfCourse)
    {
        abort_unless($changeOfCourse->status === 'under_review', 403, 'This request is not awaiting review.');

        $data = $request->validate([
            'decision' => ['required', Rule::in(['recommend', 'against'])],
            'note'     => 'nullable|string|max:1000',
        ]);

        $changeOfCourse->update([
            'status'         => $data['decision'] === 'recommend' ? 'recommended' : 'not_recommended',
            'secretary_id'   => auth()->id(),
            'secretary_note' => $data['note'] ?? null,
            'recommended_at' => now(),
        ]);

        ActivityLog::record("Reviewed change-of-course for student #{$changeOfCourse->student_id}", 'coc.recommend');

        return back()->with('success', 'Recommendation recorded and forwarded to the Registrar.');
    }

    /* ----------------------------- Registrar --------------------------- */

    /** Requests the Academic Secretary has reviewed, awaiting final decision. */
    public function approvals()
    {
        $requests = ChangeOfCourseRequest::with(['student', 'currentProgram', 'requestedProgram'])
            ->whereIn('status', ['recommended', 'not_recommended'])->latest()->get();

        return view('change_of_course.registrar', compact('requests'));
    }

    public function decide(Request $request, ChangeOfCourseRequest $changeOfCourse)
    {
        abort_unless(in_array($changeOfCourse->status, ['recommended', 'not_recommended'], true), 403);

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'reason'   => 'required_if:decision,reject|nullable|string|max:1000',
        ]);

        if ($data['decision'] === 'approve') {
            $program = $changeOfCourse->requestedProgram;
            // Move the student onto the new course of study.
            Student::withoutGlobalScopes()->where('id', $changeOfCourse->student_id)->update([
                'program_id'    => $program->id,
                'department_id' => $program->department_id,
                'class_arm'     => $program->name,
            ]);
            $changeOfCourse->update([
                'status'        => 'approved',
                'registrar_id'  => auth()->id(),
                'decided_at'    => now(),
            ]);
            $msg = 'Change of course approved — the student has been moved to the new course.';
        } else {
            $changeOfCourse->update([
                'status'           => 'rejected',
                'registrar_id'     => auth()->id(),
                'registrar_reason' => $data['reason'],
                'decided_at'       => now(),
            ]);
            $msg = 'Change-of-course application rejected.';
        }

        ActivityLog::record("Registrar {$data['decision']}d change-of-course #{$changeOfCourse->id}", 'coc.decide');

        return back()->with('success', $msg);
    }

    /* ------------------------------ Helpers ---------------------------- */

    /** Mark a request paid once its application-fee invoice is settled. */
    public static function markPaidByInvoice(Invoice $invoice): void
    {
        $cocr = ChangeOfCourseRequest::where('invoice_id', $invoice->id)
            ->where('status', 'pending_payment')->first();
        $cocr?->update(['status' => 'under_review']);
    }

    private function student(): ?Student
    {
        return Student::where('email', auth()->user()->email)->first();
    }
}
