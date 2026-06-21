<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Binds the current college id into the container for the lifetime of the
 * request, so the CollegeScope and current_college() helper resolve the right
 * tenant. Derived from the authenticated user; guests resolve to null (no-op).
 */
class SetCollegeContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            // Authenticated users are scoped to THEIR college. A super-admin has
            // no college (college_id null) and is intentionally left unscoped so
            // the platform panel can see every college.
            if (auth()->user()->college_id) {
                app()->instance('current_college_id', (int) auth()->user()->college_id);
            }
        } else {
            // LOCAL DEV ONLY: localhost matches no college domain, so allow
            // explicitly choosing which tenant to preview/test via
            // ?college=<id|acronym>. The choice is sticky (stored in the session)
            // so subsequent form posts (e.g. self-registration lookup) keep it.
            // This branch can never run in production (isLocal guard).
            if (app()->isLocal()) {
                if ($request->filled('college')) {
                    $key = trim((string) $request->query('college'));
                    $picked = \App\Models\College::where('is_active', true)
                        ->where(function ($q) use ($key) {
                            $q->whereRaw('LOWER(acronym) = ?', [strtolower($key)]);
                            if (ctype_digit($key)) {
                                $q->orWhere('id', (int) $key);
                            }
                        })->first();
                    if ($picked) {
                        session(['dev_college_id' => $picked->id]);
                    }
                }
                if ($devId = session('dev_college_id')) {
                    app()->instance('current_college_id', (int) $devId);
                    return $next($request);
                }
            }

            // Guests: resolve the tenant by the request domain so the public
            // landing page and frontend render that college's branding/content.
            $host = $request->getHost();

            // 1) Exact custom-domain match (e.g. albazchst.edu.ng).
            $college = \App\Models\College::where('domain', $host)->where('is_active', true)->first();

            // 2) Subdomain of the platform domain (e.g. albaz.myplatform.com):
            //    one wildcard DNS serves every college as <acronym>.platform.
            $platform = config('app.platform_domain');
            if (!$college && $platform && str_ends_with($host, '.'.$platform)) {
                $label = substr($host, 0, -strlen('.'.$platform));   // first label
                $college = \App\Models\College::whereRaw('LOWER(acronym) = ?', [strtolower($label)])
                    ->where('is_active', true)->first();
            }

            if ($college) {
                app()->instance('current_college_id', (int) $college->id);
            }
        }

        return $next($request);
    }
}
