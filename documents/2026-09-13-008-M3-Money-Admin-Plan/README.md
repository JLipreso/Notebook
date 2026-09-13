# 2026-09-13-008 — M3 Money & Admin Plan (milestone level)

The **Milestone 3 (payments + entitlements + admin)** plan — the last piece of the boss's plan-everything requirement. Same treatment as the [M2 plan](../2026-09-13-007-M2-Classroom-Plan/README.md): **milestone/phase level now**, file-level `Phase-3NN.md` detail written later against real code. With this, the roadmap is complete end-to-end: M1 file-level → M2 milestone-level → M3 milestone-level.

**Scope (concept-final.md delivery plan, D-011):** the canonical entitlements module (dynamic tier→feature mapping), subscription lifecycle (14-day trial → active / free floor / past-due), GCash QR + manual verification workflow, admin panel (dashboard counts, student/teacher/payment management, admin-editable pricing). M3 closes the MVP. Deferred stays deferred: PayMongo/Maya, AI add-on activation (schema ready), Grade Calculators, Open API.

## Ground rules

1. **Detail-fill trigger:** M3 detail is written when **M2's Phase-204 (courses) has merged** — the entitlements module's hardest rule (D-022 teacher-led access) reads course/teacher data, so the fill needs M2's real services. The M2 plan's trigger fires first by construction.
2. **Numbering:** `Phase-301…311` (the `3` = milestone), same scheme as M2's `Phase-2NN`.
3. The M1 plan's ["rules that outrank everything"](../2026-09-13-005-Implementation-Plan/README.md) apply unchanged. Two get sharper in M3:
   - **The money rule (CLAUDE.md money row):** ONE canonical entitlements module; no other code computes access. When Phase-304 lands, **update the CLAUDE.md money routing row to point at that file** — the row itself says so.
   - **Exact brief strings:** the payment status ladder (`unverified` → `in_progress` → `follow_up` → `received` / `fail_payment`) and launch prices (student 6900/12900 · teacher 8900/16900 minor units) are locked (D-008, schema §7). Never improvise beyond them.
4. **The schema is already designed** — tables 26–35 in [database-schema.md §7](../2026-09-13-004-M1-Foundation/plan/database-schema.md): plans/prices as INSERT-only history, features as data rows ("add a feature to a tier anytime = a row, zero code"), subscriptions with the `free_floor` status (D-023: never a hard lock), payments with the verification trail. Deviations go through `decisions.md`.
5. ~~Open questions~~ **All five M3 questions CLOSED (2026-09-13 → D-037…D-041)** — see [questions/](questions/README.md). The plan-shaping one, Q-M3-2, resolved to **D-040: web-only purchase flow** — the GCash QR screen lives exclusively in the browser app; native apps show plan status + "subscribe on the website".
6. **After M3 the MVP ships** — which makes **Q-004 (deploy environment)** blocking by M3's end. Deployment work (CLAUDE.md §9) should be wired *during* M3, not after it.

## Phase index (milestone level)

| Phase | Title | Builds | Depends on |
|---|---|---|---|
| Phase-301 | Admin workspace scaffold | `apps/admin/browser` thin shell (form factors per Q-M3-1), admin role gate on the M1 auth flow, admin seeding | M1 Phase-004 |
| Phase-302 | Billing database | migrations/models for tables 26–35 + launch seeds: plans, prices (₱69/₱129/₱89/₱169), features + free-floor rows (D-023 limits), AI addons (₱89, dormant) | M1 Phase-002 |
| Phase-303 | Contract & services extension | billing/admin domains in `@notebook/types`, services + mock fixtures per the datasource pattern | 302 · M1 Phase-003 |
| Phase-304 | **The entitlements module** | the ONE canonical module: plan→feature resolution, teacher-led access (D-022: student's course access checks the *course teacher's* subscription), free floor (D-023), trial state; backend + a mirrored read-only helper in `@notebook/utility` | 303 · M2 Phase-204 |
| Phase-305 | Subscription lifecycle | trial (14d from registration, D-030) → `active`/`free_floor`/`past_due` transitions via the Laravel scheduler, lifecycle notifications (M1 Phase-010 plumbing) | 304 |
| Phase-306 | Payment submission (browser app ONLY — D-040) | GCash QR display (client's static QR, config not code — D-041), reference-number + optional-message form, own payment status view; native apps get a plan-status screen pointing to the website, never a payment method | 305 |
| Phase-307 | Admin: payment verification | "New Payments" queue (`unverified`/`in_progress`/`follow_up`), status ladder with `payment_status_logs` follow-up notes, `received` ⇒ entitlements activates the subscription + notifies payer, "History" view | 301, 306 |
| Phase-308 | Admin: management pages | dashboard counts, student/teacher management, plan/price/feature editing (price edit = INSERT new `plan_prices` row — history preserved) | 301, 303 |
| Phase-309 | Enforcement rollout | flip gating ON in student/teacher apps (Q-M2-2 ends here): notebook/storage/font/sharing limits from the entitlements module, upgrade prompts, free-floor UX (read-only archives, never data loss) | 304–306 |
| Phase-310 | Packaging & release prep | native rebuilds (plan-status screen, NO payment UI — D-040), store-listing compliance pass verifying the Play payments policy is satisfied, deployment wiring (Q-004 must be closed) | 301–309 |
| Phase-311 | MVP acceptance & handoff | full acceptance against the brief's money claims, docs reality-check (CLAUDE.md §1/§2/§9 all get rewritten — the pre-product banner dies here) | all |

307/308 are independent of each other; 309 must not start before 307 proves a real payment can activate a real subscription.

## Pre-implementation work that is NOT a phase

- **Admin design pass** (Claude Design, Komposisyon palette): admin browser screens — dashboard, payment queue, payment detail + status ladder, user management, plan/pricing editor. Schedule with the teacher passes (Q-M2-1) or after; needed before Phase-301.
- **Deployment (Q-004):** the client environment conversation should start during M2 so §9 wiring lands inside Phase-310 instead of blocking it.

## Decisions this plan implements

D-008 (tiers/prices/status ladder, Admin-editable) · D-011 (M3 = money + admin) · D-014 (admin = role on the one users table) · D-022 (teacher-led growth — enforcement lives HERE, in the entitlements module) · D-023 (permanent free floor, never a hard lock) · D-024 (schema §7) · D-030 (trial starts at registration) · Q-M2-2's answer (no gating before Phase-309).
