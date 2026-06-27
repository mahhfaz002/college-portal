<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use App\Models\User;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    /** Bursar HR hub: staff + this month's payslips. */
    public function index(Request $request)
    {
        $month = $request->query('month', now()->format('Y-m'));

        $staff = User::where('role', '!=', 'student')
            ->whereNotIn('role', ['applicant', 'superadmin'])
            ->with('departmentModel')->orderBy('name')->get();
        $slips = Payslip::where('month', $month)->get()->keyBy('user_id');

        // Staff carry a section (from their department) for filtering; those with
        // no department (cleaners, assistants, secretaries…) fall under "Others".
        $rows = $staff->map(fn ($s) => [
            'staff'   => $s,
            'slip'    => $slips->get($s->id),
            'dept'    => optional($s->departmentModel)->name,
            'section' => optional($s->departmentModel)->section ?: 'Others',
        ]);

        return view('hr.index', [
            'rows'     => $rows,
            'month'    => $month,
            'sections' => array_merge(\App\Support\Sections::ALL, ['Others']),
            'departments' => \App\Models\Department::orderBy('name')->get(['id', 'name', 'section']),
            'counts' => [
                'draft'     => $slips->where('status', 'draft')->count(),
                'in_review' => $slips->whereIn('status', ['provost_review', 'provost_forwarded', 'proprietor_review'])->count(),
                'queried'   => $slips->where('status', 'queried')->count(),
                'approved'  => $slips->where('status', 'approved')->count(),
                'paid'      => $slips->where('status', 'paid')->count(),
            ],
        ]);
    }

    /** Create / edit a single staff member's payslip for a month. */
    public function edit(Request $request, User $user)
    {
        $month = $request->query('month', now()->format('Y-m'));
        $slip = Payslip::firstOrNew(['user_id' => $user->id, 'month' => $month]);

        return view('hr.edit', compact('user', 'slip', 'month'));
    }

    public function store(Request $request, User $user)
    {
        $data = $request->validate([
            'month'                => 'required|string',
            'basic_salary'         => 'required|numeric|min:0',
            'allowances'           => 'nullable|numeric|min:0',
            'tax'                  => 'nullable|numeric|min:0|max:100',           // percentage of gross
            'contributory_savings' => 'nullable|numeric|min:0|max:100',           // percentage of gross
            'deduction_nature'     => 'nullable|array',
            'deduction_nature.*'   => 'nullable|string|max:255',
            'deduction_amount'     => 'nullable|array',
            'deduction_amount.*'   => 'nullable|numeric|min:0',
        ]);

        $deductions = [];
        foreach ($request->input('deduction_nature', []) as $i => $nature) {
            $amount = (float) ($request->input("deduction_amount.$i", 0));
            if ($nature && $amount > 0) {
                $deductions[] = ['nature' => $nature, 'amount' => $amount];
            }
        }

        $slip = Payslip::firstOrNew(['user_id' => $user->id, 'month' => $data['month']]);
        // Locked once in any review/approval stage unless it was queried back to the bursar.
        abort_if(in_array($slip->status, ['provost_review', 'provost_forwarded', 'proprietor_review', 'approved', 'paid'], true), 403, 'This payslip is locked.');

        $slip->fill([
            'basic_salary'         => $data['basic_salary'],
            'allowances'           => $data['allowances'] ?? 0,
            'tax'                  => $data['tax'] ?? 0,
            'contributory_savings' => $data['contributory_savings'] ?? 10,
            'deductions'           => $deductions,
            'status'               => 'draft',
            'flag_comment'         => null,
            'created_by'           => auth()->id(),
        ]);
        $slip->recomputeNet();
        $slip->save();

        return redirect()->route('payroll.index', ['month' => $data['month']])
            ->with('success', "Payslip saved for {$user->name} (net ".money($slip->net_salary).").");
    }

    /** Bursar submits all draft/queried payslips for the month to the Provost. */
    public function submit(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        $count = Payslip::where('month', $month)
            ->whereIn('status', ['draft', 'flagged', 'queried'])
            ->update([
                'status'          => 'provost_review',
                'submitted_at'    => now(),
                'flag_comment'    => null,
                'provost_status'  => 'pending',
                'provost_comment' => null,
            ]);

        return back()->with('success', "{$count} payslip(s) submitted to the Provost for review.");
    }

    /** Bursar initiates payment on an approved payslip. */
    public function pay(Payslip $payslip)
    {
        abort_unless($payslip->status === 'approved', 403, 'Only approved payslips can be paid.');
        $payslip->update(['status' => 'paid', 'paid_at' => now()]);

        return back()->with('success', "Salary paid to {$payslip->staff->name}. Payslip generated.");
    }

    /** Printable payslip (bursar only). */
    public function show(Payslip $payslip)
    {
        $payslip->load('staff');
        return view('hr.payslip', compact('payslip'));
    }

    /**
     * Read-only payslip detail for the Provost and Proprietor — every earning
     * and deduction exactly as the Bursar entered it, with no edit controls.
     * (GET only; the review actions remain query / forward / approve.)
     */
    public function viewSlip(Payslip $payslip)
    {
        abort_unless(auth()->user()->hasRole('provost', 'proprietor', 'bursar'), 403);
        $payslip->load('staff');

        // Where "Back" should return to, per the viewer's review queue.
        $back = match (auth()->user()->role) {
            'provost'    => route('payroll.review', ['month' => $payslip->month]),
            'proprietor' => route('payroll.approval', ['month' => $payslip->month]),
            default      => route('payroll.index', ['month' => $payslip->month]),
        };

        return view('hr.payslip_view', compact('payslip', 'back'));
    }

    /** Downloadable payslip PDF (bursar only). */
    public function downloadSlip(Payslip $payslip)
    {
        $payslip->load('staff');
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('hr.payslip_pdf', compact('payslip'));
        return $pdf->download('Payslip_'.\Illuminate\Support\Str::slug($payslip->staff->name ?? 'staff').'_'.$payslip->month.'.pdf');
    }

    // ---- Provost review ----

    public function provostReview(Request $request)
    {
        $month = $request->query('month', now()->format('Y-m'));
        $slips = Payslip::with('staff')->where('month', $month)
            ->whereIn('status', ['provost_review', 'provost_forwarded', 'proprietor_review', 'approved', 'paid', 'queried'])
            ->get();

        return view('hr.provost_review', compact('slips', 'month'));
    }

    /** Provost forwards a reviewed payslip to the Proprietor for final approval. */
    public function provostForward(Payslip $payslip)
    {
        abort_unless($payslip->status === 'provost_review', 403);
        $payslip->update([
            'status'              => 'proprietor_review',
            'provost_status'      => 'forwarded',
            'provost_reviewed_at' => now(),
            'proprietor_status'   => 'pending',
        ]);

        return back()->with('success', "{$payslip->staff->name}'s payslip forwarded to the Proprietor.");
    }

    /** Provost queries a payslip back to the Bursar for correction. */
    public function provostQuery(Request $request, Payslip $payslip)
    {
        $data = $request->validate(['comment' => 'required|string|max:1000']);
        abort_unless(in_array($payslip->status, ['provost_review', 'provost_forwarded'], true), 403);

        $payslip->update([
            'status'          => 'queried',
            'provost_status'  => 'queried',
            'provost_comment' => $data['comment'],
            'flag_comment'    => $data['comment'],
        ]);

        return back()->with('success', "Payslip queried back to the Bursar.");
    }

    /**
     * Provost relays a proprietor's query back down to the Bursar (after the
     * proprietor queried the provost).
     */
    public function provostRelayToBursar(Payslip $payslip)
    {
        abort_unless($payslip->proprietor_status === 'queried', 403);
        $payslip->update([
            'status'         => 'queried',
            'provost_status' => 'queried',
            'flag_comment'   => $payslip->proprietor_comment,
        ]);

        return back()->with('success', "Proprietor's query relayed to the Bursar.");
    }

    // ---- Proprietor final approval ----

    public function proprietorReview(Request $request)
    {
        $month = $request->query('month', now()->format('Y-m'));
        $slips = Payslip::with('staff')->where('month', $month)
            ->whereIn('status', ['proprietor_review', 'approved', 'paid'])
            ->get();

        return view('hr.proprietor_review', compact('slips', 'month'));
    }

    /** Proprietor gives final approval. Approved payslips are locked for everyone. */
    public function proprietorApprove(Payslip $payslip)
    {
        abort_unless($payslip->status === 'proprietor_review', 403);
        $payslip->update([
            'status'                 => 'approved',
            'proprietor_status'      => 'approved',
            'proprietor_approved_at' => now(),
            'approved_at'            => now(),
        ]);

        return back()->with('success', "{$payslip->staff->name}'s payslip approved. It is now locked.");
    }

    /** Proprietor queries a payslip back to the Provost. */
    public function proprietorQuery(Request $request, Payslip $payslip)
    {
        $data = $request->validate(['comment' => 'required|string|max:1000']);
        abort_unless($payslip->status === 'proprietor_review', 403);

        $payslip->update([
            'status'             => 'provost_forwarded',
            'proprietor_status'  => 'queried',
            'proprietor_comment' => $data['comment'],
        ]);

        return back()->with('success', "Payslip queried back to the Provost.");
    }
}
