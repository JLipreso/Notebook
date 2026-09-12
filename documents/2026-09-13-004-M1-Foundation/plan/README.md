# 2026-09-13-004 — M1 Foundation Plan (Database Schema + Project Structure)

Planning stage for the Milestone 1 foundation, produced as development reference **before** any migration or scaffold code is written. Everything here derives from the locked decisions (D-001…D-023 in [.claude/memory/decisions.md](../../../.claude/memory/decisions.md)) and the finalized concept ([concept-final.md](../../2026-09-12-001-Project-Details/concept-final.md)).

## Documents in this plan

| File | What it is | Feeds which boss document |
|---|---|---|
| [database-schema.md](database-schema.md) | Full MVP schema (M1–M3, milestone-tagged): every table, column, FK, index note, the offline-sync contract, and Mermaid ER diagrams per domain | **"Project Structure with ER Diagram"** (the ERD sections) |
| [project-structure.md](project-structure.md) | Monorepo layout: pnpm workspace, apps, shared packages, backend organization, Capacitor placement, env/port/naming conventions | **"Project Structure with ER Diagram"** (the structure sections) |
| [concept-final.md](../../2026-09-12-001-Project-Details/concept-final.md) + [validation README §3](../../2026-09-13-003-Concept-Validation/README.md) (already written) | Identity, milestones, feature list, business model | **"Project Requirements and Features summary"** |

**Workflow:** Lead Developer reviews these md files → corrections land here first → then the two client-facing documents are generated from them (polished, printable). The md files stay the canonical dev reference; the generated documents are derived artifacts and are never edited by hand.

## Why the schema covers M1–M3, not just M1

The boss ER diagram needs the whole product picture, and two locked decisions reach across milestones: UUIDv7 keys (D-013) and the offline-sync columns (D-016) must be right in the *first* migration, and M2's assessment tree shapes M1's notification and file tables. Milestone tags on each table keep the M1 implementation cut unambiguous — **only M1-tagged tables get migrations in this task's implementation phase.**

## Phases

1. **Phase 1 — Schema review & lock** (this folder): Lead Developer reviews `database-schema.md` + `project-structure.md`; corrections are edited in; anything contentious becomes a Q-file. Exit: Lead Developer says "locked".
2. **Phase 2 — Generate the two client documents** from the locked plan (separate task or same folder; format decision — artifact page / PDF — taken then).
3. **Phase 3 — Scaffold the pnpm workspace** per `project-structure.md` (root workspace, `apps/student`, `packages/*`, CLAUDE.md §2 table updated in the same commit).
4. **Phase 4 — M1 migrations + models** per the M1-tagged tables in `database-schema.md` (fake-timestamp counter per CLAUDE.md §5), plus the PSGC seeder. `/refresh-docs` in the same commit as any route/env change.
5. Completion notes land in `plan/completion/phase-N-complete.md` as each phase ships (§7 convention).

## Open items surfaced while planning (need Lead Developer input, not blocking review)

- **O-1 · PSGC data source file**: the PSA publishes the PSGC as a quarterly XLSX. Phase 4 needs the chosen snapshot committed as a seeder asset (`backend/database/seeders/data/psgc-YYYYQN.csv`). Any preference on snapshot quarter, or take the latest at implementation time?
- **O-2 · Dev ports** are proposed in `project-structure.md` (5171/5172/5173) — confirm or adjust to match Exploria habits.
- **O-3 · Trial start**: does the 14-day trial start at registration, or at first paid-feature use? Schema supports either (`subscriptions.trial_ends_at`); the onboarding flow needs the answer. Default assumption: at registration.
