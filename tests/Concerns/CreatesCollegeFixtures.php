<?php

namespace Tests\Concerns;

use App\Models\College;
use App\Models\Student;
use App\Models\User;

/**
 * Test fixtures for the multi-tenant college architecture.
 *
 * The production DatabaseSeeder seeds only the platform super-admin, so feature
 * tests must build their own tenant: a College, the staff/student logins they
 * exercise, and any student records. bootCollege() also binds the college as the
 * active tenant context so every model created afterwards (and every request
 * made via actingAs) resolves to it through the CollegeScope.
 */
trait CreatesCollegeFixtures
{
    protected College $college;

    /**
     * Create the tenant college and bind it as the active context.
     *
     * Acronym is 'MAHHFAZ' to match the seeded `school_acronym` setting, and the
     * domain is left null so generated staff emails fall back to the seeded
     * `staff_email_domain` setting (what the staff-management tests assert on).
     */
    protected function bootCollege(array $attrs = []): College
    {
        $this->college = College::create(array_merge([
            'name'      => 'MAHHFAZ College of Health Sciences and Technology',
            'acronym'   => 'MAHHFAZ',
            'email'     => 'info@mahhfaz.edu.ng',
            'is_active' => true,
        ], $attrs));

        app()->instance('current_college_id', (int) $this->college->id);

        return $this->college;
    }

    /**
     * A user of the given college role, attached to the booted college.
     * Pass a role key from App\Support\Permissions (e.g. 'lecturer', 'bursar').
     */
    protected function userWithRole(string $role, array $attrs = []): User
    {
        return User::factory()->role($role)->create($attrs);
    }

    /**
     * A student record in the booted college. Pass ['email' => ...] to link it
     * to a student login for ownership checks.
     */
    protected function studentRecord(array $attrs = []): Student
    {
        $seq = Student::withoutGlobalScopes()->count() + 1;

        return Student::create(array_merge([
            'full_name'        => "Test Student {$seq}",
            'admission_number' => "TST/{$seq}",
            'class_arm'        => 'UG1',
            'parent_phone'     => '08000000000',
            'fees_balance'     => 0,
            'email'            => "student{$seq}@example.test",
        ], $attrs));
    }
}
