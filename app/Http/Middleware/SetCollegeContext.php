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
        if (auth()->check() && auth()->user()->college_id) {
            app()->instance('current_college_id', (int) auth()->user()->college_id);
        }

        return $next($request);
    }
}
