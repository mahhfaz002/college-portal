<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Email-OTP two-factor gate. A staff user who has authenticated but not yet
 * cleared their one-time code is held at the challenge page (escape hatch: the
 * two-factor routes themselves + logout), mirroring ForcePasswordChange.
 */
class EnsureTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->requiresTwoFactor() && ! $request->session()->get('2fa_verified', false)) {
            if ($request->routeIs('two-factor.challenge', 'two-factor.verify', 'two-factor.resend') || $request->is('logout')) {
                return $next($request);
            }

            return redirect()->route('two-factor.challenge');
        }

        return $next($request);
    }
}
