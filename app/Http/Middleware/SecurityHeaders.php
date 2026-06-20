<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds defensive HTTP response headers to every web response.
 * (CSP is intentionally omitted to avoid breaking inline Alpine/Tailwind;
 * can be added in report-only mode later.)
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Frame-Options'        => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy'        => 'strict-origin-when-cross-origin',
            'Permissions-Policy'     => 'camera=(), microphone=(), geolocation=()',
            'X-XSS-Protection'       => '0',
        ];

        // HSTS only over HTTPS (production).
        if ($request->secure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        // Content-Security-Policy in REPORT-ONLY mode: it never blocks (so it
        // can't break the live UI), but documents the intended policy and flags
        // violations in the browser console. Allow-lists the CDNs the frontend
        // currently uses (Tailwind/Alpine/Font Awesome/Google Fonts). Flip the
        // header name to 'Content-Security-Policy' to enforce once assets are
        // self-hosted (recommended — see supply-chain note in the audit).
        $headers['Content-Security-Policy-Report-Only'] = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com",
            "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: blob: https:",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "frame-ancestors 'self'",
            "form-action 'self'",
        ]);

        foreach ($headers as $key => $value) {
            if (!$response->headers->has($key)) {
                $response->headers->set($key, $value);
            }
        }

        return $response;
    }
}
