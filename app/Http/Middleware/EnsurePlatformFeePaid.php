<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Self-onboarded students may not use the portal until the one-off platform
 * registration fee is paid. Sends them to settle it (escape hatch: the pay
 * route itself + logout).
 */
class EnsurePlatformFeePaid
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->role === 'student' && !$user->platform_fee_paid) {
            if ($request->routeIs('platform.fee.pay') || $request->is('logout')) {
                return $next($request);
            }
            return redirect()->route('platform.fee.pay');
        }

        return $next($request);
    }
}
