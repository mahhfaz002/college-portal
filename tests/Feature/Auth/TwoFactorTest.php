<?php

namespace Tests\Feature\Auth;

use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bootCollege();
        config(['auth.two_factor_enabled' => true]); // suite runs with it off
    }

    public function test_staff_login_requires_email_otp_then_reaches_dashboard(): void
    {
        Notification::fake();
        $staff = $this->userWithRole('mis');

        // Login emails a code and parks the user at the challenge.
        $this->post('/login', ['email' => $staff->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.challenge'));

        $code = null;
        Notification::assertSentTo($staff, TwoFactorCodeNotification::class, function ($n) use (&$code) {
            $code = $n->code;
            return true;
        });

        // Gated until verified; the challenge page renders.
        $this->get('/dashboard')->assertRedirect(route('two-factor.challenge'));
        $this->get('/two-factor')->assertOk();

        // Wrong code is rejected.
        $this->post('/two-factor', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->get('/dashboard')->assertRedirect(route('two-factor.challenge'));

        // Correct code clears the gate.
        $this->post('/two-factor', ['code' => $code])->assertRedirect(route('dashboard'));
        $this->get('/dashboard')->assertOk();
    }

    public function test_students_are_not_challenged(): void
    {
        Notification::fake();
        $this->studentRecord(['email' => 'twofa.student@example.test']);
        $student = $this->userWithRole('student', ['email' => 'twofa.student@example.test']);

        $this->post('/login', ['email' => $student->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        Notification::assertNothingSent();
    }
}
