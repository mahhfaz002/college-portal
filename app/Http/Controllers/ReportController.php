<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Score;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function downloadPdf($studentId)
    {
        $student = Student::findOrFail($studentId);

        // Staff (view_students) may pull any report; a student may pull only
        // their own (matched by the email linking their login to the record).
        $user = auth()->user();
        $isStaff = $user->canManage('view_students');
        $isOwner = $user->role === 'student' && $student->email && $student->email === $user->email;
        abort_unless($isStaff || $isOwner, 403);

        // Students only ever see published results.
        $scoreQuery = Score::where('student_id', $student->id);
        if (!$isStaff) {
            $scoreQuery->where('status', 'published');
        }
        $scores = $scoreQuery->get();
        $totalScores = $scores->sum(fn($s) => $s->ca_score + $s->exam_score);
        $average = $scores->count() > 0 ? $totalScores / $scores->count() : 0;

        // Load the view that contains the HTML for the report card
        $pdf = Pdf::loadView('reports.student_pdf', compact('student', 'scores', 'average'));

        // Download the file
        return $pdf->download($student->full_name . '_Report_Card.pdf');
    }
}
