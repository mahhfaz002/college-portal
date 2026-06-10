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
}
