<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\ExamCycle;
use Illuminate\Http\Request;

/**
 * Exam Officer "Exam Mode": setting the exam start date opens the cycle,
 * notifies everyone and starts the countdown timers across all dashboards.
 * The question-submission deadline is fixed at 5 days before exams start.
 */
class ExamModeController extends Controller
{
    public function activate(Request $request)
    {
        $data = $request->validate([
            'title'         => 'nullable|string|max:120',
            // At least 6 days out so the 5-day submission window is in the future.
            'exam_start_at' => 'required|date|after:'.now()->addDays(ExamCycle::SUBMISSION_LEAD_DAYS)->format('Y-m-d H:i:s'),
        ]);

        $start = \Illuminate\Support\Carbon::parse($data['exam_start_at']);
        $title = $data['title'] ?: 'Examinations';

        // Supersede any currently-active cycle for this college.
        ExamCycle::where('status', 'active')->update(['status' => 'closed']);

        $cycle = ExamCycle::create([
            'college_id'             => current_college_id(),
            'title'                  => $title,
            'exam_start_at'          => $start,
            'submission_deadline_at' => $start->copy()->subDays(ExamCycle::SUBMISSION_LEAD_DAYS),
            'status'                 => 'active',
            'created_by'             => auth()->id(),
        ]);

        // Notify everyone (staff + students) via an announcement.
        Announcement::create([
            'user_id'      => auth()->id(),
            'title'        => "Exam Mode activated — {$title}",
            'body'         => "Examinations begin {$start->format('D, d M Y g:ia')}. Lecturers must submit exam questions on or before {$cycle->submission_deadline_at->format('D, d M Y g:ia')} (5 days before exams).",
            'audience'     => 'all',
            'is_published' => true,
        ]);

        ActivityLog::record("Activated Exam Mode '{$title}' (exams {$start->format('d M Y')})", 'exam.mode');

        return back()->with('success', "Exam Mode activated. Countdown to {$start->format('d M Y g:ia')} is now live on every dashboard.");
    }

    public function close(ExamCycle $examCycle)
    {
        $examCycle->update(['status' => 'closed']);

        return back()->with('success', 'Exam Mode closed.');
    }
}
