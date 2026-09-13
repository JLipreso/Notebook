# 2026-09-13 — Phase-001 workstation onboarding, laptop (task 2026-09-13-005)

**Branch:** `Workstation-Laptop` · **Machine:** laptop (second dev machine; `Workstation-PC` is the desktop) · **Requested by:** Jason (Lead Developer) — boss directed a start at Phase-001 through Phase-012.

First execution of [Phase-001](../../documents/2026-09-13-005-Implementation-Plan/Phase-001.md) on this machine. No product code — environment bring-up and verification only, so **no PR** (per the phase file).

## What was done

- **Work branch created:** `Workstation-Laptop` off `origin/staging` (at `bd6ddb8`), pushed, upstream set to `origin/Workstation-Laptop`. Note: `git checkout -b <name> origin/staging` initially set the upstream to *staging*, which would make a bare `git push` target the integration branch — `push -u` corrected it. Worth doing in that order on the next machine.
- **Toolchain verified** (all at or above the Phase-001 table): Node v24.14.1 · pnpm 10.28.0 (matches the pinned `packageManager`) · PHP 8.2.30 via Herd · Composer 2.9.7 · Git 2.54.0. PHP extensions present: sqlite3, pdo_sqlite, mbstring, openssl, fileinfo, curl, zip, gd.
- **Frontend up:** `pnpm install` clean (1m02s) → `pnpm typecheck` green across all 8 workspace projects → `pnpm build:all` green (both apps ~8s each, Fraunces + Figtree `@fontsource` faces bundling into `dist/assets` as intended). `.env` copied from `.env.example` for `apps/student/browser` and `apps/student/mobile`, both `VITE_USE_MOCK=true`.
- **Backend up:** `composer install` clean → `.env` from template → `key:generate` → `touch database/database.sqlite` → `php artisan migrate` clean (4 migrations). `php artisan serve` + `GET /api/health` → 200.
- **Dev servers:** browser on :5171 → 200, mobile on :5174 → 200, both rendering the scaffold shell.
- **Tooling:** `node scripts/refresh-docs.mjs` runs clean (3 routes via artisan, 3 env sources). Output was byte-identical to what is committed, so it was reverted — the working tree was left clean.
- **Tracker:** Phase-001 flipped `pending` → `in_progress` on the boss-facing [M1 Build Tracker](https://claude.ai/code/artifact/74514cdd-95cb-44b5-8170-b7277b5fa991) (`phases/phase-001`, now version 2).

## Verified

Every Phase-001 acceptance item passes on this machine:

- [x] `pnpm typecheck` + `pnpm build:all` green from root
- [x] Browser :5171 and mobile :5174 both render the scaffold page
- [x] `php artisan migrate` clean; `/api/health` returns 200
- [x] `Workstation-Laptop` exists on the remote
- [x] `.env` files and `database/database.sqlite` confirmed gitignored via `git check-ignore -v`

## Notes / gotchas for next session

- **Phase-001 §5 is imprecise about `/api/health`.** It says the endpoint "returns the success envelope"; it actually returns `{ok, app, env, time}` — not the §3 `{success, message, data}` shape. The route comment in `backend/routes/api.php` shows this is deliberate (a platform probe distinct from Laravel's built-in `/up`), and `/api/user` directly below it *does* use the envelope. **The code is correct and the phase doc is loose — do not "fix" the endpoint.** Flagged to the Lead Developer; not ledger-worthy (cost no debugging time, caught on first read).
- **Android Studio + JDK 21 are already installed on this machine** — ahead of the Phase-011 requirement. `ANDROID_HOME` is currently unset; that is a Phase-011 concern, not a blocker now.
- **Open, not blocking yet — Firebase credentials.** Both app `.env` files have empty `VITE_FIREBASE_*` and `backend/.env` has no `FIREBASE_CREDENTIALS`. Mock mode covers Phases 001–003; this **hard-blocks Phase-004**. Requested from the Lead Developer early so the handoff lead time does not become the critical path. Service-account JSON goes outside the repo (e.g. `C:\credentials\notebook-firebase.json`).
- **Open chore — GitHub default branch is still `main`.** Carried over from session 005. Until it is switched, every PR from this machine must pass `--base staging` explicitly (CLAUDE.md §8: PRs never target `main`).
- Backgrounding `php artisan serve` from Git Bash works on this machine (the session-002 Herd/`Start-Process` quirk did not reproduce here — different invocation path).

## Next

Phase-002 (M1 database: migrations, models, seeders) on `Workstation-Laptop`, one PR to `staging`. Before starting, re-read [database-schema.md](../../documents/2026-09-13-004-M1-Foundation/plan/database-schema.md) §1–§5 + §9 — it is authoritative over the phase file. Two things in Phase-002 have downstream reach: the RW tables' `client_updated_at TIMESTAMP(3)` (Phase-009 depends on it existing from the start) and the `notebook_types.page_template` JSON shape (Phase-007's `PaperPage.vue` consumes it — coordinate the shape before finalizing the seeder).
