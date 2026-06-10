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
        'view_staff'           => ['registrar', 'proprietor', 'academic_secretary'],

        // Student records (admit/delete) — Registrar (absorbed the old admin role).
        'manage_students'      => ['registrar'],
        // Edit existing student info (names, next-of-kin, level correction, passport).
        'edit_students'        => ['registrar', 'ict'],
        'view_students'        => ['proprietor', 'registrar', 'bursar', 'ict', 'exam_officer', 'lecturer', 'hod', 'assistant_hod', 'student_affairs', 'office_secretary'],

        // Managed class/level registry.
        'manage_classes'       => ['registrar', 'ict'],

        // Departments & Programs — Registrar owns the academic structure.
        'manage_departments'   => ['registrar'],
        'manage_programs'      => ['registrar'],
        'view_departments'     => ['registrar', 'proprietor', 'academic_secretary', 'hod', 'assistant_hod', 'exam_officer'],

        // Timetable generation & approval — Registrar. Everyone else views.
        'manage_timetable'     => ['registrar'],

        // Payroll — Bursar runs it; Registrar reviews/approves only.
        'manage_payroll'       => ['bursar'],
        'review_payroll'       => ['registrar'],

        // Fees / payments — Bursar.
        'manage_fees'          => ['bursar'],
        'view_fees'            => ['proprietor', 'bursar', 'registrar'],

        // Admissions: applicants apply online; Registrar offers admission.
        'create_admissions'    => ['ict'],
        'manage_admissions'    => ['registrar'],

        // Academic term/session control — Registrar.
        'manage_term'          => ['registrar'],

        // Exam lifecycle — Exam Officer (+ ICT support during exams).
        'manage_exams'         => ['exam_officer', 'ict'],
        'view_exams'           => ['exam_officer', 'ict', 'registrar', 'proprietor', 'hod'],
        'enter_scores'         => ['lecturer', 'exam_officer'],
        'author_questions'     => ['lecturer'],
        'take_exams'           => ['student'],

        // Attendance — Lecturers take it for their classes.
        'take_attendance'      => ['lecturer', 'exam_officer'],
        'view_attendance'      => ['lecturer', 'exam_officer', 'registrar', 'proprietor', 'hod'],

        // Courses (formerly subjects) — staff view; bursar excluded.
        'view_subjects'        => ['lecturer', 'exam_officer', 'registrar', 'proprietor', 'ict', 'academic_secretary', 'hod', 'assistant_hod'],

        // Academic Secretary assigns courses to lecturers.
        'assign_courses'       => ['academic_secretary', 'registrar'],

        // HOD approves a student's final registration in their department.
        'approve_registration' => ['hod', 'assistant_hod'],

        // Library — Librarian runs it; ICT supports.
        'manage_library'       => ['librarian', 'ict'],

        // Staff clock in/out — all staff except the view-only proprietor.
        'clock_attendance'     => ['lecturer', 'bursar', 'exam_officer', 'ict', 'registrar', 'hod', 'assistant_hod', 'academic_secretary', 'student_affairs', 'librarian', 'office_secretary'],

        // Staff-attendance oversight report.
        'view_staff_attendance'=> ['registrar', 'proprietor'],

        // Academic structure — Registrar & ICT add/remove courses.
        'manage_subjects'      => ['registrar', 'ict'],

        // Technical support — ICT handles tickets; anyone can raise one.
        'handle_tickets'       => ['ict'],
        'reset_passwords'      => ['ict'],

        // Operations / system.
        'manage_settings'      => ['registrar', 'ict'],
        'manage_announcements' => ['registrar', 'ict', 'student_affairs'],
        'manage_inventory'     => ['registrar', 'ict', 'office_secretary'],
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
