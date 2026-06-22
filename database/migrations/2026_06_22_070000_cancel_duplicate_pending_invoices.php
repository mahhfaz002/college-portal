<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One-off cleanup: cancel pending invoices that duplicate a fee the same payer
 * has ALREADY paid (same applicant/student + purpose). Before accept() was made
 * idempotent, re-clicking "Accept" raised a second acceptance-fee invoice, so
 * dashboards showed a stray "Pay Now" for an already-settled fee.
 */
return new class extends Migration
{
    public function up(): void
    {
        $paid = DB::table('invoices')->where('status', 'paid')
            ->get(['applicant_id', 'student_id', 'purpose']);

        foreach ($paid as $p) {
            $q = DB::table('invoices')->where('status', 'pending')->where('purpose', $p->purpose);
            if ($p->applicant_id) {
                $q->where('applicant_id', $p->applicant_id);
            } elseif ($p->student_id) {
                $q->where('student_id', $p->student_id);
            } else {
                continue;
            }
            $q->update(['status' => 'cancelled']);
        }
    }

    public function down(): void
    {
        // No-op: we don't want to resurrect duplicate payable invoices.
    }
};
