# Notebook — Project Guideline (CLAUDE.md)

Project context for Claude Code and any developer joining the repo.
This file is the **entry point for every AI conversation and every new developer** — read it first.
Personal working preferences stay in each dev's local `~/.claude/` memory; everything in this file applies to everyone.

Shared work logs and locked decisions live in [.claude/](.claude/README.md) — read `.claude/memory/current-status.md` at the start of a session and append to `.claude/worklog/` at the end of one.

**Discoverability:** in a Claude Code session, say **"help"** to get the repo knowledge map and skill catalog (`/help`, `/whats-live`, `/diagnose-deploy`, `/refresh-docs`).

> **This repo is at day zero.** No application code exists yet — `apps/` and `backend/` are empty. Rows in the routing table below point only at things that actually exist; sections marked **TBD** are honest gaps, not oversights. Fill them in as the work lands, and delete this banner when §1 describes a real product.

---

## 0. 🛑 CONTEXT-FIRST RULE

**When a developer mentions ANY of these topics — bug report, how-does-it-work question, or extension request — read the matching doc BEFORE answering.** Don't answer subsystem questions from memory.

**Enforcement convention:** a new subsystem doc without a row in this table *doesn't exist*. When you write one, add the row in the same commit.

| Topic mentioned… | Read FIRST |
|---|---|
| **"what's the status", "what's next", "where were we"** | [.claude/memory/current-status.md](.claude/memory/current-status.md) — the live picture; read at the start of every session |
| **"why was X decided", "can we change the stack/branching"** | [.claude/memory/decisions.md](.claude/memory/decisions.md) — locked (D-001…), do not re-litigate |
| **"what did we do last time", "we hit this before"** | [.claude/worklog/](.claude/worklog/) — one file per session, newest wins |
| **weird bug, "it worked before", encoding/date/build flakiness** | [document/0000-00-00-000-Memory/001-Learnings.md](document/0000-00-00-000-Memory/001-Learnings.md) — check it BEFORE debugging; append to it after |
| **"what endpoint/URL/route", API surface** | [document/0000-00-00-000-Memory/002-Endpoints-Reference.md](document/0000-00-00-000-Memory/002-Endpoints-Reference.md) (auto-generated — run `/refresh-docs`, never hand-edit) |
| **env var, secret, "where do I set X"** | [document/0000-00-00-000-Memory/003-Env-Vars-Reference.md](document/0000-00-00-000-Memory/003-Env-Vars-Reference.md) (auto-generated — run `/refresh-docs`, never hand-edit) |
| **what the product is, business rules, who the client is** | [document/2026-09-12-001-Project-Details/](document/2026-09-12-001-Project-Details/) — the brief's home, **currently an empty stub**. While it is empty, **ask the Lead Developer instead of inventing an answer**; when it fills in, replace §1's TBD with a one-paragraph identity plus a pointer here |
| **backend conventions, controllers, routes, envelope, auth** | §5 below (Laravel 12) + the Rosterlink-EMR reference project named there. Do NOT improvise a different shape |
| **frontend conventions, apps, shared packages, aliases** | §3–4 below. The Exploria monorepo (`D:\Software-Dev-Projects\Jazer\Monorepo-Exploria-Restart`) is the working example of this exact layout |
| **money math, pricing, commission, tax** | **No money model exists yet.** Do not improvise one — money rules are a Lead-Developer decision; get it locked into `decisions.md`, then implement it in ONE canonical module and add a row here pointing at that file |
| **deploy, GitHub Actions, VPS, subdomain, SSL, red pipeline** | §9 below (**not yet wired**). Org-wide runbooks: `D:\Software-Dev-Projects\Foxcity-4-Project-Notes\Claude-AI-Guide\VPS-Management\`. For failures fire `/diagnose-deploy` |
| **"is the site up", "what's deployed"** | `/whats-live` skill — it will report "nothing deployed yet" until §9 is filled in |
| **branching, "where do I open the PR", release** | §8 below — **PRs target `staging`, never `main`** |
| **starting a new piece of work, where do docs go** | §7 below — `document/<YYYY-MM-DD>-<NNN>-<Kebab-Title>/` |
| **how this repo's AI setup works, adding a skill** | [.claude/README.md](.claude/README.md) + the org guide `D:\Software-Dev-Projects\Foxcity-4-Project-Notes\Claude-AI-Guide\Project-Scaffold\agentic-repository.md` |

---

## 1. What this project is

**Notebook** — a client project built by **W Labs**. Lead Developer: Jason Lipreso.

- **Product: TBD.** The client brief has not landed yet — its reserved home is [document/2026-09-12-001-Project-Details/](document/2026-09-12-001-Project-Details/), currently a stub. One paragraph goes here once it does: what the product does, who uses it, and the business model in 2–3 sentences.
- **Business rules: TBD.** Anything money-, tenancy-, or entitlement-shaped is a locked decision, not an implementation detail — see the routing row above.
- **Current status:** day zero — repository bootstrapped with the agentic knowledge scaffold, no application code. Live detail: [.claude/memory/current-status.md](.claude/memory/current-status.md).

**Do not fill this section in from guesses.** An inaccurate §1 is worse than a TBD, because the routing table sends every session through it.

---

## 2. Repo shape

Target shape (locked as D-001, mirroring the Exploria monorepo). Nothing below exists yet except the directories:

pnpm workspace (`pnpm-workspace.yaml` → `apps/*`, `packages/*`). Node ≥18, pnpm ≥8. Always `pnpm install` from the root.

| Path | What goes here | Status |
|---|---|---|
| [apps/](apps/) | Vue 3.5 + Vite 6 + TS 5.7 + Pinia + Vue Router 4 + Tailwind 3.4 + Reka UI. One folder per deployable front end, each its own workspace package with its own dev port and production subdomain | empty |
| `packages/` | Shared, platform-agnostic TypeScript: `types/` (the API contract), `services/` (one per domain + an axios singleton + the single mock↔API switch point), `utility/` (canonical business math). **Zero Vue, ships raw TS** | not created |
| [backend/](backend/) | Laravel 12 + PHP 8.2. **Not a pnpm workspace member** — a sibling directory reached by `cd backend`. See §5 | empty |
| [document/](document/) | All project documentation. See §7 | seeded |
| [scripts/](scripts/) | Repo tooling. Today: `refresh-docs.mjs` (§6) | seeded |
| [.claude/](.claude/README.md) | Shared session state and skills — checked in, replicates to every machine | seeded |

**When the workspace is scaffolded, update this table in the same commit** — it is the map every session reads before touching files.

---

## 3. Data-flow architecture — the golden rule

The target architecture (D-001), stated up front so the first code written already obeys it:

```
Vue view → composable (or Pinia auth store) → shared service → datasource switch → mock db  (early)
                                                             → axios              → Laravel (target)
```

- **Apps NEVER import the mock package directly.** Exactly one module (`datasource.ts` in the shared package) touches mock data, and it is the only place that branches on `VITE_USE_MOCK`. Keeping that rule is what makes the backend swap a one-file change instead of a rewrite.
- **Shared TypeScript types are the API contract.** Laravel emits exactly those snake_case fields; the backend adapts to the types, not the reverse.
- Response envelopes mirror Laravel and are the same in every app:
  - `ApiResponse<T> { success, message, data?, errors? }`
  - `PaginatedResponse<T> { success, data: T[], meta: { current_page, last_page, per_page, total } }`
- State: Pinia stores stay **auth-only**; per-view state lives in composables. Don't introduce global stores without a reason recorded in `decisions.md`.

## 4. Frontend rules

- **A type error in a shared package fails every app's build** — each app's tsconfig includes the shared packages and `vue-tsc -b` runs on build. Run `pnpm typecheck` from the root before committing package changes, and `pnpm build` (not just `pnpm dev`) before pushing — dev mode skips strict checks.
- Path aliases must be declared in BOTH each app's `tsconfig.app.json` and its `vite.config.ts` — change both or neither.
- Any demo/test-user affordance must be gated behind an env flag (`VITE_DEMO_MODE`) and can never ship against production auth.
- Brand tokens live in one shared Tailwind preset, not per app.

---

## 5. Backend architecture (Laravel 12 + PHP 8.2)

Conventions follow the Rosterlink-EMR reference project (`C:\Users\USER\Documents\Project-Sandbox\Rosterlink-EMR`), same as Exploria (D-002). The load-bearing rules:

- **Response envelope via a base `ApiController`** with `success()` / `error()` / `paginated()` helpers producing exactly the shapes in §3. **No Laravel API Resource classes** — models are serialized directly with `->select()` / `with('rel:id,col')`; the shared TS types are the contract.
- Controllers: flat under `app/Http/Controllers/Api/`, one per domain, docblock above each method naming `VERB /api/path?params`. Validation inline via `$request->validate()`.
- Routes: single `routes/api.php`, no version prefix, organized with banner comments carrying the originating task ID (e.g. `// ==== NOTES (2026-09-12-002 Phase 3) ====`). Register literal routes BEFORE `apiResource` wildcards; guard ids with `->whereNumber()`. **The endpoint reference generator parses this file — keep one `Route::` call per line.**
- Auth: **Sanctum stateless bearer tokens** (`SANCTUM_STATEFUL_DOMAINS` stays empty) unless a decision says otherwise.
- Migrations: fake-timestamp global counter `0001_01_01_NNNNNN_<verb>_<subject>_table.php`; related tables grouped into one file.
- CORS: custom `Cors.php` middleware prepended first so it answers OPTIONS before auth.
- Laravel 12 skeleton: no `RouteServiceProvider`, no `Http/Kernel` — middleware and rate limiters register in `bootstrap/app.php` / `AppServiceProvider::boot()`.
- Safety: `DB::prohibitDestructiveCommands()` in production; named rate limiters on public endpoints.

---

## 6. Auto-generated references — never hand-write an enumerable list

`node scripts/refresh-docs.mjs` (or the `/refresh-docs` skill) regenerates:

| File | Generated from |
|---|---|
| [document/0000-00-00-000-Memory/002-Endpoints-Reference.md](document/0000-00-00-000-Memory/002-Endpoints-Reference.md) | `php artisan route:list --json` when PHP + vendor are available; falls back to statically parsing `backend/routes/api.php` |
| [document/0000-00-00-000-Memory/003-Env-Vars-Reference.md](document/0000-00-00-000-Memory/003-Env-Vars-Reference.md) | the committed `.env.example` templates (`backend/`, each `apps/*`) |

- Both files open with a do-not-edit banner. Edit the source, rerun the script.
- The script is idempotent, works with the backend absent (it writes an honest "not present yet" placeholder), and **never commits** — you review the diff.
- **Convention: regenerate in the SAME commit as the change that made them stale** (any `routes/api.php` or `.env.example` edit).

---

## 7. Documentation conventions

- Work folders: `document/<YYYY-MM-DD>-<NNN>-<Kebab-Title>/` — ISO date + globally incrementing task number. The task ID is quoted in route banners, docblocks, and commit messages so any line of code traces back to its plan.
- Lifecycle inside a task folder: `findings/` → `plan/README.md` + `plan/phase-N-*.md` → implementation → `plan/completion/phase-N-complete.md`. Write a completion note when a phase ships — those notes become routing-table targets.
- Evergreen knowledge: [document/0000-00-00-000-Memory/](document/0000-00-00-000-Memory/) — the learnings ledger and the auto-generated references live here, plus one behavior doc per app once apps exist.
- Open questions go in a `questions/` subfolder; each ends with a `**Decision:**` line only the Lead Developer fills in. Answered ones get an ID in `.claude/memory/decisions.md` and are then final.
- Session logs: `.claude/worklog/` (see [.claude/README.md](.claude/README.md)).
- Never commit credentials into `document/` — not even "temporarily". Git history keeps them forever.

## 8. Git flow

- **Three tiers:** per-machine work branches → `staging` (integration) → `main` (production). Existing work branches: **`Workstation-PC`** (the Lead Developer's desktop — the default place changes get made). Add one per machine, named for the machine, branched from `staging`.
- **Day-to-day: commit on your machine's work branch, not on `staging` or `main`.** `git checkout Workstation-PC` is the normal starting state for a session on this desktop.
- **PRs ALWAYS target `staging`, never `main`.** This applies to Claude too: `gh pr create --base staging`. Only the Lead Developer promotes `staging` → `main` (via PR) — that merge IS the production release, and once deploy workflows exist it will fire them.
- **This includes documentation-only PRs.** Knowledge changes ride the same flow as code; a doc PR merged straight to `main` would be an unreviewed production release the moment CI/CD lands.
- After a `staging` → `main` promotion the two should be identical; if `staging` falls behind, fast-forward it (`git push origin origin/main:staging`).
- Never commit `.env*` (gitignored).

## 9. Deployment

**Not wired yet.** The intended target, once the client environment is confirmed, is the same pattern as Exploria: GitHub Actions on push to `main` → rsync to a Hostinger VPS (AlmaLinux + CyberPanel + OpenLiteSpeed), frontends as per-subdomain static sites with an SPA `.htaccess` fallback, backend built on the runner then migrated on the server, all hosts/paths/env from GitHub secrets.

- Org-wide deploy patterns and failure tables: `D:\Software-Dev-Projects\Foxcity-4-Project-Notes\Claude-AI-Guide\VPS-Management\`.
- When this lands: write the runbook into a `document/<date>-<NNN>-Deployment/` folder, point a routing row at it, replace this section with a pointer, and update the `/whats-live` and `/diagnose-deploy` skills with the real hosts and the real failures hit.

---

## 10. The conventions that keep this alive

The scaffold is cheap; the discipline is the product. Six rules — hold the line on all of them:

1. **Session ritual** — read `.claude/memory/current-status.md` at the start, write a `.claude/worklog/` entry and update the status file at the end.
2. **Gotcha → ledger before the incident is closed.** Project-specific lessons go to [001-Learnings.md](document/0000-00-00-000-Memory/001-Learnings.md); org-general ones (deploy, tooling, Windows) go ALSO to the matching guide in `Claude-AI-Guide/`. Entry bar: *cost real debugging time AND is non-obvious.*
3. **Enumerable change → `/refresh-docs` in the same commit.**
4. **New subsystem doc → routing-table row, or it doesn't exist.**
5. **Milestone → reality-check this file.** Status line, URLs, architecture claims, the TBDs in §1 and §9. A routing table that points at accurate docs is worthless if §1 describes last quarter's plan.
6. **Docs ride the same PR flow as code** (§8) — reviewable work, not drive-by edits.

**Growth path** (don't build these early — a solo repo doesn't need them): per-subsystem diagnostic skills as incidents accumulate, task-automation skills, git hooks that flag stale references, role-aware `help`. Add each one when asking has become slower than looking it up.

## 11. Personal / user-specific working style

Deliberately empty — personal preferences live in each developer's own `~/.claude/` memory, not here.
