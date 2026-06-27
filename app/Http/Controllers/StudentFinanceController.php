<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;

/**
 * The student's own Fees page — invoices (online payment orders) plus their
 * payment history, on a dedicated page reached from the sidebar.
 */
class StudentFinanceController extends Controller
{
    public function index()
    {
        $student = Student::where('email', auth()->user()->email)->firstOrFail();

        $invoices = Invoice::where('student_id', $student->id)->latest()->get();
        $payments = Payment::where('student_id', $student->id)->latest()->get();

        // Unified payment history: EVERY settled payment the student has made,
        // whether it was an online invoice (Paystack) or an offline payment a
        // bursar recorded. Each row carries a receipt link the student can
        // download. Paid invoices appear here the moment the gateway confirms
        // them (status → paid in the callback/webhook), so the history is live.
        $history = collect();

        foreach ($invoices->where('status', 'paid') as $inv) {
            $history->push([
                'date'        => $inv->paid_at ?? $inv->updated_at,
                'description' => $inv->description ?: ucwords(str_replace('_', ' ', $inv->purpose)),
                'method'      => $inv->payment_method ?: 'paystack',
                'amount'      => (float) $inv->chargeable(),
                'receipt_url' => route('invoices.receipt', $inv),
            ]);
        }

        foreach ($payments as $p) {
            $history->push([
                'date'        => $p->created_at,
                'description' => $p->description ?: 'Fees payment',
                'method'      => $p->payment_method ?: 'cash',
                'amount'      => (float) $p->amount,
                'receipt_url' => route('payments.receipt', $p),
            ]);
        }

        $history = $history->sortByDesc('date')->values();

        return view('student.fees', compact('student', 'invoices', 'payments', 'history'));
    }
}
