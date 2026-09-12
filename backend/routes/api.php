<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Notebook API routes
|--------------------------------------------------------------------------
| Middleware policy:
|   - Public endpoints live under Route::prefix('public') with throttle:public;
|     public writes additionally get throttle:public-write.
|   - Everything else requires auth:sanctum (stateless bearer tokens, §5).
| Conventions (CLAUDE.md §5):
|   - Every section carries a banner comment with its originating task ID.
|   - Literal routes are registered BEFORE apiResource wildcards.
|   - Numeric route ids are guarded with ->whereNumber().
|   - One Route:: call per line — the endpoint reference generator parses
|     this file (/refresh-docs).
*/

// ================================================================
// PLATFORM (2026-09-12-002)
// ================================================================

// Public health probe (separate from Laravel's built-in /up).
Route::get('/health', fn () => response()->json([
    'ok' => true,
    'app' => config('app.name'),
    'env' => app()->environment(),
    'time' => now()->toIso8601String(),
]));

// Sanctum smoke test — the caller behind a bearer token. Replace with a real
// AuthController when the auth design is decided (Q-003).
Route::get('/user', fn (Request $request) => response()->json([
    'success' => true,
    'message' => 'Success',
    'data' => $request->user(),
]))->middleware('auth:sanctum');
