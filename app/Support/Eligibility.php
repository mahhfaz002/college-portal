<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\Exam;
use App\Models\ExamEligibility;
use App\Models\Student;

/**
 * Decides whether a student may sit an exam. Base rule = fees cleared AND
 * attendance at/above the configured minimum. An explicit exam-officer
 * decision (admit / block) overrides the base rule.
 */
class Eligibility
{
    public static function evaluate(Student $student, Exam $exam, ?ExamEligibility $override = null): array
    {
        $present = Attendance::where('student_id', $student->id)
            ->whereIn('status', ['present', 'late'])->count();
        $total = Attendance::where('student_id', $student->id)->count();
        $attPct = $total > 0 ? (int) round($present / $total * 100) : 100;

        $minAtt = (int) setting('min_attendance_percent', 75);
        $feesOk = $student->feesCleared();
        $attOk  = $attPct >= $minAtt;

        $override ??= ExamEligibility::where('exam_id', $exam->id)
            ->where('student_id', $student->id)->first();

        $eligible = $feesOk && $attOk;
        $overridden = false;
        if ($override) {
            $eligible = $override->status === 'eligible';
            $overridden = true;
        }

        $reasons = [];
        if (!$feesOk) $reasons[] = 'Outstanding fees ('.money($student->fees_balance).')';
        if (!$attOk)  $reasons[] = 'Low attendance ('.$attPct.'%)';

        return [
            'eligible'   => $eligible,
            'fees_ok'    => $feesOk,
            'attendance_ok' => $attOk,
            'attendance_pct' => $attPct,
            'reasons'    => $reasons,
            'overridden' => $overridden,
            'override'   => $override,
        ];
    }
}
