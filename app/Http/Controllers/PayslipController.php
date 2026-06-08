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

        $staff = User::where('role', '!=', 'student')->orderBy('name')->get();
        $slips = Payslip::where('month', $month)->get()->keyBy('user_id');

        $rows = $staff->map(fn ($s) => ['staff' => $s, 'slip' => $slips->get($s->id)]);

        return view('hr.index', [
            'rows'  => $rows,
            'month' => $month,
            'counts' => [
                'draft'     => $slips->where('status', 'draft')->count(),
                'submitted' => $slips->where('status', 'submitted')->count(),
                'flagged'   => $slips->where('status', 'flagged')->count(),
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
            'month'              => 'required|string',
            'basic_salary'       => 'required|numeric|min:0',
            'allowances'         => 'nullable|numeric|min:0',
            'tax'                => 'nullable|numeric|min:0',
            'deduction_nature'   => 'nullable|array',
            'deduction_nature.*' => 'nullable|string|max:255',
            'deduction_amount'   => 'nullable|array',
            'deduction_amount.*' => 'nullable|numeric|min:0',
        ]);

        $deductions = [];
        foreach ($request->input('deduction_nature', []) as $i => $nature) {
            $amount = (float) ($request->input("deduction_amount.$i", 0));
            if ($nature && $amount > 0) {
                $deductions[] = ['nature' => $nature, 'amount' => $amount];
            }
        }

        $slip = Payslip::firstOrNew(['user_id' => $user->id, 'month' => $data['month']]);
        // Locked once submitted/approved unless it was flagged back to the bursar.
        abort_if(in_array($slip->status, ['submitted', 'approved', 'paid'], true), 403, 'This payslip is locked.');

        $slip->fill([
            'basic_salary' => $data['basic_salary'],
            'allowances'   => $data['allowances'] ?? 0,
            'tax'          => $data['tax'] ?? 0,
            'deductions'   => $deductions,
            'status'       => 'draft',
            'flag_comment' => null,
            'created_by'   => auth()->id(),
        ]);
        $slip->recomputeNet();
        $slip->save();

        return redirect()->route('payroll.index', ['month' => $data['month']])
            ->with('success', "Payslip saved for {$user->name} (net ".money($slip->net_salary).").");
    }

    /** Bursar submits all draft/flagged payslips for the month to the principal. */
    public function submit(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        $count = Payslip::where('month', $month)
            ->whereIn('status', ['draft', 'flagged'])
            ->update(['status' => 'submitted', 'submitted_at' => now(), 'flag_comment' => null]);

        return back()->with('success', "{$count} payslip(s) submitted to the Principal for approval.");
    }

    /** Bursar initiates payment on an approved payslip. */
    public function pay(Payslip $payslip)
    {
        abort_unless($payslip->status === 'approved', 403, 'Only approved payslips can be paid.');
        $payslip->update(['status' => 'paid', 'paid_at' => now()]);

        return back()->with('success', "Salary paid to {$payslip->staff->name}.");
    }

    // ---- Principal review ----

    public function review(Request $request)
    {
        $month = $request->query('month', now()->format('Y-m'));
        $slips = Payslip::with('staff')->where('month', $month)
            ->whereIn('status', ['submitted', 'flagged', 'approved', 'paid'])
            ->get();

        return view('hr.review', compact('slips', 'month'));
    }

    public function approve(Payslip $payslip)
    {
        abort_unless(in_array($payslip->status, ['submitted', 'flagged'], true), 403);
        $payslip->update(['status' => 'approved', 'approved_at' => now(), 'flag_comment' => null]);

        return back()->with('success', "{$payslip->staff->name}'s payslip approved.");
    }

    public function flag(Request $request, Payslip $payslip)
    {
        $data = $request->validate(['flag_comment' => 'required|string|max:1000']);
        abort_unless(in_array($payslip->status, ['submitted', 'flagged'], true), 403);

        $payslip->update(['status' => 'flagged', 'flag_comment' => $data['flag_comment']]);

        return back()->with('success', "Payslip flagged back to the Bursar with your note.");
    }
}
