<?php

namespace App\Support;

use App\Models\Applicant;
use App\Models\Exam;
use App\Models\ExamSubmission;
use App\Models\Payslip;
use App\Models\ResultQuery;
use App\Models\Student;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

/**
 * Cheap, role-aware "pending actions" indicator for the nav bell.
 * Each entry: ['count' => int, 'url' => route, 'label' => string].
 */
class Notifications
{
    public static function forUser(?User $user): array
    {
        if (!$user) {
            return ['count' => 0, 'url' => '#', 'label' => ''];
        }

        return match ($user->role) {
            'principal' => self::wrap(
                Schema::hasTable('payslips') ? Payslip::where('status', 'submitted')->count() : 0,
                route('payroll.review'), 'payslips awaiting approval'
            ),
            'accountant' => self::wrap(
                Schema::hasTable('payslips') ? Payslip::where('status', 'flagged')->count() : 0,
                route('payroll.index'), 'payslips flagged for correction'
            ),
            'exam_officer' => self::wrap(
                ResultQuery::where('status', 'open')->count(),
                route('exams.queries'), 'open result queries'
            ),
            'ict' => self::wrap(
                SupportTicket::where('status', '!=', 'resolved')->count(),
                route('support.index'), 'open support tickets'
            ),
            'admin' => self::wrap(
                Applicant::where('status', 'pending')->count(),
                route('admission.admin'), 'pending applications'
            ),
            'teacher' => self::teacherActions($user),
            'student' => self::studentActions($user),
            default => ['count' => 0, 'url' => '#', 'label' => ''],
        };
    }

    private static function teacherActions(User $user): array
    {
        $subjectIds = $user->subjects()->pluck('subjects.id');
        if ($subjectIds->isEmpty()) {
            return self::wrap(0, route('dashboard'), '');
        }
        $toAuthor = Exam::whereIn('subject_id', $subjectIds)->where('status', 'draft')->count();
        $toGrade = Exam::whereIn('subject_id', $subjectIds)
            ->whereIn('status', ['released', 'grading'])
            ->whereHas('submissions')->count();

        return self::wrap($toAuthor + $toGrade, route('dashboard'), 'exam tasks');
    }

    private static function studentActions(User $user): array
    {
        $student = Student::where('email', $user->email)->first();
        if (!$student) {
            return self::wrap(0, route('dashboard'), '');
        }
        $available = Exam::where('status', 'released')->get()
            ->filter(fn ($e) => in_array($student->class_arm, $e->class_arms, true))
            ->count();

        return self::wrap($available, route('myexams.available'), 'exams available');
    }

    private static function wrap(int $count, string $url, string $label): array
    {
        return ['count' => $count, 'url' => $url, 'label' => $label];
    }
}
