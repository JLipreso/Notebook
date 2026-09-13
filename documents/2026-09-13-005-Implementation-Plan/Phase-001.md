# Phase 001 — Workstation onboarding & environment

**Goal:** your machine builds and runs everything that exists today, and you have your own work branch. No product code is written in this phase.

## 1. Prerequisites (install before anything)

| Tool | Version | Check |
|---|---|---|
| Node.js | ≥ 18 (repo developed on 22) | `node --version` |
| pnpm | ≥ 8 (repo developed on 10) | `pnpm --version` (`corepack enable` gives you the pinned one) |
| PHP | 8.2+ with `sqlite3`, `mbstring`, `openssl` extensions | `php -v`, `php -m` |
| Composer | 2.x | `composer --version` |
| Git | any recent | `git --version` |
| Android Studio | latest (only needed from Phase-011) | — |

## 2. Clone and branch (D-003 — three-tier git flow)

```bash
git clone https://github.com/JLipreso/Notebook.git
cd Notebook
git checkout staging
git checkout -b Workstation-<YourMachineName>   # your permanent work branch, named for the machine
git push -u origin Workstation-<YourMachineName>
```

Day-to-day you commit on YOUR branch. PRs go `Workstation-<You>` → `staging` (**always** `gh pr create --base staging`). Only the Lead Developer promotes `staging` → `main`.

## 3. Read, in this order (≈30 minutes, saves days)

1. [CLAUDE.md](../../CLAUDE.md) — the whole file; §0's routing table is how you find everything else.
2. [concept-final.md](../2026-09-12-001-Project-Details/concept-final.md) — what you're building.
3. [database-schema.md](../2026-09-13-004-M1-Foundation/plan/database-schema.md) §1–§5 + [project-structure.md](../2026-09-13-004-M1-Foundation/plan/project-structure.md) — the two locked plans this whole implementation follows.
4. This plan's [README](README.md) — the ground rules.

## 4. Frontend workspace up

```bash
pnpm install                 # ALWAYS from the repo root
pnpm typecheck               # must be green before you've touched anything
pnpm build:all               # must be green
cp apps/student/browser/.env.example apps/student/browser/.env
cp apps/student/mobile/.env.example apps/student/mobile/.env
pnpm dev:student:browser     # http://localhost:5171 — scaffold landing page
pnpm dev:student:mobile      # http://localhost:5174 — same, portrait shell
```

Both `.env` files ship with `VITE_USE_MOCK=true` — the frontend runs without the backend until Phase-004 flips your local setup to live.

## 5. Backend up

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite      # dev DB is sqlite (D-019); prod is MySQL 8 — never configure MySQL locally unless testing an engine-specific issue
php artisan migrate
php artisan serve                   # http://127.0.0.1:8000
```

Verify: `GET http://127.0.0.1:8000/api/health` returns the success envelope.

## 6. Credentials you need from the Lead Developer (never committed, never in chat logs)

- **Firebase web config values** for the two frontend `.env` files (`VITE_FIREBASE_*`) — used from Phase-004.
- **Firebase service-account JSON** — store it OUTSIDE the repo (e.g. `C:\credentials\notebook-firebase.json`) and point `FIREBASE_CREDENTIALS` in `backend/.env` at that absolute path. Used from Phase-004.

## Acceptance checklist

- [ ] `pnpm typecheck` and `pnpm build:all` green from root
- [ ] Browser app on :5171 and mobile app on :5174 both render the scaffold page
- [ ] `php artisan migrate` clean; `/api/health` returns the envelope
- [ ] Your `Workstation-<Name>` branch exists on the remote
- [ ] You can state, without looking: where PRs go, where decisions live, what the datasource switch is

**No PR for this phase** (nothing changed). Write a short `.claude/worklog/` entry noting your environment quirks — the next machine benefits.
