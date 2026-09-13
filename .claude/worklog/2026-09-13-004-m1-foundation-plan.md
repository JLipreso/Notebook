# 2026-09-13 — M1 Foundation plan drafted (task 2026-09-13-004)

**Branch:** Workstation-PC · **Who:** Claude session with the Lead Developer · Follows 2026-09-13-003 (concept locked, PR #1 open to staging)

## What happened

- Lead Developer set the next objective: plan the database schema + project structure as md dev references FIRST; two client documents ("Project Structure with ER Diagram", "Project Requirements and Features summary") will be GENERATED from them afterward for the boss's review.
- Created `documents/2026-09-13-004-M1-Foundation/plan/`:
  - `README.md` — phase plan (1 review → 2 generate client docs → 3 workspace scaffold → 4 M1 migrations), doc→deliverable mapping, open items O-1/O-2/O-3.
  - `database-schema.md` — full MVP schema: 35 tables tagged M1/M2/M3 with sync class [RW]/[RO]/[ON], 4 Mermaid ERDs (identity/address, notebook, classroom, billing), schema-wide conventions (UUIDv7 CHAR(36), PSGC natural-key exception, utf8mb4, soft deletes on user content, integer-minor-unit money), and the offline-sync contract (client_updated_at LWW, tombstone pulls, attachment outbox). Unified assessment tree with `kind`; single notifications table; plan/feature matrix for dynamic tiers; payments with the brief's exact 5-status ENUM + status log.
  - `project-structure.md` — pnpm workspace layout; PROPOSES two packages beyond CLAUDE.md §2's three: `@notebook/ui` (Tiptap editor + paper templates shared by student/teacher) and `@notebook/sync` (offline engine with storage adapters); Capacitor inside apps/student; flat Api/ controller map incl. SyncController; env var plan; dev ports 5171–5173 (proposed).
- Updated current-status (next-steps item 3 → drafted/awaiting review).

## Open / next

- **Lead Developer reviews both plan docs** (schema table-by-table; the two proposed packages; O-1/O-2/O-3).
- After "locked": Phase 2 = generate the two client documents; Phase 3 = workspace scaffold (update CLAUDE.md §2 same commit); Phase 4 = M1 migrations (tables 1–13 + PSGC/notebook_types seeders).
- Not done on purpose: no code, no migrations, no CLAUDE.md §2 edit (happens with the scaffold commit), client docs not generated yet (plan must lock first).

## Session part 2 — plan locked, merged, client docs generated

- Lead Developer said "locked" → D-024 recorded; status lines in both plan docs flipped to LOCKED; committed `20bc284`, PR #2 → staging, **merged by the Lead Developer**.
- **Phase 2 DONE**: generated the two client review documents as private artifacts (shared design identity: paper-white/ink/margin-red, Zilla Slab + Source Sans 3 + Plex Mono, both themes):
  - "Project Structure with ER Diagram" → https://claude.ai/code/artifact/4d9c29a4-0ec5-49af-af38-4db06094d972 (4 Mermaid ERDs, master table index, sync contract, repo tree)
  - "Project Requirements & Features Summary" → https://claude.ai/code/artifact/fcd28a9f-c1fc-4a64-a973-e4bdcaa68399 (CSS-drawn Composition/Writing paper samples, milestone table, pricing, GCash 5-status flow)
  - Completion note with links: `documents/2026-09-13-004-M1-Foundation/plan/completion/phase-2-complete.md`. Regeneration rule: edit md sources → republish to the SAME URLs.
- Next: Phase 3 workspace scaffold, Phase 4 M1 migrations.
- **Session end state: ON HOLD awaiting boss review of the two documents.** Lead Developer saved the share links to `documents/2026-09-13-004-M1-Foundation/Artifact.md`. Resume plan (revision loop vs approved path) is written into current-status item 4 — a fresh session picks it up from there. No implementation before the go signal.

## Gotchas hit

- None. Note for the ERD-to-client-doc step: keep Mermaid attribute lists trimmed (GitHub renders them, but full 35-table single diagrams are unreadable — per-domain split is deliberate).
- `gh pr create` on PowerShell 5.1: a `--body` here-string containing double quotes gets re-split into args — use `--body-file` instead. (Also logged for org guide if it recurs.)
