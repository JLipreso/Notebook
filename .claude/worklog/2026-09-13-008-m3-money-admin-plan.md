# 2026-09-13 — Session 008: M3 money & admin plan (milestone level)

**Context:** continuation of the parallel planning track (007 = M2). With this, the boss's plan-everything requirement is met: M1 file-level, M2 + M3 milestone-level, detail-fill triggers written for both.

## What happened

1. Wrote [documents/2026-09-13-008-M3-Money-Admin-Plan/README.md](../../documents/2026-09-13-008-M3-Money-Admin-Plan/README.md) — Phase-301…311: admin scaffold → billing DB (tables 26–35, launch seeds) → contract → **the entitlements module (Phase-304, THE canonical money module)** → subscription lifecycle → payment submission → admin verification queue → admin management → enforcement rollout (paywall flips on here, closing Q-M2-2's free period) → packaging/release prep (deployment must be wired by here) → MVP acceptance (the pre-product banner dies in Phase-311).
2. Detail-fill trigger: **M2 Phase-204 merged** (D-022 teacher-led access needs real course services). Numbering `Phase-3NN`.
3. Opened [questions/](../../documents/2026-09-13-008-M3-Money-Admin-Plan/questions/README.md) — five: Q-M3-1 admin browser-only (rec: yes) · **Q-M3-2 Google Play payments policy vs in-app GCash — the plan-shaping one, flagged close-FIRST; recommended web-purchase flow (compliant, keeps 100% of revenue)** · Q-M3-3 GCash operational details (client's account/QR, staff) · Q-M3-4 past_due grace length (rec: 7 days) · Q-M3-5 USD deferred to PayMongo/Maya (rec: yes).
4. CLAUDE.md routing row added (same commit) with the do-not-implement-yet warning.

## Gotcha worth surfacing (not a ledger entry — no debugging cost, but plan-critical)
The brief's in-app GCash subscription flow collides with Google Play's Payments policy (digital subscriptions ⇒ Play Billing, 15–30% fee). Surfaced as Q-M3-2 with a compliant web-purchase recommendation BEFORE any code exists — this is exactly the kind of thing that's cheap in planning and brutal at store-review time. iOS will be stricter when its turn comes (D-026: last).

## Next
- Boss/client: Q-M3-2 + Q-M3-3 (longest latency — send now). Lead Developer: Q-M3-1/4/5.
- Start the Q-004 deployment conversation during M2 so Phase-310 isn't blocked.
- Roadmap docs are now complete; remaining Lead-Dev planning work = M2 question answers, teacher + admin design passes, detail-fills when triggers fire.

## Addendum (same session) — Q-M2-1 answered: teacher design passes NOW (D-033)

- Lead Developer answered Q-M2-1 with "run them now" → **D-033** in decisions.md; the question's `**Decision:**` line filled.
- Wrote **briefs 4 (teacher mobile, 12 screens) + 5 (teacher browser, 12 screens)** into the design home ([2026-09-13-006-Brand-Colors/](../../documents/2026-09-13-006-Brand-Colors/README.md)), pass table extended. Both grounded in schema §6's actual rules: expired label, clone-course, availability windows, lesson-gated quizzes, deliberate answers-release, question-bank immutable copy, declined-invitations-with-reason, manual+auto grading, one chat room per course. Teacher persona: Gng. Liza Manalo, Math 7. Mobile = monitoring/quick-edit/grading-on-the-go; desktop = heavy authoring (lesson editor + grading table are the hero screens).
- Rhythm as before: Lead Developer runs pass 4 in Claude Design, exports `Notebook-Teacher-Mobile-12-screens.html` here, then pass 5.

## Addendum 2 (same session) — pass 4 delivered & verified

- The Lead Developer ran pass 4; export landed as `Notebook-Teacher-Mobile-12-screens.html` (~472 KB). Verified programmatically against the brief before logging: all D-032 hexes, Fraunces+Figtree, Margin Red ≈ once per screen (12 hits / 12 screens), every schema-§6 rule visible (Expired, declined-with-reason, question bank, gated quizzes, answers-release, grading, roster, calendar, chat), persona correct, D-031 grep clean. Completion note: `design-pass-4-complete.md`; pass table updated; status item 14 updated. Pass 5 (teacher browser) is next, referencing the pass-4 canvas.

## Addendum 3 (same session) — pass 5 delivered: M2 teacher design surface COMPLETE

- Pass 5 export verified the same way (`Notebook-Teacher-Browser-12-screens.html`, ~519 KB): palette/typography/persona correct, Margin Red ×13/12 screens, desktop chrome (1440, sidebar), all §6 rules PLUS the D-009 lesson PDF/print affordance (×15). Completion note: `design-pass-5-complete.md`. **Teacher design surface done** — remaining passes parked: tablet (D-029), admin (M3 pre-work).
- **Junior progress:** PR #13 (M1 Phases 002–003, database + contract/services) MERGED — first implementation code is in `staging`. It bundled two phases against the one-PR-per-phase rule; accepted this once, noted in status for review discipline. **Tracker chips phase-001…003 flipped to done via write_db** (batch, atomic).
- PR #14 (pass-4 log) also merged.

## Addendum 4 (same session) — all Lead-Developer questions closed (D-034…D-039)

Prompted the Lead Developer through every open question that was theirs to decide; all six answered (recommended options taken):
- **D-034** Q-M2-2: no gating in M2 — enforcement once, in M3's entitlements module.
- **D-035** Q-M2-3: M2 invitations in-app only — no email provider; sender revisited in M3.
- **D-036** Q-M2-4: lesson PDF = client print stylesheet, a real Phase-205 deliverable.
- **D-037** Q-M3-1: admin browser-only.
- **D-038** Q-M3-4: past_due grace = 7 days (one scheduler constant).
- **D-039** Q-M3-5: USD deferred to PayMongo/Maya.

Question files' `**Decision:**` lines filled; both intros updated. **Open project-wide: only Q-M3-2 + Q-M3-3 (boss/client) and Q-004 (deploy).**

## Addendum 5 (same session) — Q-M3-2/Q-M3-3 closed: EVERY product question decided

- The Lead Developer answered the last two with business-model authority. **Q-M3-2 took a correction first**: the initial answer assumed the Play fee "doesn't apply to QR payments" — clarified that an in-app GCash QR for the app's own subscription is still a prohibited alternative payment method under Play policy (where the payment executes doesn't matter). Re-asked with that understanding → **D-040: web-only purchase flow** (QR screen exclusively in the browser app; native apps show plan status + "subscribe on the website"). The QR ops flow itself is unchanged.
- **D-041** (Q-M3-3): client's business GCash, one static QR + reference matching (amount-QRs rejected — fight dynamic pricing), one launch admin, follow-up off-app with the `payment_status_logs` note mandatory as audit record.
- M3 plan README updated (rule 5, Phase-306/310 rows), CLAUDE.md M3 routing row updated. **Project-wide: D-001…D-041 all decided; Q-004 (deploy) is the sole open question.** Lesson echo (same as session 005's): a confidently-worded answer that contains a factual misconception gets corrected before it becomes a decision — the D-ID records the informed choice.

## Session close (paused — Lead Developer to a meeting)

- PRs #16, #17, #19 all merged — planning batch fully in `staging`. Planning backlog is EMPTY.
- **Junior's PR #18 (M1 Phase-005, profile & PSGC) is OPEN awaiting the Lead Developer's review**; tracker shows phase-005 in_progress (001–004 done).
- Next triggers, in whoever's court: PR #18 review (Lead Dev) · Q-004 with the client · M2 detail-fill fires when M1 Phases 007+009 merge · optional `staging`→`main` promotion · default-branch chore.
