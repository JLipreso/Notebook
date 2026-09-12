# 2026-09-12-002 — Backend Scaffold

**Status: complete** (single-session task, 2026-09-12).

Installed Laravel 12 into [backend/](../../backend/) and applied the D-002 conventions from CLAUDE.md §5, using the Exploria backend (`D:\Software-Dev-Projects\Jazer\Monorepo-Exploria-Restart\backend`) as the working reference. No product code — this is the conventions skeleton the first real feature builds on.

## What shipped

| Piece | Where | Notes |
|---|---|---|
| Laravel 12.69.2, PHP 8.2 | `backend/` | `composer create-project`; sqlite dev DB (engine for prod is an open decision — see `.env.example` note) |
| Sanctum v4.3.3, stateless | `config/sanctum.php`, `User` model | `php artisan install:api`; `HasApiTokens` on `User`; `SANCTUM_STATEFUL_DOMAINS` deliberately blank |
| Response envelope | `app/Http/Controllers/Api/ApiController.php` | `success()` / `error()` / `paginated()` + helpers; envelope frozen to the shared TS contract (D-005) |
| CORS | `app/Http/Middleware/Cors.php` + `config/cors.php` | Single origin list, env-replaceable via `CORS_ALLOWED_ORIGINS`; framework `HandleCors` removed; prepended so OPTIONS gets a bare 204 first |
| Security headers | `app/Http/Middleware/SecurityHeaders.php` | Exploria H-14 baseline carried over from day zero |
| API-only JSON errors | `bootstrap/app.php` | `redirectGuestsTo(null)` + `shouldRenderJsonWhen` for `api/*` — 401 JSON, never a login redirect |
| Rate limiters | `AppServiceProvider::boot()` | `public` (60/min), `public-write` (5/min), `auth` (10/min), IP-keyed; `DB::prohibitDestructiveCommands()` in production |
| Routes | `routes/api.php` | Banner-comment convention, one `Route::` per line; `/api/health` probe + `auth:sanctum` `/api/user` smoke route |
| Migrations | `database/migrations/` | Sanctum migration renamed into the `0001_01_01_NNNNNN` fake-timestamp counter (`000003`) |

## Deliberately NOT done (blocked on open questions)

- No AuthController / login flow — auth design waits on roles/tenancy (Q-003).
- No domain models, no money math (Q-001/Q-002).
- `config/cors.php` lists only a placeholder `localhost:5173` — add one entry per app as `apps/*` land.

## Verification (2026-09-12)

- `php artisan route:list` — clean; `php artisan test` — 2/2 pass.
- Live smoke test: `GET /api/health` → 200 with security headers; `OPTIONS` preflight from `http://localhost:5173` → 204 with full CORS headers; unauthenticated `GET /api/user` → 401 JSON.
- `/refresh-docs` regenerated the endpoint + env-var references in the same commit.
