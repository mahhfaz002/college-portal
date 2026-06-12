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
