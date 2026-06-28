<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * The proprietor is an oversight role: they may VIEW all data but may not
 * create, edit, delete, or pay anything. This middleware is the global
 * safety net — regardless of how individual routes are grouped, any
 * state-changing request (POST/PUT/PATCH/DELETE) from a proprietor is
 * blocked, EXCEPT the handful of self-service account routes below
 * (logging out, changing their own password/profile).
 */
class EnforceReadOnly
{
    /**
     * Routes a proprietor is still allowed to write to — managing their
     * own account, not school data.
     */
    private const SELF_SERVICE = [
        'logout',
        'profile.update',
        'profile.destroy',
        'password.update',
        'password.change.update',
        'password.confirm',
        'verification.send',

        // The Provost manages their OWN e-signature (used on transcripts /
        // statements of result). That's a personal-account action, not a data
        // edit, so it must be exempt from the read-only rule.
        'signature.update',
        'signature.destroy',

        // Governance actions explicitly delegated to the Provost / Proprietor as
        // part of the case-escalation and payroll-approval workflows. These are
        // oversight decisions (resolve/approve/query), not data edits.
        'payroll.provost.forward',
        'payroll.provost.query',
        'payroll.provost.relay',
        'payroll.proprietor.approve',
        'payroll.proprietor.query',
        'cases.registrar.resolve',
        'cases.registrar.forward',
        'cases.provost.resolve',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->isReadOnly() && !$request->isMethodSafe()) {
            $routeName = $request->route()?->getName();

            if (!in_array($routeName, self::SELF_SERVICE, true)) {
                abort(403, 'Proprietor access is view-only. You can oversee all records but cannot change them.');
            }
        }

        return $next($request);
    }
}
