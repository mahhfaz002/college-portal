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
        $levels      = Student::query()->select('level')->whereNotNull('level')->distinct()->pluck('level');

        // Programmes shaped for the cascading scope selector (section → dept → programme → level).
        $programs = Program::with('department')->orderBy('name')->get()->map(fn ($p) => [
            'id'      => $p->id,
            'name'    => $p->name,
            'type'    => $p->program_type ?? 'UG',
            'levels'  => (int) ($p->levels ?: 1),
            'dept_id' => $p->department_id,
            'section' => optional($p->department)->section,
        ]);

        // Filtered student directory (bursar can browse by section/dept/program/level).
        $students = Student::with(['department', 'program'])
            ->when($request->filled('section'), fn ($q) => $q->whereIn('department_id', Department::where('section', $request->section)->pluck('id')))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('program_id'), fn ($q) => $q->where('program_id', $request->program_id))
            ->when($request->filled('level'), fn ($q) => $q->where('level', $request->level))
            ->orderBy('full_name')->get();

        return view('fees.orders.index', [
            'orders'      => $orders,
            'departments' => $departments,
            'programs'    => $programs,
            'levels'      => $levels,
            'students'    => $students,
            'sections'    => \App\Support\Sections::ALL,
        ]);
    }

    /** Create a payment order and fan it out to invoices. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:150',
            'description' => 'nullable|string|max:255',
            'amount'      => 'required|numeric|min:1',
            // mode 'filter' = optional cascade (any depth); 'students' = hand-picked.
            'mode'          => 'required|in:filter,students',
            'section'       => 'nullable|string',
            'department_id' => 'nullable|exists:departments,id',
            'program_id'    => 'nullable|exists:programs,id',
            // Targeting a programme REQUIRES a level, so an order can never spill
            // across every level of that course of study (a 300L order must not
            // reach 100L students of the same programme).
            'level'         => 'nullable|string|required_with:program_id',
            'student_ids'   => 'nullable|array',
            'student_ids.*' => 'integer',
        ], [
            'level.required_with' => 'Select the level too — a payment order for a course of study must target a specific level.',
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
                'scope_type'  => $data['mode'],
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

    /**
     * Resolve the target students + a human label.
     *
     * 'students' mode = a hand-picked list. 'filter' mode = an OPTIONAL cascade:
     * the bursar may set any of section / department / programme / level and
     * stop at any depth — every unset level is left unconstrained. Setting
     * nothing targets the whole college.
     */
    private function resolveTargets(array $data): array
    {
        if (($data['mode'] ?? 'filter') === 'students') {
            return [Student::whereIn('id', $data['student_ids'] ?? [])->get(), 'Selected students'];
        }

        $q = Student::query();
        $labels = [];

        if (!empty($data['section'])) {
            $deptIds = Department::where('section', $data['section'])->pluck('id');
            $q->whereIn('department_id', $deptIds);
            $labels[] = $data['section'];
        }
        if (!empty($data['department_id'])) {
            $q->where('department_id', $data['department_id']);
            $labels[] = optional(Department::find($data['department_id']))->name;
        }
        if (!empty($data['program_id'])) {
            $q->where('program_id', $data['program_id']);
            $labels[] = optional(Program::find($data['program_id']))->name;
        }
        if (!empty($data['level'])) {
            $q->where('level', $data['level']);
            $labels[] = 'L'.$data['level'];
        }

        return [$q->get(), $labels ? implode(' · ', array_filter($labels)) : 'All students'];
    }
}
