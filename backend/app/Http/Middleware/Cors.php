<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Project CORS middleware (2026-09-12-002, pattern inherited from Exploria D-025).
 *
 * THE single source of CORS truth. Origins come from config/cors.php
 * (env-extendable via CORS_ALLOWED_ORIGINS) — never introduce a second
 * origin list anywhere else. The framework's HandleCors is removed in
 * bootstrap/app.php so headers are never emitted twice.
 *
 * Prepended to the global stack so OPTIONS preflights are answered with a
 * bare 204 before auth or anything else runs.
 */
class Cors
{
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->headers->get('Origin');
        $allowed = $origin !== null
            && in_array($origin, config('cors.allowed_origins', []), true);

        $response = $request->isMethod('OPTIONS')
            ? response('', 204)
            : $next($request);

        if ($allowed) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Vary', 'Origin');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept, X-Requested-With');
            $response->headers->set('Access-Control-Max-Age', '86400');
        }

        return $response;
    }
}
