<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    /** Show the code-entry challenge. */
    public function show(Request $request)
    {
        $user = $request->user();

        // Nothing to do if 2FA isn't required or it's already cleared.
        if (! $user->requiresTwoFactor() || $request->session()->get('2fa_verified', false)) {
            return redirect()->intended(route('dashboard'));
        }

        return view('auth.two-factor-challenge', [
            'email' => $this->maskEmail($user->email),
        ]);
    }

    /** Verify the submitted code. */
    public function verify(Request $request)
    {
        $request->validate(['code' => ['required', 'string']]);
        $user = $request->user();

        if (! $user->verifyTwoFactorCode(trim($request->input('code')))) {
            throw ValidationException::withMessages([
                'code' => 'That code is invalid or has expired. Request a new one.',
            ]);
        }

        $request->session()->put('2fa_verified', true);

        return redirect()->intended(route('dashboard'))->with('success', 'Login verified.');
    }

    /** Email a fresh code (route is rate-limited). */
    public function resend(Request $request)
    {
        $request->user()->sendTwoFactorCode();

        return back()->with('success', 'A new verification code has been sent to your email.');
    }

    private function maskEmail(?string $email): string
    {
        if (! $email || ! str_contains($email, '@')) {
            return 'your email';
        }
        [$name, $domain] = explode('@', $email, 2);
        $shown = strlen($name) <= 2 ? $name[0] ?? '' : substr($name, 0, 2);

        return $shown.str_repeat('*', max(1, strlen($name) - strlen($shown))).'@'.$domain;
    }
}
