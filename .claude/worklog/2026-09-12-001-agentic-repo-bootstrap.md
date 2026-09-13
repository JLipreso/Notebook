# 2026-09-12-001 — Agentic repository bootstrap

**Who:** Jason Lipreso (+ Claude Code)
**Branch:** `main` (no commits existed before this session)
**Goal:** make the fresh Notebook repo "know everything" per the org guide `Claude-AI-Guide/Project-Scaffold/agentic-repository.md`.

## Starting point

Empty repo: `README.md` (0 bytes), empty `apps/` and `backend/`, remote `https://github.com/JLipreso/Notebook.git`, zero commits, no branches.

## Decisions taken this session

Recorded as D-001…D-005 in [../memory/decisions.md](../memory/decisions.md):

- Stack mirrors the Exploria monorepo (Laravel 12 + PHP 8.2 backend; Vue 3.5 / Vite 6 / TS / Pinia / Tailwind pnpm workspace).
- Backend conventions follow Rosterlink-EMR (inherited via Exploria).
- Three-tier git flow: work branches → `staging` → `main`; PRs base on `staging`, docs included.
- Agentic scaffold sized at the guide's small ("Exploria") tier — solo developer.
- Shared TS types are the frozen API contract.

Four things are explicitly **not** decided and block real work — Q-001…Q-004 in the same file (product brief, money rules, tenancy/roles, deployment environment).

## What was done

| Pillar (guide §1) | Delivered |
|---|---|
| 1 — Context-First routing | [CLAUDE.md](../../CLAUDE.md): 15-row routing table, repo shape, data-flow golden rule, frontend/backend conventions, doc conventions, git flow, deployment placeholder, the six keep-alive conventions |
| 2 — Shared session state | `.claude/` — README, `settings.json` (`Edit(.claude/**)` baseline, validated), `memory/current-status.md`, `memory/decisions.md`, this worklog |
| 3 — Auto-generated references | [scripts/refresh-docs.mjs](../../scripts/refresh-docs.mjs) + `documents/0000-00-00-000-Memory/002-Endpoints-Reference.md` and `003-Env-Vars-Reference.md` |
| 4 — Learnings ledger | `documents/0000-00-00-000-Memory/001-Learnings.md` — 11 same-stack entries carried from Exploria, quarantined in their own section and marked as not-yet-hit-here |
| 5 — Skills | `.claude/skills/{help,whats-live,diagnose-deploy,refresh-docs}/SKILL.md` |

Also: `README.md` (public entry point → CLAUDE.md), `.gitignore`, `.gitattributes`, `.gitkeep` in `apps/` and `backend/`, `documents/0000-00-00-000-Memory/000-README.md`.

## Notable choices

- **No invented product content.** CLAUDE.md §1 is a deliberate TBD with an instruction not to fill it from guesses; the routing table sends product questions to the Lead Developer instead of to a fabricated answer.
- **No dead routing rows.** Every row points at something that exists today. Topics with no home yet (money math, deployment) route to an explicit "doesn't exist, don't improvise" instruction rather than a broken link.
- **The generator degrades honestly.** With no backend and no `.env.example`, it writes a "not present yet" placeholder naming the next step — never a silently empty table.
- **Ledger provenance is explicit.** Carried-over entries are separated from Notebook's own (currently none) so nobody mistakes borrowed lessons for this project's history; the rule is to promote an entry when we actually hit it.
- `diagnose-deploy` and `whats-live` carry the same provenance caveat and tell the next session to fill in real hosts when deployment lands.

## Verification

- `node scripts/refresh-docs.mjs` → exit 0, both placeholders written.
- Parser paths proven against real input (Exploria's `routes/api.php` + `.env.example` copied into a scratch tree): **160 routes** parsed with correct section banners, prefix groups, and controller actions; env vars emitted with their comment notes. Scratch tree deleted.
- `.claude/settings.json` parses as valid JSON.

## Next

1. Commit this bootstrap and create `staging` off it; set `staging` as the default PR base on GitHub.
2. Get the client brief → `documents/2026-09-12-001-Project-Details/about.md` (created by the Lead Developer mid-session; currently contains only `ty`). The routing table and §1 already point at it — filling it in is what unblocks Q-001.
3. Then the workspace scaffold and the Laravel install (see [../memory/current-status.md](../memory/current-status.md)).

## Addendum — permission grant (same session)

Lead Developer granted Claude standing write access to `.claude/` (D-006). `settings.json` now carries `Edit(.claude/**)` + `Write(.claude/**)`; rationale, scope boundary, and the reload caveat are documented in [../README.md](../README.md).

Noted while checking for conflicts: the Lead Developer's personal `~/.claude/settings.json` already allows `Edit(*)` / `Write(*)` globally, so on that machine the project rule is redundant. It is not redundant anywhere else — it is what carries the grant to a second machine, a fresh clone, or another developer.

## Blockers

Q-001 (what Notebook actually is) blocks essentially all application work. The scaffold is complete and useful without it.
