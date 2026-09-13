<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
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

// ================================================================
// ADDRESS (2026-09-13-005 Phase 005)
// ================================================================

// Public PSGC reference data, cached a day server-side. {code} is a PSGC
// natural key (CHAR(10)), never a UUID — guarded as 10 digits.
Route::get('/address/regions', [AddressController::class, 'regions'])->middleware('throttle:public');
Route::get('/address/regions/{code}/provinces', [AddressController::class, 'provinces'])->where('code', '[0-9]{10}')->middleware('throttle:public');
Route::get('/address/regions/{code}/cities', [AddressController::class, 'citiesByRegion'])->where('code', '[0-9]{10}')->middleware('throttle:public');
Route::get('/address/provinces/{code}/cities', [AddressController::class, 'citiesByProvince'])->where('code', '[0-9]{10}')->middleware('throttle:public');
Route::get('/address/cities/{code}/barangays', [AddressController::class, 'barangays'])->where('code', '[0-9]{10}')->middleware('throttle:public');
Route::get('/address/barangays/{code}/chain', [AddressController::class, 'chain'])->where('code', '[0-9]{10}')->middleware('throttle:public');

// ================================================================
// PROFILE (2026-09-13-005 Phase 005)
// ================================================================

Route::get('/profile', [ProfileController::class, 'show'])->middleware('auth:sanctum');
Route::put('/profile', [ProfileController::class, 'update'])->middleware('auth:sanctum');
