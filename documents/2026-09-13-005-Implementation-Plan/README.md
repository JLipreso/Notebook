# 2026-09-13-005 — M1 Implementation Plan (phase-by-phase)

Detailed, self-sufficient implementation plan for **Milestone 1 (Notebook core)**, written so a developer new to this repo can execute it phase by phase on their own workstation without needing product decisions — **every open question is already answered and locked as a decision (D-001…D-031 in [.claude/memory/decisions.md](../../.claude/memory/decisions.md))**. If something seems undecided, it isn't: check the decision log first, and if it's genuinely missing, STOP and ask the Lead Developer — never improvise a product rule (CLAUDE.md §0).

**Scope:** M1 only (D-011/D-029): browser + mobile student app, notebook core, offline sync, Android packaging. M2 (classroom) and M3 (payments/admin) get their own plan when M1 nears completion. Deployment is out of scope (Q-004 still open; CLAUDE.md §9).

## How to use this plan

1. Do the phases **in order** — each lists its prerequisites and each ends with a working, verified state. Don't start a phase until the previous one's acceptance checklist is fully green.
2. One phase = one PR to `staging` (never `main` — CLAUDE.md §8). Branch: your own machine branch (Phase-001 sets it up).
3. When a phase ships, write `completion/Phase-NNN-complete.md` in this folder (what shipped, deviations, gotchas) and update [.claude/memory/current-status.md](../../.claude/memory/current-status.md).
4. Every phase ends with the same two gates: `pnpm typecheck` and `pnpm build:all` green from the repo root, plus that phase's own checks.
5. Session ritual (CLAUDE.md §10): read `current-status.md` at session start; append a `.claude/worklog/` entry at session end. Hit a non-obvious gotcha? Log it in [001-Learnings.md](../../documents/0000-00-00-000-Memory/001-Learnings.md) before closing the incident.

## Phase index

| Phase | Title | Builds | Depends on |
|---|---|---|---|
| [Phase-001](Phase-001.md) | Workstation onboarding & environment | your machine branch, running dev environment | — |
| [Phase-002](Phase-002.md) | M1 database: migrations, models, seeders | tables 1–13, PSGC + notebook_types seeds | 001 |
| [Phase-003](Phase-003.md) | Type contract & services layer | `@notebook/types` domain files, services, mock fixtures | 002 |
| [Phase-004](Phase-004.md) | Auth: Firebase → Sanctum | sign-up/sign-in on both form factors | 003 |
| [Phase-005](Phase-005.md) | Profile & PSGC address | profile screen, address dropdown chain | 004 |
| [Phase-006](Phase-006.md) | Notebook library | library CRUD, covers, school-year archive | 004 |
| [Phase-007](Phase-007.md) | Paper templates & page editor | `@notebook/ui` paper renderer + Tiptap editor, page CRUD | 006 |
| [Phase-008](Phase-008.md) | Attachments: images & PDF | uploads, quota, attach/view UI | 007 |
| [Phase-009](Phase-009.md) | Offline sync engine | SQLite adapter, outbox, pull/push, SyncController | 007 |
| [Phase-010](Phase-010.md) | Sharing links & notifications | read-only share links, in-app notifications | 007 |
| [Phase-011](Phase-011.md) | Android packaging | `native/` Android project, portrait lock, device build | 008–010 |
| [Phase-012](Phase-012.md) | M1 acceptance & handoff | full acceptance run, docs reality-check | all |

Phases 008, 009, 010 are independent of each other (all sit on 007) — do them in the listed order unless the Lead Developer reprioritizes.

## The rules that outrank everything in these files

- **Architecture golden rule (CLAUDE.md §3):** view → composable/auth store → shared service → datasource switch → mock **or** axios. Views NEVER import mock data or call axios directly; `packages/services/datasource.ts` is the only module reading `VITE_USE_MOCK`.
- **D-027 guardrail:** `apps/*` form-factor apps contain ONLY layout, navigation, composition. Any component both form factors need lives in `packages/ui`. A domain component under `apps/` is wrong by definition.
- **Types are the contract (D-005):** Laravel emits exactly the snake_case fields declared in `@notebook/types`. The backend adapts to the types, never the reverse.
- **Backend shape is locked (CLAUDE.md §5):** `ApiController` envelope, flat `Api/` controllers, inline `$request->validate()`, no API Resource classes, one `Route::` per line with task-ID banner comments, `->whereUuid()` on id params.
- **Same-commit conventions:** `routes/api.php` or any `.env.example` change ⇒ run `node scripts/refresh-docs.mjs` in the same commit. New subsystem doc ⇒ CLAUDE.md routing-table row in the same commit.
- **Never commit:** `.env*` files, the Firebase service-account JSON, any credential. Git history keeps them forever.

## Decisions this plan implements (read them once, in full)

D-001/D-002 (stack + conventions) · D-005 (types contract) · D-009…D-013 (paper templates, notebook-first, Tiptap JSON, UUIDv7) · D-014/D-015 (one users table, Firebase→Sanctum) · D-016/D-017 (offline scope, Capacitor) · D-019 (MySQL prod / sqlite dev) · D-020 (no geolocation) · D-022/D-023 (teacher-led growth, permanent free floor — schema only in M1) · D-024 (schema + structure plan) · D-025…D-029 (platforms, form factors, native bundle, M1 = browser+mobile) · **D-030 (appId `com.notebook.student`; PSGC = latest snapshot; trial starts at registration)** · **D-031 (no contractor branding anywhere)**.
