# Phase 012 — M1 acceptance & handoff

**Goal:** prove M1 is done as a whole, leave the repo's knowledge layer telling the truth, and stage the demo the Lead Developer shows the boss. No new features here — gaps found go back to their phase.

**Prereq:** Phases 001–011 all accepted.

## 1. Full acceptance run (the M1 definition of done, from concept-final.md §Delivery)

Run this end-to-end on BOTH form factors (browser desktop; Android device), live API, fresh `migrate:fresh --seed` backend, `VITE_DEMO_MODE=false`:

| # | Scenario | Pass = |
|---|---|---|
| 1 | Sign up (email+password) with full profile incl. PSGC address | lands signed-in, profile persisted |
| 2 | Sign in with Google on a second account | works; roles independent |
| 3 | Create one notebook of EACH of the 7 types | correct paper per type, correct default school year |
| 4 | Write a multi-block page (headings, lists, table) on Composition | autosaves; reload identical; text on the lines |
| 5 | Attach image + PDF; set a cover | render, open, shelf shows cover |
| 6 | Archive last year's notebook | moves to archive shelf, still readable (D-023: never locked away) |
| 7 | Android: full offline session (create/edit/attach) → reconnect | syncs clean; LWW conflict resolves server-wins; nothing lost |
| 8 | Share a notebook read-only; open signed-out; revoke | renders on paper; revoked link 404s |
| 9 | Notifications: welcome note present, mark-read works | badge counts correct |
| 10 | Cross-user probe (user B guesses user A's ids) | 404s everywhere |
| 11 | `pnpm typecheck` + `pnpm build:all` + `php artisan test` | all green |

Log every failure as a checklist in the completion note, fix in the owning phase's terms (same conventions), re-run the scenario.

## 2. Knowledge-layer reality check (CLAUDE.md §10 rule 5)

- [ ] CLAUDE.md: banner + §1 status line updated to "M1 feature-complete"; §2 table statuses; the **money row still points at locked-rules-no-code** (true until M3)
- [ ] `.claude/memory/current-status.md` rewritten: M1 done, M2 next, open questions (Q-004 only)
- [ ] `001-Learnings.md` holds every real gotcha from Phases 002–011 (if it's empty, that's a red flag, not a clean run)
- [ ] `node scripts/refresh-docs.mjs` — endpoints + env references current
- [ ] Each phase has its `completion/Phase-NNN-complete.md`
- [ ] `grep -ri "w labs" .` returns nothing (D-031) — run it, contractor branding creeps back through copy-paste

## 3. Demo prep

Seed a demo account (dedicated seeder, NOT fixtures in prod tables by hand): student with 3 notebooks across 2 school years, filled pages per template showpiece (Writing with penmanship lines, Timesheet with a table), one shared link ready. Boss demo script: shelf → open Composition → type on the lines → airplane-mode edit on the phone → reconnect sync → share link on the projector.

## 4. Handoff

- PR (`M1 Phase 012 — acceptance`) with the completion note = the acceptance report (scenario table with ✅/❌ history).
- The Lead Developer reviews, merges to `staging`, and owns the `staging` → `main` promotion decision.
- M2 planning (classroom layer) starts as a NEW task folder with its own phase plan — do not extend this one.

## Acceptance checklist

- [ ] All 11 scenarios ✅ on both form factors
- [ ] Knowledge layer current; branding grep clean
- [ ] Demo account seeded and script rehearsed once
- [ ] PR merged; M1 declared done by the Lead Developer (not by this checklist alone)
