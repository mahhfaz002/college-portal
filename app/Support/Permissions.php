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
        // Staff / teacher management — Principal only.
        'manage_staff'         => ['principal'],
        'view_staff'           => ['principal', 'proprietor'],

        // Student records (admit/edit/delete) — Admin/Registrar.
        'manage_students'      => ['admin'],
        'view_students'        => ['proprietor', 'principal', 'admin', 'accountant', 'ict', 'exam_officer', 'teacher'],

        // Fees / payments — Bursar (accountant).
        'manage_fees'          => ['accountant'],
        'view_fees'            => ['proprietor', 'accountant', 'principal'],

        // Admissions intake — Admin/Registrar (+ ICT support).
        'manage_admissions'    => ['admin', 'ict'],

        // Exam lifecycle — Exam Officer (+ ICT support during exams).
        'manage_exams'         => ['exam_officer', 'ict'],
        'view_exams'           => ['exam_officer', 'ict', 'principal', 'proprietor'],
        'enter_scores'         => ['teacher', 'exam_officer'],
        'author_questions'     => ['teacher'],
        'take_exams'           => ['student'],

        // Attendance taking — class teachers.
        'take_attendance'      => ['teacher', 'exam_officer'],

        // Staff clock in/out — all staff except the view-only proprietor.
        'clock_attendance'     => ['teacher', 'accountant', 'exam_officer', 'ict', 'admin', 'principal'],

        // Teacher-attendance oversight report.
        'view_staff_attendance'=> ['principal', 'proprietor'],

        // Academic structure.
        'manage_subjects'      => ['principal', 'admin', 'exam_officer'],

        // Technical support — ICT handles tickets; anyone can raise one.
        'handle_tickets'       => ['ict'],
        'reset_passwords'      => ['ict'],

        // Operations / system.
        'manage_settings'      => ['principal', 'admin', 'ict'],
        'manage_announcements' => ['principal', 'admin', 'ict'],
        'manage_inventory'     => ['admin', 'ict'],
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
