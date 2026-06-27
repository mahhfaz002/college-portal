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

        return view('student.fees', compact('student', 'invoices', 'payments'));
    }
}
