<?php

namespace App\Support;

use App\Models\Applicant;
use App\Models\ChangeOfCourseRequest;
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
    /**
     * The nav bell. The badge counts only NEW notifications — feed items newer
     * than when the user last opened the notifications page — so opening the
     * page (which stamps notifications_last_read_at) clears the badge to zero.
     */
    public static function forUser(?User $user): array
    {
        // The platform super-admin oversees all colleges but is deliberately kept
        // out of every college's notification stream.
        if (!$user || $user->role === 'superadmin') {
            return ['count' => 0, 'url' => '#', 'label' => ''];
        }

        $lastRead = $user->notifications_last_read_at;
        $new = 0;
        foreach (self::feedFor($user) as $item) {
            $time = $item['time'] ?? null;
            if ($time instanceof \Illuminate\Support\Carbon || $time instanceof \DateTimeInterface) {
                if ($lastRead === null || $time > $lastRead) {
                    $new++;
                }
            } elseif ($lastRead === null) {
                // Undated items are "new" only until the page is first opened.
                $new++;
            }
        }

        return ['count' => $new, 'url' => route('notifications.index'), 'label' => 'new notifications'];
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
        if (!$user || $user->role === 'superadmin') {
            return [];
        }

        $items = [];

        // Announcements visible to everyone in their lane.
        if (Schema::hasTable('announcements')) {
            $student = $user->role === 'student' ? Student::where('email', $user->email)->first() : null;
            $classArm = $student->class_arm ?? null;
            $query = \App\Models\Announcement::query();
            if (method_exists(\App\Models\Announcement::class, 'scopeVisibleTo')) {
                $query->visibleTo((string) $user->role, $classArm);
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
            case 'academic_secretary':
                if (Schema::hasTable('change_of_course_requests')) {
                    foreach (ChangeOfCourseRequest::with(['student', 'requestedProgram'])
                        ->whereIn('status', ['secretary_review', 'new_hod_approved', 'new_hod_rejected', 'current_hod_approved', 'current_hod_rejected'])
                        ->latest('updated_at')->get() as $c) {
                        $items[] = ['icon' => '🔁', 'title' => 'Change-of-course needs your action',
                            'detail' => (optional($c->student)->full_name ?? 'Student').' — '.$c->statusLabel(),
                            'url' => route('change-of-course.review'), 'time' => $c->updated_at];
                    }
                }
                break;
            case 'hod':
            case 'assistant_hod':
                if (Schema::hasTable('change_of_course_requests') && $user->department_id) {
                    $deptId = $user->department_id;
                    $rows = ChangeOfCourseRequest::with(['student', 'requestedProgram'])
                        ->where(function ($q) use ($deptId) {
                            $q->where(fn ($w) => $w->where('status', 'new_hod_review')->whereHas('requestedProgram', fn ($p) => $p->where('department_id', $deptId)))
                              ->orWhere(fn ($w) => $w->where('status', 'current_hod_review')->whereHas('student', fn ($p) => $p->where('department_id', $deptId)));
                        })->latest('updated_at')->get();
                    foreach ($rows as $c) {
                        $items[] = ['icon' => '🔁', 'title' => 'Change-of-course awaiting your review',
                            'detail' => (optional($c->student)->full_name ?? 'Student').' → '.optional($c->requestedProgram)->name,
                            'url' => route('change-of-course.hod'), 'time' => $c->updated_at];
                    }
                }
                break;
            case 'registrar':
                if (Schema::hasTable('change_of_course_requests')) {
                    foreach (ChangeOfCourseRequest::with(['student', 'requestedProgram'])
                        ->where('status', 'registrar_review')->latest('updated_at')->get() as $c) {
                        $items[] = ['icon' => '🔁', 'title' => 'Change-of-course awaiting final approval',
                            'detail' => (optional($c->student)->full_name ?? 'Student').' → '.optional($c->requestedProgram)->name,
                            'url' => route('change-of-course.approvals'), 'time' => $c->updated_at];
                    }
                }
                break;
            case 'student':
                if (Schema::hasTable('change_of_course_requests')) {
                    $me = Student::where('email', $user->email)->first();
                    if ($me) {
                        foreach (ChangeOfCourseRequest::with('requestedProgram')
                            ->where('student_id', $me->id)
                            ->whereIn('status', ['approved', 'rejected'])
                            ->latest('decided_at')->get() as $c) {
                            $items[] = $c->isApproved()
                                ? ['icon' => '✅', 'title' => 'Change of course approved',
                                    'detail' => 'You may download your acceptance letter and pay the new registration fee.',
                                    'url' => route('change-of-course.index'), 'time' => $c->decided_at]
                                : ['icon' => '⛔', 'title' => 'Change of course rejected',
                                    'detail' => $c->rejection_reason ?? 'Your application was not approved.',
                                    'url' => route('change-of-course.index'), 'time' => $c->decided_at];
                        }
                    }
                }
                break;
        }

        // Newest first; undated items sink to the bottom.
        usort($items, fn ($a, $b) => ($b['time']?->timestamp ?? 0) <=> ($a['time']?->timestamp ?? 0));

        return $items;
    }
}
