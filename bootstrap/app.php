<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Behind Laravel Cloud's load balancer: trust forwarded headers so
        // request()->getHost() / scheme reflect the real client-facing domain.
        // This is essential for host-based tenant resolution (the app is only
        // reachable through the Cloud edge, so trusting it is safe).
        $middleware->trustProxies(at: '*');

        // Registering both your existing Role middleware and the new Password Change middleware
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'force.password.change' => \App\Http\Middleware\ForcePasswordChange::class,
            'readonly' => \App\Http\Middleware\EnforceReadOnly::class,
            'platform.fee' => \App\Http\Middleware\EnsurePlatformFeePaid::class,
        ]);

        // Defensive response headers on every web request.
        // SetCollegeContext binds the tenant (college) so the CollegeScope can
        // isolate every query to the logged-in user's college.
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\SetCollegeContext::class,
        ]);

        // Paystack posts webhooks server-to-server (no CSRF token).
        $middleware->validateCsrfTokens(except: [
            'paystack/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Report unhandled exceptions to Sentry (no-op until SENTRY_LARAVEL_DSN is set).
        \Sentry\Laravel\Integration::handles($exceptions);
    })->create();
