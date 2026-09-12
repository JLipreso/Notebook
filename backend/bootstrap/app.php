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
    ->withMiddleware(function (Middleware $middleware): void {
        // CORS (2026-09-12-002): one custom middleware holds the single origin
        // list and is PREPENDED so OPTIONS preflights are answered before
        // anything else. The framework's HandleCors is removed so CORS
        // headers are never emitted twice.
        $middleware->prepend(\App\Http\Middleware\Cors::class);
        $middleware->remove(\Illuminate\Http\Middleware\HandleCors::class);

        // Baseline security headers on every response. Appended, so it runs
        // after CORS and never interferes with the preflight answer.
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // API-only app: unauthenticated requests must get 401 JSON, never a
        // redirect to a (nonexistent) 'login' web route — even without an
        // Accept: application/json header.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API-only app: always render JSON for /api/* (a browser client with
        // no Accept header must get 401 JSON, never a redirect to a
        // nonexistent 'login' web route).
        $exceptions->shouldRenderJsonWhen(
            fn ($request, $e) => $request->is('api/*') || $request->expectsJson()
        );
    })->create();
