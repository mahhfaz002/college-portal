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
}
