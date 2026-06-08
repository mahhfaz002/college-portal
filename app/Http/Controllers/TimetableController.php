<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\TimetablePlan;
use App\Models\TimetableEntry;
use App\Support\TimetableService;
use Illuminate\Http\Request;

class TimetableController extends Controller
{
    public function __construct(private TimetableService $service)
    {
    }

    /**
     * Role-aware entry point:
     * - manage_timetable (principal/ict): config + draft/approved plans
     * - teacher: own weekly schedule
     * - student: own class timetable
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->role === 'student') {
            return $this->studentView($user);
        }
        if ($user->role === 'teacher') {
            return $this->teacherView($user);
        }

        // Principal manages; every other staff role sees the published timetable read-only.
        $canManage = $user->canManage('manage_timetable'); // principal only
        $approved = TimetablePlan::where('status', 'approved')->latest()->with('entries.subject', 'entries.teacher')->first();
        $draft = $canManage
            ? TimetablePlan::where('status', 'draft')->latest()->with('entries.subject', 'entries.teacher')->first()
            : null;
        $params = $this->service->defaultParams();
        $rows = $this->service->periodRows($params);

        return view('timetable.index', [
            'draft' => $draft,
            'approved' => $approved,
            'params' => $params,
            'rows' => $rows,
            'canManage' => $canManage,
        ]);
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'periods'        => 'nullable|integer|min:1|max:12',
            'period_minutes' => 'nullable|integer|min:20|max:120',
            'start_time'     => 'nullable',
            'break_after'    => 'nullable|integer|min:0',
            'break_minutes'  => 'nullable|integer|min:0|max:60',
        ]);

        $plan = $this->service->generate(array_filter($data, fn ($v) => $v !== null), auth()->id());

        $msg = $plan->entries->isEmpty()
            ? 'No timetable could be built — assign teachers to classes & subjects first.'
            : "Draft timetable generated ({$plan->engine}). Review and approve to publish.";

        return redirect()->route('timetable.index')->with('success', $msg);
    }

    public function approve(TimetablePlan $plan)
    {
        // Approving a plan supersedes any previously approved plan.
        TimetablePlan::where('status', 'approved')->update(['status' => 'superseded']);
        $plan->update(['status' => 'approved', 'approved_at' => now()]);

        return back()->with('success', 'Timetable approved and published to teachers and students.');
    }

    public function destroy(TimetablePlan $plan)
    {
        abort_unless($plan->status === 'draft', 403);
        $plan->delete();
        return back()->with('success', 'Draft timetable discarded.');
    }

    // ---- role views ----

    private function teacherView($user)
    {
        $plan = TimetablePlan::where('status', 'approved')->latest()->first();
        $entries = $plan
            ? TimetableEntry::where('plan_id', $plan->id)->where('teacher_id', $user->id)
                ->with('subject')->orderBy('period_no')->get()
            : collect();

        return view('timetable.personal', [
            'title' => 'My Teaching Schedule',
            'plan' => $plan,
            'entries' => $entries,
            'params' => $plan ? $plan->params : $this->service->defaultParams(),
            'rows' => $this->service->periodRows($plan ? $plan->params : $this->service->defaultParams()),
            'showClass' => true,
        ]);
    }

    private function studentView($user)
    {
        $student = Student::where('email', $user->email)->first();
        $classArm = $student->class_arm ?? null;

        $plan = TimetablePlan::where('status', 'approved')->latest()->first();
        $entries = ($plan && $classArm)
            ? TimetableEntry::where('plan_id', $plan->id)->where('class_arm', $classArm)
                ->with('subject', 'teacher')->orderBy('period_no')->get()
            : collect();

        return view('timetable.personal', [
            'title' => 'My Class Timetable'.($classArm ? " — {$classArm}" : ''),
            'plan' => $plan,
            'entries' => $entries,
            'params' => $plan ? $plan->params : $this->service->defaultParams(),
            'rows' => $this->service->periodRows($plan ? $plan->params : $this->service->defaultParams()),
            'showClass' => false,
        ]);
    }
}
