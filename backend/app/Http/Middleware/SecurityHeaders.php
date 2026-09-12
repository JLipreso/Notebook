<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security headers on every API response (2026-09-12-002, carried
 * over from Exploria's H-14 hardening — cheaper to ship from day zero than
 * to retrofit after an audit).
 *
 * Middleware rather than an `.htaccess` so it is testable and cannot be
 * lost when a vhost is recreated.
 *
 * No document CSP: this host serves JSON, not pages. `default-src 'none'`
 * plus a locked-down frame policy is the meaningful equivalent — nothing
 * should ever embed or execute an API response.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
            // An API response is never a document: refuse everything.
            'Content-Security-Policy' => "default-src 'none'; frame-ancestors 'none'; base-uri 'none'",
        ];

        // HSTS only over TLS — sending it on a plain-HTTP dev request would
        // pin localhost to https and make the dev server unreachable.
        if ($request->secure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $name => $value) {
            // Do not clobber anything a route deliberately set.
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }
}
