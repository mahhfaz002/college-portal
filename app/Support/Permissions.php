<?php

namespace App\Support;

/**
 * Single source of truth for "which role can do what".
 *
 * Routes consume this via Permissions::roles('capability') to build the
 * role: middleware string; Blade templates consume it via the @can-style
 * helper user()->canManage('capability') to show/hide action controls.
 *
 * The proprietor is deliberately absent from every *write* capability:
 * they oversee (read) everything but change nothing. That rule is also
 * enforced globally by the EnforceReadOnly middleware as a safety net.
 */
class Permissions
{
    public const MATRIX = [
        // Staff management — Registrar registers/manages all staff.
        'manage_staff'         => ['registrar'],
        'view_staff'           => ['registrar', 'proprietor', 'provost', 'academic_secretary', 'mis'],

        // Student records (create/edit/delete) — MIS. Registrar is READ-ONLY now.
        'manage_students'      => ['mis'],
        'edit_students'        => ['mis'],
        'view_students'        => ['proprietor', 'provost', 'registrar', 'mis', 'exam_officer', 'lecturer', 'hod', 'assistant_hod', 'student_affairs', 'office_secretary', 'admission_officer'],

        // Managed class/level registry — MIS.
        'manage_classes'       => ['mis'],

        // Departments & courses of study (programs) — MIS owns the structure.
        'manage_departments'   => ['mis'],
        'manage_programs'      => ['mis'],
        'view_departments'     => ['registrar', 'proprietor', 'provost', 'academic_secretary', 'hod', 'assistant_hod', 'exam_officer', 'mis', 'admission_officer'],

        // Timetable generation & approval — Academic Secretary ONLY.
        // Everyone else (incl. MIS, lecturers, students) sees the published one.
        'manage_timetable'     => ['academic_secretary'],

        // Payroll — Bursar runs it; MIS reviews/approves.
        'manage_payroll'       => ['bursar'],
        'review_payroll'       => ['mis'],

        // Fees / payments — Bursar. (Proprietor no longer sees payment orders.)
        'manage_fees'          => ['bursar'],
        'view_fees'            => ['provost', 'bursar'],

        // Admissions: applicants apply online; Registrar & Admission Officer
        // share the approval queue. Applications list is viewable by both.
        'manage_admissions'    => ['registrar', 'admission_officer'],
        'view_applications'    => ['registrar', 'admission_officer', 'proprietor', 'provost'],

        // Academic term/session control — MIS.
        'manage_term'          => ['mis'],

        // Exam lifecycle — Exam Officer (+ MIS support during exams).
        'manage_exams'         => ['exam_officer', 'mis'],
        'view_exams'           => ['exam_officer', 'mis', 'provost', 'hod'],
        'enter_scores'         => ['lecturer', 'exam_officer'],
        'author_questions'     => ['lecturer'],
        'take_exams'           => ['student'],

        // Attendance — Lecturers take it for their classes.
        'take_attendance'      => ['lecturer', 'exam_officer'],
        'view_attendance'      => ['lecturer', 'exam_officer', 'provost', 'hod'],

        // Courses (formerly subjects) — academic staff view. (Proprietor sees
        // courses inside the Departments browse instead of a separate menu.)
        'view_subjects'        => ['lecturer', 'exam_officer', 'provost', 'mis', 'academic_secretary', 'hod', 'assistant_hod'],

        // Academic Secretary creates courses and assigns them to lecturers.
        'manage_subjects'      => ['academic_secretary'],
        'assign_courses'       => ['academic_secretary'],

        // HOD approves a student's final registration in their department.
        'approve_registration' => ['hod', 'assistant_hod'],

        // Student affairs cases.
        'manage_affairs'       => ['student_affairs'],

        // Library — Librarian runs it; MIS supports.
        'manage_library'       => ['librarian', 'mis'],

        // Office correspondence register.
        'manage_correspondence'=> ['office_secretary'],

        // Staff clock in/out — all staff except the view-only proprietor.
        'clock_attendance'     => ['lecturer', 'bursar', 'exam_officer', 'mis', 'registrar', 'admission_officer', 'hod', 'assistant_hod', 'academic_secretary', 'student_affairs', 'librarian', 'office_secretary'],

        // Staff-attendance oversight report.
        'view_staff_attendance'=> ['registrar', 'proprietor', 'provost', 'mis'],

        // Technical support — MIS handles tickets; anyone can raise one.
        'handle_tickets'       => ['mis'],
        'reset_passwords'      => ['mis'],

        // Operations / system.
        'manage_settings'      => ['mis'],
        'manage_announcements' => ['mis', 'student_affairs', 'registrar'],
        'manage_inventory'     => ['mis', 'office_secretary'],
    ];

    /**
     * Roles allowed for a capability. Unknown capability => empty (deny all).
     */
    public static function roles(string $capability): array
    {
        return self::MATRIX[$capability] ?? [];
    }

    /**
     * Comma-joined role list for use in the route role: middleware, e.g.
     * Route::middleware('role:'.Permissions::middleware('manage_fees')).
     */
    public static function middleware(string $capability): string
    {
        return implode(',', self::roles($capability));
    }

    public static function roleCan(?string $role, string $capability): bool
    {
        return $role !== null && in_array($role, self::roles($capability), true);
    }
}
