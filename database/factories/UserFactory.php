<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            // Sensible defaults so a factory user clears the post-login gates
            // (verified, force-password-change, platform-fee) out of the box.
            'must_change_password' => false,
            'status' => 'active',
            'platform_fee_paid' => true,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Give the user a specific college role. This is the single generic state;
     * the named helpers below all delegate here so test intent reads clearly,
     * e.g. User::factory()->lecturer()->create().
     */
    public function role(string $role): static
    {
        return $this->state(fn (array $attributes) => ['role' => $role]);
    }

    // --- Named role states (current college roles; see App\Support\Permissions) ---

    public function superadmin(): static { return $this->role('superadmin'); }
    public function proprietor(): static { return $this->role('proprietor'); }
    public function provost(): static { return $this->role('provost'); }
    public function registrar(): static { return $this->role('registrar'); }
    public function bursar(): static { return $this->role('bursar'); }
    public function mis(): static { return $this->role('mis'); }
    public function academicSecretary(): static { return $this->role('academic_secretary'); }
    public function examOfficer(): static { return $this->role('exam_officer'); }
    public function lecturer(): static { return $this->role('lecturer'); }
    public function hod(): static { return $this->role('hod'); }
    public function assistantHod(): static { return $this->role('assistant_hod'); }
    public function studentAffairs(): static { return $this->role('student_affairs'); }
    public function officeSecretary(): static { return $this->role('office_secretary'); }
    public function admissionOfficer(): static { return $this->role('admission_officer'); }
    public function librarian(): static { return $this->role('librarian'); }

    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'student',
            'platform_fee_paid' => true,
        ]);
    }
}
