<?php

namespace App\Support;

use App\Models\Exam;
use App\Models\ExamEligibility;
use App\Models\Student;

/**
 * Decides whether a student may sit an exam. Base rule = fees cleared.
 * An explicit exam-officer decision (admit / block) overrides the base rule.
 * (The attendance requirement was removed with the attendance system.)
 */
class Eligibility
{
    public static function evaluate(Student $student, Exam $exam, ?ExamEligibility $override = null): array
    {
        $feesOk = $student->feesCleared();

        $override ??= ExamEligibility::where('exam_id', $exam->id)
            ->where('student_id', $student->id)->first();

        $eligible = $feesOk;
        $overridden = false;
        if ($override) {
            $eligible = $override->status === 'eligible';
            $overridden = true;
        }

        $reasons = [];
        if (!$feesOk) {
            $reasons[] = 'Outstanding fees ('.money($student->fees_balance).')';
        }

        return [
            'eligible'   => $eligible,
            'fees_ok'    => $feesOk,
            'reasons'    => $reasons,
            'overridden' => $overridden,
            'override'   => $override,
        ];
    }
}
