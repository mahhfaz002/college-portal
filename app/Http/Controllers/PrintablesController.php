<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Invoice;
use App\Models\Payslip;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Bursar "Printables" archive — one place to retrieve every printable a student
 * or staff member has accrued:
 *   • Student receipts (paid invoices) + outstanding (unpaid) invoices.
 *   • Staff payslips.
 * The lists are searchable/filterable; the per-person documents are fetched on
 * demand (JSON) and opened in a popup as printable/downloadable links.
 */
class PrintablesController extends Controller
{
    public function index()
    {
        $programs = Program::with('department')->orderBy('name')->get()->map(fn ($p) => [
            'id'      => $p->id,
            'name'    => $p->name,
            'levels'  => (int) ($p->levels ?: 1),
            'dept_id' => $p->department_id,
            'section' => optional($p->department)->section,
        ]);

        $students = Student::with('department')->orderBy('full_name')->get()->map(fn ($s) => [
            'id'         => $s->id,
            'name'       => $s->full_name,
            'reg'        => $s->registration_number ?: $s->admission_number,
            'dept_id'    => $s->department_id,
            'program_id' => $s->program_id,
            'level'      => (string) $s->level,
            'section'    => optional($s->department)->section ?: 'Others',
        ])->values();

        $staff = User::whereNotIn('role', ['student', 'applicant', 'superadmin'])
            ->with('departmentModel')->orderBy('name')->get()->map(fn ($u) => [
                'id'       => $u->id,
                'name'     => $u->name,
                'staff_id' => $u->staff_id,
                'role'     => $u->role,
                'dept_id'  => $u->department_id,
                'dept'     => optional($u->departmentModel)->name,
                'section'  => optional($u->departmentModel)->section ?: 'Others',
            ])->values();

        return view('printables.index', [
            'programs'    => $programs,
            'students'    => $students,
            'staff'       => $staff,
            'departments' => Department::orderBy('name')->get(['id', 'name', 'section']),
            'sections'    => array_merge(\App\Support\Sections::ALL, ['Others']),
        ]);
    }

    /**
     * JSON: a student's receipts (newest first) + outstanding invoices.
     *
     * "Receipts" = ALL money received for the student: manual bursar-recorded
     * Payments (cash/transfer) AND paid online Invoices (application / acceptance
     * / registration / fee orders) — whether the invoice is linked directly by
     * student_id or through the student's applicant record. Previously only
     * student_id invoices showed, so cash payments and admission-stage online
     * payments never appeared for the bursar to print.
     */
    public function studentReceipts(Student $student)
    {
        // 1. Manual payments recorded by the bursar — each row is a paid receipt.
        $payments = \App\Models\Payment::where('student_id', $student->id)
            ->latest()->get()->map(fn ($p) => [
                'description' => $p->description ?: 'Fee payment',
                'purpose'     => $p->payment_method ? ucfirst($p->payment_method).' payment' : 'Payment',
                'amount'      => money($p->amount),
                'date'        => optional($p->created_at)->format('d M Y'),
                'reference'   => 'PAY-'.str_pad((string) $p->id, 6, '0', STR_PAD_LEFT),
                'receipt_url' => route('payments.receipt', $p->id),
                '_sort'       => optional($p->created_at)->timestamp ?? 0,
            ]);

        // 2. Online invoices — by student_id OR via the linked applicant record.
        $invoices = Invoice::where(function ($q) use ($student) {
                $q->where('student_id', $student->id);
                if ($student->applicant_id) {
                    $q->orWhere('applicant_id', $student->applicant_id);
                }
            })
            ->orderByDesc('paid_at')->orderByDesc('created_at')->get();

        $mapInvoice = fn ($i) => [
            'description' => $i->description,
            'purpose'     => ucwords(str_replace('_', ' ', $i->purpose)),
            'amount'      => money($i->chargeable()),
            'date'        => optional($i->paid_at ?: $i->created_at)->format('d M Y'),
            'reference'   => $i->reference,
            'receipt_url' => $i->status === 'paid' ? route('invoices.receipt', $i) : null,
            '_sort'       => optional($i->paid_at ?: $i->created_at)->timestamp ?? 0,
        ];

        $strip = fn ($rows) => collect($rows)->map(fn ($r) => \Illuminate\Support\Arr::except($r, '_sort'))->values();

        // Receipts = manual payments + paid invoices, newest first.
        $receipts = $payments
            ->concat($invoices->where('status', 'paid')->map($mapInvoice))
            ->sortByDesc('_sort');

        return response()->json([
            'student'  => ['name' => $student->full_name, 'reg' => $student->registration_number ?: $student->admission_number],
            'receipts' => $strip($receipts),
            'unpaid'   => $strip($invoices->where('status', '!=', 'paid')->map($mapInvoice)),
        ]);
    }

    /** JSON: a staff member's payslips, newest first. */
    public function staffPayslips(User $user)
    {
        $slips = Payslip::where('user_id', $user->id)
            ->orderByDesc('month')->get()->map(fn ($p) => [
                'month'    => \Illuminate\Support\Carbon::parse($p->month.'-01')->format('F Y'),
                'net'      => money($p->net_salary),
                'status'   => $p->status,
                'slip_url' => $p->status === 'paid' ? route('payroll.slip', $p) : null,
                'pdf_url'  => $p->status === 'paid' ? route('payroll.slip.pdf', $p) : null,
            ]);

        return response()->json([
            'staff'    => ['name' => $user->name, 'staff_id' => $user->staff_id],
            'payslips' => $slips,
        ]);
    }
}
