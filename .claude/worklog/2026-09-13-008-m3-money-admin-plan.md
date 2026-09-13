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
