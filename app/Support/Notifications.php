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
            'bursar' => self::wrap(
                Schema::hasTable('payslips') ? Payslip::where('status', 'flagged')->count() : 0,
                route('payroll.index'), 'payslips flagged for correction'
            ),
            'exam_officer' => self::wrap(
                ResultQuery::where('status', 'open')->count(),
                route('exams.queries'), 'open result queries'
            ),
            'mis' => self::wrap(
                SupportTicket::where('status', '!=', 'resolved')->count(),
                route('support.index'), 'open support tickets'
            ),
            'admission_officer' => self::wrap(
                Applicant::where('status', 'pending')->count(),
                route('admission.admin'), 'pending applications'
            ),
            'lecturer' => self::teacherActions($user),
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
        // Online exam-taking has been removed; students have no exam actions here.
        return self::wrap(0, route('dashboard'), '');
    }

    private static function wrap(int $count, string $url, string $label): array
    {
        return ['count' => $count, 'url' => $url, 'label' => $label];
    }

    /**
     * Full role-aware notification feed for the dedicated notifications page.
     * Each item: ['icon','title','detail','url','time'(Carbon|null)].
     */
    public static function feedFor(?User $user): array
    {
        if (!$user) {
            return [];
        }

        $items = [];

        // Announcements visible to everyone in their lane.
        if (Schema::hasTable('announcements')) {
            $student = $user->role === 'student' ? Student::where('email', $user->email)->first() : null;
            $classArm = $student->class_arm ?? null;
            $query = \App\Models\Announcement::query();
            if (method_exists(\App\Models\Announcement::class, 'scopeVisibleTo')) {
                $query->visibleTo($user->role, $classArm);
            }
            foreach ($query->latest()->limit(15)->get() as $a) {
                $items[] = [
                    'icon'   => '📢',
                    'title'  => $a->title,
                    'detail' => \Illuminate\Support\Str::limit($a->body, 140),
                    'url'    => route('announcements.index'),
                    'time'   => $a->created_at,
                ];
            }
        }

        // Role-specific pending actions.
        switch ($user->role) {
            case 'principal':
                if (Schema::hasTable('payslips')) {
                    foreach (Payslip::with('staff')->where('status', 'submitted')->latest()->get() as $p) {
                        $items[] = ['icon' => '💰', 'title' => 'Payslip awaiting approval',
                            'detail' => ($p->staff->name ?? 'Staff').' — '.$p->month, 'url' => route('payroll.review'), 'time' => $p->submitted_at ?? $p->updated_at];
                    }
                }
                break;
            case 'bursar':
                if (Schema::hasTable('payslips')) {
                    foreach (Payslip::with('staff')->where('status', 'flagged')->latest()->get() as $p) {
                        $items[] = ['icon' => '⚠️', 'title' => 'Payslip flagged for correction',
                            'detail' => ($p->staff->name ?? 'Staff').' — '.($p->flag_comment ?? ''), 'url' => route('payroll.index'), 'time' => $p->updated_at];
                    }
                }
                break;
            case 'exam_officer':
                foreach (ResultQuery::where('status', 'open')->latest()->get() as $q) {
                    $items[] = ['icon' => '❓', 'title' => 'Open result query',
                        'detail' => \Illuminate\Support\Str::limit($q->message ?? '', 120), 'url' => route('exams.queries'), 'time' => $q->created_at];
                }
                break;
            case 'mis':
                foreach (SupportTicket::where('status', '!=', 'resolved')->latest()->get() as $t) {
                    $items[] = ['icon' => '🛠️', 'title' => 'Open support ticket',
                        'detail' => \Illuminate\Support\Str::limit($t->subject ?? $t->message ?? '', 120), 'url' => route('support.index'), 'time' => $t->created_at];
                }
                break;
            case 'admission_officer':
                foreach (Applicant::where('status', 'pending')->latest()->get() as $ap) {
                    $items[] = ['icon' => '🧾', 'title' => 'Pending admission application',
                        'detail' => $ap->full_name ?? '', 'url' => route('admission.admin'), 'time' => $ap->created_at];
                }
                break;
            case 'lecturer':
                $subjectIds = $user->subjects()->pluck('subjects.id');
                if ($subjectIds->isNotEmpty()) {
                    foreach (Exam::whereIn('subject_id', $subjectIds)->where('status', 'draft')->get() as $e) {
                        $items[] = ['icon' => '✍️', 'title' => 'Exam to author', 'detail' => $e->title ?? '', 'url' => route('dashboard'), 'time' => $e->updated_at];
                    }
                    foreach (Exam::whereIn('subject_id', $subjectIds)->whereIn('status', ['released', 'grading'])->whereHas('submissions')->get() as $e) {
                        $items[] = ['icon' => '📝', 'title' => 'Exam to grade', 'detail' => $e->title ?? '', 'url' => route('dashboard'), 'time' => $e->updated_at];
                    }
                }
                break;
            // Student exam-taking removed — no student exam notifications.
        }

        // Newest first; undated items sink to the bottom.
        usort($items, fn ($a, $b) => ($b['time']?->timestamp ?? 0) <=> ($a['time']?->timestamp ?? 0));

        return $items;
    }
}
