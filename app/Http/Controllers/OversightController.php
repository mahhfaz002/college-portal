<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Invoice;
use App\Models\Program;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Read-only college oversight for the Proprietor and Provost.
 *
 * The fee-breakdown screen lets them slice fees actually collected by
 * department → course of study → level, and see, for any chosen level, the
 * roll of students with a paid / pending (registration fee not paid) badge.
 * Everything here is strictly view-only and college-scoped by the global
 * CollegeScope on the underlying models.
 */
class OversightController extends Controller
{
    /** Levels offered (covers the common tertiary range; falls back to data). */
    private const LEVELS = ['100', '200', '300', '400', '500', '600'];

    public function fees(Request $request)
    {
        $deptId    = $request->query('department_id');
        $programId = $request->query('program_id');
        $level     = $request->query('level');

        $departments = Department::orderBy('name')->get(['id', 'name', 'acronym']);
        $programs    = Program::with('department')->orderBy('name')->get(['id', 'name', 'department_id']);

        // Levels present in the data, unioned with the standard set, sorted.
        $dataLevels = Student::query()->whereNotNull('level')->distinct()->pluck('level')->all();
        $levels = collect(self::LEVELS)->merge($dataLevels)
            ->map(fn ($l) => (string) $l)->unique()->filter()->sort()->values();

        // The filtered cohort.
        $students = Student::query()
            ->when($deptId, fn ($q) => $q->where('department_id', $deptId))
            ->when($programId, fn ($q) => $q->where('program_id', $programId))
            ->when($level, fn ($q) => $q->where('level', $level))
            ->with(['program:id,name,department_id', 'department:id,name'])
            ->orderBy('full_name')
            ->get();

        $studentIds = $students->pluck('id');

        // Fees actually collected per student (paid invoices only). Keyed by
        // student_id so the per-row badge and the cohort total share one query.
        $paidByStudent = $studentIds->isEmpty()
            ? collect()
            : Invoice::where('status', 'paid')
                ->whereIn('student_id', $studentIds)
                ->select('student_id', DB::raw('SUM(amount) as total'))
                ->groupBy('student_id')
                ->pluck('total', 'student_id');

        // Decorate each student with a paid / pending status. "Paid" = has at
        // least one settled invoice OR carries no outstanding balance; otherwise
        // the registration fee is still pending.
        $rows = $students->map(function ($s) use ($paidByStudent) {
            $collected = (float) ($paidByStudent[$s->id] ?? 0);
            $paid      = $collected > 0 || (float) $s->fees_balance <= 0;

            return [
                'student'    => $s,
                'collected'  => $collected,
                'paid'       => $paid,
            ];
        });

        $paidCount    = $rows->where('paid', true)->count();
        $pendingCount = $rows->where('paid', false)->count();
        $totalCollected = (float) $paidByStudent->sum();

        // Department-level summary (fees collected + headcount) shown when not
        // drilled into a single department — the "big picture" view.
        $deptSummary = collect();
        if (!$deptId) {
            $countsByDept = Student::select('department_id', DB::raw('COUNT(*) as total'))
                ->groupBy('department_id')->pluck('total', 'department_id');

            $collectedByDept = Invoice::where('invoices.status', 'paid')
                ->whereNotNull('student_id')
                ->join('students', 'students.id', '=', 'invoices.student_id')
                ->select('students.department_id', DB::raw('SUM(invoices.amount) as total'))
                ->groupBy('students.department_id')
                ->pluck('total', 'students.department_id');

            $deptSummary = $departments->map(fn ($d) => [
                'department' => $d,
                'students'   => (int) ($countsByDept[$d->id] ?? 0),
                'collected'  => (float) ($collectedByDept[$d->id] ?? 0),
            ]);
        }

        return view('oversight.fees', [
            'departments'    => $departments,
            'programs'       => $programs,
            'levels'         => $levels,
            'filters'        => ['department_id' => $deptId, 'program_id' => $programId, 'level' => $level],
            'rows'           => $rows,
            'paidCount'      => $paidCount,
            'pendingCount'   => $pendingCount,
            'totalCollected' => $totalCollected,
            'deptSummary'    => $deptSummary,
        ]);
    }
}
