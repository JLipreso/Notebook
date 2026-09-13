# M2 open questions

Each ends with a `**Decision:**` line **only the Lead Developer fills in** (CLAUDE.md §7). Answered ⇒ gets a D-ID in `.claude/memory/decisions.md` and becomes final. All four must be closed **before the M2 detail-fill** (see the plan README's trigger) — none block M1.

---

## Q-M2-1 — Teacher design passes: when, and which screens?

The teacher browser + mobile design passes were parked at M1 design close. The Phase-201 scaffold and every teacher-portal phase after it design-match against these canvases, so they must exist before Phase-201 starts. Proposed: run them in Claude Design on the Komposisyon palette (D-032) during M1's later phases — pass 1 = teacher mobile core (dashboard, course, lesson authoring, quiz builder, invitations, scores ≈ 12 screens), pass 2 = teacher browser. Boss visibility: same artifact/canvas logging convention as task 006.

**Decision:** Run them NOW (Lead Developer, 2026-09-13) → **D-033**. Pass 4 = teacher mobile, pass 5 = teacher browser; briefs [claude-design-brief-4.md](../../2026-09-13-006-Brand-Colors/claude-design-brief-4.md) / [claude-design-brief-5.md](../../2026-09-13-006-Brand-Colors/claude-design-brief-5.md) in the design home (task 006 folder).

---

## Q-M2-2 — Pre-M3 access: is everything free during M2?

The entitlements module (tier→feature mapping, D-022/D-023 enforcement) is M3 scope. Until it exists, M2 builds have no paywall: any teacher can create unlimited courses, any student can join. Confirm this is acceptable for M2 testing/demo builds (recommended: yes — gate nothing in M2, and make the M3 entitlements module the single place enforcement appears; matches the CLAUDE.md money rule of one canonical module, no scattered checks to retrofit).

**Decision:**

---

## Q-M2-3 — Invitation email delivery: which sender?

Phase-207 invitations are keyed by student email (brief). If the invited email has no account yet, an actual email must be sent (invite link + app download). That needs a sending provider and a from-domain — a client-facing cost/branding choice: SMTP on client hosting, a transactional service (Resend/Mailgun/SES), or **M2-fallback = in-app only** (invitations reach existing accounts via M1 notifications; unmatched emails sit `pending` until that student registers — zero external dependency, weaker growth loop). Needs the boss/client, since the from-address is their brand.

**Decision:**

---

## Q-M2-4 — Lesson PDF (D-009): client print stylesheet or server-side render?

The schema calls PDF "a rendering pipeline, not a schema feature". Two viable shapes: (a) **client-side** — print stylesheet + browser print-to-PDF; zero backend deps, but layout fidelity varies by device/browser and mobile UX is clunky; (b) **server-side** — Laravel renders Tiptap JSON to PDF (dompdf, or Browsershot+headless-Chrome for fidelity); one canonical output everywhere, but a backend dependency and server load. Recommended: (a) for M2 with the print stylesheet built as a real deliverable, (b) as an M2.x upgrade if fidelity complaints arrive. Lead Developer's call — no client input needed.

**Decision:**
