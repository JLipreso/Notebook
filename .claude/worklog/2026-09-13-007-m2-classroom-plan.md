# 2026-09-13 — Session 007: M2 classroom plan (milestone level)

**Context:** Boss approved the parallel split — the junior developer implements M1 (their `Workstation-Laptop` branch exists on the remote; no phase PRs merged yet) while the Lead Developer plans M2.

## What happened

1. Wrote [documents/2026-09-13-007-M2-Classroom-Plan/README.md](../../documents/2026-09-13-007-M2-Classroom-Plan/README.md) — **milestone-level only, by design**: phase index Phase-201…212 (teacher scaffold → classroom DB → contract → courses → lessons+PDF → quiz builder → invitations → student experience → quiz taking/scores → chat → packaging → acceptance), each with builds + M1-phase dependencies. File-level `Phase-2NN.md` detail is deliberately deferred behind a written trigger: **M1 Phase-007 (editor) + Phase-009 (sync) merged to `staging`** — detail written earlier would reference code that doesn't exist and rot with every M1 deviation.
2. The plan redesigns nothing: schema §6 (tables 14–25) already carries the classroom/assessment/chat domain with the brief's rules; the plan builds on it and routes deviations through `decisions.md`.
3. Opened [questions/](../../documents/2026-09-13-007-M2-Classroom-Plan/questions/README.md) — four, each with a `**Decision:**` line for the Lead Developer: Q-M2-1 teacher design passes (when/which screens), Q-M2-2 confirm no paywall pre-M3, Q-M2-3 invitation email sender (client-facing — provider + from-domain, or in-app-only fallback), Q-M2-4 lesson PDF client vs server. All to close before detail-fill; none block M1.
4. CLAUDE.md routing row added (same commit): "M2, classroom layer, teacher portal…" → the plan README, with an explicit **do-not-implement-yet** warning.

## Numbering note
M2 phases are `Phase-2NN` so M1's `Phase-0NN` sequence never shifts. Same scheme reserved for M3 (`Phase-3NN`).

## Next
- Lead Developer: answer Q-M2-1…4 (→ D-IDs), run the teacher design passes per Q-M2-1's answer.
- Watch M1: when Phases 007+009 merge, fire the detail-fill (write Phase-201…212 files against real code, extend the build tracker with M2 rows).
