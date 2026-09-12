# 2026-09-13 — Concept validation (task 2026-09-13-003)

**Branch:** Workstation-PC · **Who:** Claude session with the Lead Developer

## What happened

- The product brief landed in `documents/2026-09-12-001-Project-Details/about.md` (Lead Developer wrote it; instruction: never edit it directly — companion files only).
- Scanned the MIIT/Elea LMS monorepo (`D:\Software-Dev-Projects\Jazer-Tech\Monorepo-MIIT`) end-to-end via an Explore agent → `documents/2026-09-13-003-Concept-Validation/findings/miit-scan.md`. Key facts: Laravel 10 (not 11), Bootstrap not Tailwind, no Pinia, 64 MySQL tables, no payments/invitations/offline anywhere, Firebase→Sanctum exchange already proven, `firebase-service-account.json` committed (do not copy that).
- Wrote the validation → `documents/2026-09-13-003-Concept-Validation/README.md`: concept validated with 3 caveats — (A) the notebook editor (the differentiator) is the least-specified part of the brief, (B) phasing inversion: Phase 1 students consume content Phase 3 teachers can't author yet, (C) MVP as written = 4 products. Proposed a 3-milestone cut (notebook core → classroom layer → money/admin), a 12-item do-differently list from MIIT, and a full stack recommendation (Tiptap, Capacitor + community SQLite, custom UUIDv7 cursor sync, Reverb, FCM, PSGC address seed).
- Opened Q-005…Q-016 as one file each under `questions/` (options + recommendation + blank `**Decision:**`), registered them in `decisions.md`, updated `current-status.md`.

## Open / next

- **Everything waits on the Lead Developer's Q-005…Q-016 answers.** Highest-leverage: Q-005 (scope), Q-006 (what a notebook page IS), Q-007 (user model), Q-009 (PK strategy — unretrofittable).
- After decisions: finalize concept paragraph beside about.md, fix CLAUDE.md §1, then pnpm workspace scaffold + first data-model plan.
- Not done on purpose: no CLAUDE.md edits (decisions not locked), no code, about.md untouched.

## Session part 2 — Lead Developer clarifications + first locked decisions

The Lead Developer replied with 4 clarifications (reference photos of real PH notebook pages attached in chat) and answered 4 option prompts. Recorded as **D-007…D-013** in `decisions.md`:

- **D-007** Pusher Channels (paid), not Reverb.
- **D-008** MVP collects raw scores only; final-grade computation ships later as free per-level "Grade Calculator" add-on tools (grades differ per level and per school).
- **D-009** Lessons downloadable as PDF + directly printable (for students without devices).
- **D-010** Notebook types = faithful paper templates: Composition (single-ruled + red margin, HS/college), Writing (blue/red penmanship guide lines + Date header + Teacher's/Parent's Signature footers, preschool).
- **D-011** (closes Q-005) Notebook-first MVP: M1 notebook core → M2 classroom + minimal teacher portal → M3 payments/admin.
- **D-012** (closes Q-006/Q-006b) Pages are typed Tiptap-JSON block documents on paper templates, schema reserves a future drawing/ink block; Writing type is visual-style-only in MVP.
- **D-013** (closes Q-009) UUIDv7 PKs everywhere, client-generated.

Updated in place: validation README (§3 concept + milestones, §4 item 1, §5 stack rows for Pusher + PDF/print), q-005/q-006/q-009 Decision lines, decisions.md open-questions table, current-status.

## Session part 3 — all remaining questions closed; concept finalized

Lead Developer answered every remaining question via option prompts — all on the recommended option. Recorded as **D-014…D-023**: single `users` table + role (Q-007), Firebase→Sanctum exchange (Q-008), notebooks-RW + courses-RO offline scope (Q-010), Capacitor + community SQLite (Q-011), Tiptap 2 (Q-012), MySQL 8 prod (Q-014), geolocation dropped (Q-015), course-room chat first (Q-016), teacher-led growth (Q-013a), permanent limited free tier (Q-013b).

Follow-through:
- All 12 question files now carry filled `**Decision:**` lines; decisions.md open table reduced to **Q-004 (deploy) only**, with a closed-questions trail (Q-001/Q-002/Q-003 closed by the brief + D-IDs).
- Wrote **`documents/2026-09-12-001-Project-Details/concept-final.md`** — the one-page decision-backed concept (about.md untouched, as always).
- **CLAUDE.md updated**: §1 rewritten (product/business/status, every claim traced to about.md or a D-ID), product routing row → concept-final.md, money routing row → locked rules + "one canonical entitlements module when implemented".
- Validation README status marked COMPLETE.

Next session: Milestone 1 (notebook core) data-model plan — see current-status item 3.

## Gotchas hit

- None repo-side. MIIT's own `documents/2026-07-25-000-Claude/analysis.md` contains wrong stack claims (says Laravel 11 + Pinia/Tailwind; actual: Laravel 10, Bootstrap, no Pinia) — verified against `composer.json`/`package.json`. Trust but verify that doc if consulted again.
