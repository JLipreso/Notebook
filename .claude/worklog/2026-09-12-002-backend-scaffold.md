# 2026-09-12 — Backend scaffold (task 2026-09-12-002)

**Branch:** `Workstation-PC` · **Requested by:** Jason (Lead Developer) — scaffold the Laravel backend ahead of the client brief.

## What was done

- `composer create-project laravel/laravel backend` → **Laravel 12.69.2** (v13 requires PHP 8.3; this machine runs PHP 8.2.31 via Herd, which matches the locked stack anyway). `php artisan install:api` → **Sanctum 4.3.3**.
- Applied the §5 conventions, copied shape-for-shape from the Exploria backend reference:
  - `app/Http/Controllers/Api/ApiController.php` — frozen envelope (`success`/`error`/`paginated` + helpers).
  - `app/Http/Middleware/Cors.php` + `config/cors.php` — single origin list, prepended, framework `HandleCors` removed.
  - `app/Http/Middleware/SecurityHeaders.php` — Exploria H-14 baseline from day zero.
  - `bootstrap/app.php` — middleware wiring, `redirectGuestsTo(null)`, JSON rendering for `api/*`.
  - `AppServiceProvider::boot()` — `DB::prohibitDestructiveCommands(prod)` + `public`/`public-write`/`auth` limiters (IP-keyed per Exploria H-10).
  - `routes/api.php` — banner convention, `/health` probe, `auth:sanctum` `/user` smoke route.
  - Sanctum migration renamed `2026_09_12_055716_…` → `0001_01_01_000003_…` to fit the counter convention; `migrate:fresh` clean.
  - `HasApiTokens` added to `User`.
- `.env.example` — `APP_NAME=Notebook`, sqlite default with a note that the prod DB engine is an open decision, `SANCTUM_STATEFUL_DOMAINS=` blank, `CORS_ALLOWED_ORIGINS` documented. Local `.env` synced. Added `/database/*.sqlite` to `backend/.gitignore` (Laravel's default does not ignore it).
- `/refresh-docs` regenerated 002/003 references (first run with real content — 3 routes, 1 env source).
- Doc obligations: task folder `documents/2026-09-12-002-Backend-Scaffold/README.md`; CLAUDE.md banner + §1 status + §2 table updated; current-status.md updated.

## Verified

- `php artisan test` 2/2 pass; `route:list` clean.
- Live: `GET /api/health` 200 + security headers · `OPTIONS` preflight from `localhost:5173` → 204 + CORS headers · unauthenticated `GET /api/user` → **401 JSON** (no redirect).

## Notes / gotchas for next session

- `Start-Process php` (Herd shim) dies immediately when used to background `artisan serve` — use Git-Bash background (`php artisan serve &`-style) for smoke tests. Not ledger-worthy yet (cost minutes, workaround trivial); promote if it bites again.
- Deliberately NOT built: AuthController/login flow (Q-003), domain models (Q-001), money math (Q-002). `config/cors.php` has a placeholder `localhost:5173` — add real per-app entries as `apps/*` are scaffolded.
- Composer picked Laravel **12.69.2** because latest laravel/laravel targets PHP 8.3 — expected, stack is locked at PHP 8.2 (D-001).
