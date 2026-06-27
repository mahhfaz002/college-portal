<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TermController extends Controller
{
    public const TERMS = ['First Semester', 'Second Semester'];

    /**
     * Principal sets the active session + term and its start/end dates.
     * Writes to settings, which every dashboard reads via setting().
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'current_session' => 'required|string|max:20',   // e.g. 2025/2026
            'current_term'    => 'required|in:'.implode(',', self::TERMS),
            'term_start'      => 'required|date',
            'term_end'        => 'required|date|after:term_start',
        ]);

        Setting::set('current_session', $data['current_session'], 'academic');
        Setting::set('current_term', $data['current_term'], 'academic');
        Setting::set('term_start', $data['term_start'], 'academic');
        Setting::set('term_end', $data['term_end'], 'academic');

        ActivityLog::record("Set {$data['current_term']} ({$data['current_session']})", 'term.update');

        return back()->with('success', "Active semester set to {$data['current_term']}, {$data['current_session']}. All dashboards updated.");
    }

    /**
     * Clear every teacher's class/subject assignments so the principal can
     * reassign for the new term. Scores already entered are untouched.
     */
    public function clearAssignments(Request $request)
    {
        DB::table('class_teacher')->delete();
        DB::table('subject_teacher')->delete();

        ActivityLog::record('Cleared all lecturer assignments for new semester', 'term.clear');

        return back()->with('success', 'All lecturer course assignments cleared. You can now reassign for the new semester.');
    }

    /* --------------------------- Semester control --------------------------- */

    /** Registrar's semester-control panel. */
    public function semesterControl()
    {
        return view('admin.semester_control', [
            'status'             => setting('semester_status', 'active'),
            'breakStart'         => setting('break_start'),
            'breakEnd'           => setting('break_end'),
            'nextSemesterStart'  => setting('next_semester_start'),
            'nextSessionStart'   => setting('next_session_start'),
            'currentTerm'        => setting('current_term', 'First Semester'),
            'currentSession'     => setting('current_session', '2025/2026'),
        ]);
    }

    /**
     * Registrar marks the semester done: sets break dates + the countdown to the
     * next semester (and, at session end, the next session start). Every
     * dashboard reads these via the semester-countdown partial.
     */
    public function endSemester(Request $request)
    {
        $data = $request->validate([
            'break_start'         => 'required|date',
            'next_semester_start' => 'required|date|after:break_start',
            'next_session_start'  => 'nullable|date',
        ]);

        Setting::set('semester_status', 'break', 'academic');
        Setting::set('break_start', $data['break_start'], 'academic');
        Setting::set('next_semester_start', $data['next_semester_start'], 'academic');
        Setting::set('break_end', $data['next_semester_start'], 'academic');
        if (!empty($data['next_session_start'])) {
            Setting::set('next_session_start', $data['next_session_start'], 'academic');
        }

        ActivityLog::record('Ended semester; break started', 'term.end_semester');

        return back()->with('success', 'Semester closed. The break countdown is now live on all dashboards.');
    }

    /**
     * Transition into the new semester/session. Advances the active term (and
     * session at year end), clears lecturer assignments for fresh allocation,
     * and re-activates the system. Can be triggered manually by the registrar
     * once the countdown elapses.
     */
    public function transition(Request $request)
    {
        $data = $request->validate([
            'new_term'    => 'required|in:'.implode(',', self::TERMS),
            'new_session' => 'required|string|max:20',
        ]);

        Setting::set('current_term', $data['new_term'], 'academic');
        Setting::set('current_session', $data['new_session'], 'academic');
        Setting::set('semester_status', 'active', 'academic');
        Setting::set('break_start', null, 'academic');
        Setting::set('break_end', null, 'academic');
        Setting::set('next_semester_start', null, 'academic');

        // Fresh course allocation for the new semester.
        DB::table('subject_teacher')->delete();

        ActivityLog::record("Transitioned to {$data['new_term']} {$data['new_session']}", 'term.transition');

        return back()->with('success', "System transitioned to {$data['new_term']}, {$data['new_session']}. New course allocation can now begin.");
    }
}
