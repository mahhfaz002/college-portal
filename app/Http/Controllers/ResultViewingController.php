<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\ResultAccessPayment;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Services\PaystackService;
use Illuminate\Http\Request;

class ResultViewingController extends Controller
{
    public const RESULT_VIEWING_FEE = 1000;

    public function index(Request $request)
    {
        $student = Student::where('email', auth()->user()->email)->firstOrFail();

        $currentTerm = setting('current_term', 'First Semester');
        $currentSession = setting('current_session', '2025/2026');

        $selectedTerm = $request->query('term', $currentTerm);
        $selectedSession = $request->query('session', $currentSession);
        $selectedLevel = $request->query('level', $student->level);

        $hasTransmittedResults = Score::where('student_id', $student->id)
            ->where('term', $selectedTerm)
            ->where('session', $selectedSession)
            ->whereNotNull('transmitted_at')
            ->exists();

        $hasPaid = ResultAccessPayment::where('student_id', $student->id)
            ->where('term', $selectedTerm)
            ->where('session', $selectedSession)
            ->whereNotNull('paid_at')
            ->exists();

        $hasPendingFees = Invoice::where('student_id', $student->id)
            ->where('status', 'pending')
            ->where('purpose', '!=', 'result_viewing')
            ->exists();

        $scores = collect();
        $feeBlocked = false;

        if ($hasTransmittedResults && $hasPaid) {
            if ($hasPendingFees) {
                $feeBlocked = true;
            } else {
                $scores = Score::where('student_id', $student->id)
                    ->where('term', $selectedTerm)
                    ->where('session', $selectedSession)
                    ->whereNotNull('transmitted_at')
                    ->with('subject')
                    ->get();
            }
        }

        $pendingInvoice = null;
        if ($hasTransmittedResults && !$hasPaid) {
            $pendingInvoice = Invoice::where('student_id', $student->id)
                ->where('purpose', 'result_viewing')
                ->where('status', 'pending')
                ->whereRaw("description LIKE ?", ["%{$selectedTerm}%{$selectedSession}%"])
                ->first();
        }

        $availableSessions = Score::where('student_id', $student->id)
            ->whereNotNull('transmitted_at')
            ->select('term', 'session')
            ->distinct()
            ->get();

        $availableLevels = Subject::whereIn('id',
            Score::where('student_id', $student->id)
                ->whereNotNull('transmitted_at')
                ->where('term', $selectedTerm)
                ->where('session', $selectedSession)
                ->pluck('subject_id')
        )->distinct()->pluck('level')->sort()->values();

        return view('results.student_index', compact(
            'student', 'scores', 'hasTransmittedResults', 'hasPaid', 'hasPendingFees',
            'feeBlocked', 'pendingInvoice', 'selectedTerm', 'selectedSession', 'selectedLevel',
            'currentTerm', 'currentSession', 'availableSessions', 'availableLevels'
        ));
    }

    public function pay(Request $request)
    {
        $request->validate([
            'term'    => 'required|string',
            'session' => 'required|string',
        ]);

        $student = Student::where('email', auth()->user()->email)->firstOrFail();

        $alreadyPaid = ResultAccessPayment::where('student_id', $student->id)
            ->where('term', $request->term)
            ->where('session', $request->session)
            ->whereNotNull('paid_at')
            ->exists();

        if ($alreadyPaid) {
            return redirect()->route('results.student.index', ['term' => $request->term, 'session' => $request->session])
                ->with('success', 'You have already paid for this semester\'s results.');
        }

        $existing = Invoice::where('student_id', $student->id)
            ->where('purpose', 'result_viewing')
            ->where('status', 'pending')
            ->whereRaw("description LIKE ?", ["%{$request->term}%{$request->session}%"])
            ->first();

        $invoice = $existing ?: Invoice::create([
            'college_id'  => $student->college_id,
            'student_id'  => $student->id,
            'user_id'     => auth()->id(),
            'purpose'     => 'result_viewing',
            'description' => "Result viewing fee — {$request->term}, {$request->session}",
            'amount'      => self::RESULT_VIEWING_FEE,
            'payer_email' => $student->email,
            'status'      => 'pending',
            'reference'   => PaystackService::reference('RES'),
        ]);

        return redirect()->route('payments.checkout', $invoice);
    }
}
