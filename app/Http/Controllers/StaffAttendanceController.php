<?php

namespace App\Http\Controllers;

use App\Models\StaffAttendance;
use App\Models\ClassAttendanceLog;
use App\Models\User;
use Illuminate\Http\Request;

class StaffAttendanceController extends Controller
{
    /**
     * Clock in for the day (idempotent — keeps the earliest time).
     */
    public function clockIn()
    {
        $record = $this->todayRecord();

        if (!$record->clock_in) {
            $record->update(['clock_in' => now()]);
            return back()->with('success', 'Clocked in at '.$record->fresh()->clock_in->format('h:i A'));
        }

        return back()->with('success', 'Already clocked in at '.$record->clock_in->format('h:i A'));
    }

    /**
     * Clock out for the day.
     */
    public function clockOut()
    {
        $record = $this->todayRecord();

        if (!$record->clock_in) {
            $record->clock_in = now();
        }
        $record->clock_out = now();
        $record->save();

        return back()->with('success', 'Clocked out at '.$record->fresh()->clock_out->format('h:i A'));
    }

    /**
     * Fetch (or create) the current user's clock record for today.
     * Uses whereDate so the date-cast column matches regardless of time part.
     */
    private function todayRecord(): StaffAttendance
    {
        $record = StaffAttendance::where('user_id', auth()->id())
            ->whereDate('work_date', now()->toDateString())
            ->first();

        return $record ?: StaffAttendance::create([
            'user_id'   => auth()->id(),
            'work_date' => now()->toDateString(),
        ]);
    }

    /**
     * Principal view: who is active in class today + classes attended vs missed.
     */
    public function report(Request $request)
    {
        $date = $request->query('date', now()->toDateString());

        $teachers = User::where('role', 'teacher')
            ->with('classes')
            ->orderBy('name')
            ->get();

        $clockedIn = StaffAttendance::whereDate('work_date', $date)
            ->whereNotNull('clock_in')
            ->pluck('clock_in', 'user_id');

        $logsToday = ClassAttendanceLog::whereDate('log_date', $date)->get()->groupBy('user_id');

        $rows = $teachers->map(function ($teacher) use ($clockedIn, $logsToday) {
            $assigned = $teacher->classes->pluck('name');
            $attended = optional($logsToday->get($teacher->id))->pluck('class_arm') ?? collect();

            return [
                'teacher'   => $teacher,
                'clock_in'  => $clockedIn->get($teacher->id),
                'assigned'  => $assigned,
                'attended'  => $attended,
                'missed'    => $assigned->diff($attended)->values(),
                'is_active' => $attended->isNotEmpty() || $clockedIn->has($teacher->id),
            ];
        });

        return view('staff.attendance', compact('rows', 'date'));
    }
}
