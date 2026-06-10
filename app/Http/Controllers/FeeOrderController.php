<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\FeeOrder;
use App\Models\Invoice;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Bursar payment-order engine. Assigns a fee to a scope of students
 * (single / bulk / level / program / department / all) and fans it out to one
 * online-payable Invoice per student.
 */
class FeeOrderController extends Controller
{
    /** Orders list + create form + filtered student directory. */
    public function index(Request $request)
    {
        $orders = FeeOrder::withCount(['invoices', 'invoices as paid_count' => fn ($q) => $q->where('status', 'paid')])
            ->latest()->get();

        $departments = Department::orderBy('name')->get();
        $programs    = Program::with('department')->orderBy('name')->get();
        $levels      = Student::query()->select('level')->whereNotNull('level')->distinct()->pluck('level');

        // Filtered student directory (bursar can browse by dept/program/level).
        $students = Student::with(['department', 'program'])
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('program_id'), fn ($q) => $q->where('program_id', $request->program_id))
            ->when($request->filled('level'), fn ($q) => $q->where('level', $request->level))
            ->orderBy('full_name')->get();

        return view('fees.orders.index', compact('orders', 'departments', 'programs', 'levels', 'students'));
    }

    /** Create a payment order and fan it out to invoices. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:150',
            'description' => 'nullable|string|max:255',
            'amount'      => 'required|numeric|min:1',
            'scope_type'  => 'required|in:all,department,program,level,students',
            'department_id' => 'nullable|exists:departments,id',
            'program_id'    => 'nullable|exists:programs,id',
            'level'         => 'nullable|string',
            'student_ids'   => 'nullable|array',
            'student_ids.*' => 'integer',
        ]);

        [$targets, $label] = $this->resolveTargets($data);

        if ($targets->isEmpty()) {
            return back()->with('error', 'No students match the selected target.')->withInput();
        }

        $order = DB::transaction(function () use ($data, $targets, $label) {
            $order = FeeOrder::create([
                'college_id'  => current_college_id(),
                'created_by'  => auth()->id(),
                'title'       => $data['title'],
                'description' => $data['description'] ?? null,
                'amount'      => $data['amount'],
                'scope_type'  => $data['scope_type'],
                'scope_label' => $label,
                'students_count' => $targets->count(),
            ]);

            $userByEmail = User::whereIn('email', $targets->pluck('email')->filter())
                ->pluck('id', 'email');

            foreach ($targets as $student) {
                Invoice::create([
                    'college_id'   => $student->college_id ?? current_college_id(),
                    'student_id'   => $student->id,
                    'user_id'      => $userByEmail[$student->email] ?? null,
                    'program_id'   => $student->program_id,
                    'fee_order_id' => $order->id,
                    'purpose'      => 'fee',
                    'description'  => $data['title'],
                    'amount'       => $data['amount'],
                    'payer_email'  => $student->email,
                    'status'       => 'pending',
                    'reference'    => PaystackService::reference('FEE'),
                ]);
            }

            return $order;
        });

        ActivityLog::record("Created payment order '{$order->title}' for {$order->students_count} student(s)", 'fees.order');

        return redirect()->route('fees.orders.show', $order)
            ->with('success', "Payment order created for {$order->students_count} student(s).");
    }

    /** One order: per-student paid / unpaid status + receipts. */
    public function show(FeeOrder $feeOrder)
    {
        $invoices = $feeOrder->invoices()->with('student')->get();

        return view('fees.orders.show', ['order' => $feeOrder, 'invoices' => $invoices]);
    }

    /** Resolve the target students + a human label from the scope. */
    private function resolveTargets(array $data): array
    {
        $q = Student::query();

        switch ($data['scope_type']) {
            case 'department':
                $q->where('department_id', $data['department_id']);
                $label = 'Department: '.optional(Department::find($data['department_id']))->name;
                break;
            case 'program':
                $q->where('program_id', $data['program_id']);
                $label = 'Program: '.optional(Program::find($data['program_id']))->name;
                break;
            case 'level':
                $q->where('level', $data['level']);
                $label = 'Level: '.$data['level'];
                break;
            case 'students':
                $q->whereIn('id', $data['student_ids'] ?? []);
                $label = 'Selected students';
                break;
            default:
                $label = 'All students';
        }

        return [$q->get(), $label];
    }
}
