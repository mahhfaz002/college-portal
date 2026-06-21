<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every account this portal creates is trusted (admin-vouched or payment-
 * confirmed), so new users must be verified on creation — never stranded on
 * /verify-email. Regression guard for the dropped-$fillable bug.
 */
class AccountVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_are_verified_on_creation(): void
    {
        $user = User::create([
            'name'     => 'Aisha Bello',
            'email'    => 'aisha@example.com',
            'password' => 'secret123',
            'role'     => 'applicant',
        ]);

        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_verified_only_routes_are_reachable_by_a_freshly_created_user(): void
    {
        $user = User::create([
            'name'     => 'Musa Bello',
            'email'    => 'musa@example.com',
            'password' => 'secret123',
            'role'     => 'applicant',
        ]);

        // The 'verified' middleware must NOT bounce a freshly-created account to
        // the email-verification prompt.
        $this->actingAs($user)->get('/dashboard')->assertOk();
    }
}
