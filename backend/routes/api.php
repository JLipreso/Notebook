<?php

use App\Http\Controllers\Api\AuthController;
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

// ================================================================
// AUTH (2026-09-13-005 Phase 004)
// ================================================================

// Public: exchange a verified Firebase ID token for a Sanctum bearer (D-015).
Route::post('/auth/firebase', [AuthController::class, 'firebase'])->middleware('throttle:auth');

// The caller behind the bearer.
Route::get('/user', [AuthController::class, 'me'])->middleware('auth:sanctum');
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/auth/device', [AuthController::class, 'device'])->middleware('auth:sanctum');
