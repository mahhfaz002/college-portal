<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\Invoice;

class InvoiceController extends Controller
{
    /**
     * Printable receipt for a paid invoice, branded with the college's logo and
     * details. Accessible to the invoice owner (applicant/student) or any
     * finance/oversight staff in the same college (scoping handles the latter).
     */
    public function receipt(Invoice $invoice)
    {
        abort_unless($invoice->isPaid(), 404);

        $user = auth()->user();
        $owns = $invoice->user_id === $user->id
            || ($invoice->applicant_id && optional($invoice->applicant)->user_id === $user->id);
        $isStaff = $user->canManage('view_fees') || $user->hasRole('registrar', 'bursar', 'proprietor');

        abort_unless($owns || $isStaff, 403);

        $college = $invoice->college_id
            ? College::withoutGlobalScopes()->find($invoice->college_id)
            : current_college();

        return view('payments.invoice_receipt', compact('invoice', 'college'));
    }

    /** Cancel a pending invoice the payer has decided not to pay. */
    public function cancel(Invoice $invoice)
    {
        $this->authorizeManage($invoice);
        abort_if($invoice->isPaid(), 422, 'A paid invoice cannot be cancelled.');

        if ($invoice->status !== 'cancelled') {
            $invoice->update(['status' => 'cancelled']);
        }

        return back()->with('success', 'Payment cancelled. You can delete it from your fees list.');
    }

    /** Permanently remove an unpaid (pending/cancelled) invoice. Never a paid one. */
    public function destroy(Invoice $invoice)
    {
        $this->authorizeManage($invoice);
        abort_if($invoice->isPaid(), 422, 'A paid invoice cannot be deleted.');

        $invoice->delete();

        return back()->with('success', 'Invoice removed.');
    }

    /**
     * Only the invoice's own payer (applicant / student / linked user) or finance
     * staff in the college may cancel or delete it.
     */
    private function authorizeManage(Invoice $invoice): void
    {
        $user = auth()->user();
        $owns = $invoice->user_id === $user->id
            || ($invoice->applicant_id && optional($invoice->applicant)->user_id === $user->id)
            || ($invoice->student_id && optional($invoice->student)->email === $user->email);
        $isStaff = $user->canManage('view_fees') || $user->hasRole('registrar', 'bursar', 'proprietor');

        abort_unless($owns || $isStaff, 403);
    }
}
