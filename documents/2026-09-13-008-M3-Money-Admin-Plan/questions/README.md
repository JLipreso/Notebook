# M3 open questions

Each ends with a `**Decision:**` line **only the Lead Developer fills in** (CLAUDE.md §7); answered ⇒ D-ID in `decisions.md`, final. Q-M3-2 and Q-M3-3 need the boss/client and have the longest latency — **send them up now**, even though M3 implementation is months out.

---

## Q-M3-1 — Admin app: browser-only?

D-025 locked platforms for student and teacher; admin was never specified. Staff verifying payments work at a desk; a phone-form admin app is triple the surface for no stated need. **Recommended: `apps/admin/browser` only** — D-027's structure still applies (thin shell, domain components in `@notebook/ui`), and another form factor can be added later without rework if the client asks. Lead Developer's call.

**Decision:**

---

## Q-M3-2 — Google Play payments policy vs GCash — ⚠ close FIRST, can reshape Phase-306/310

Google Play's Payments policy requires **Play Billing for in-app purchases of digital goods/subscriptions** — an Android app that takes GCash payments for its own subscription inside the app risks rejection or takedown, and Play Billing takes 15–30% of ₱69–₱169. Options:
- **(a) Web-purchase flow (recommended):** the Android app never sells — subscribe/pay/verify happens on the browser app (it exists from M1); the Android app only reflects the entitlement. Common, compliant, keeps 100% of revenue; costs one redirect in UX.
- **(b) Play Billing in-app** alongside/instead of GCash: native UX, but fees eat the margin and the manual-GCash workflow the brief specifies becomes web-only anyway.
- **(c) GCash in-app regardless:** matches the brief most literally; carries a real policy risk on the client's store listing.

This is a **client revenue + risk decision** — needs the boss/client. The answer decides where Phase-306's UI lives and what Phase-310's compliance pass checks. (iOS later has the same issue, stricter.)

**Decision:**

---

## Q-M3-3 — GCash operational details

Phase-306/307 need from the client: (1) **whose GCash account/QR** receives payments (the client's business account — we never hold these credentials, the QR image/number is config, not code); (2) **one static QR + reference-number matching** (assumed — matches the brief's manual flow) or amount-specific QRs; (3) **who the verifying staff are** — how many admin accounts at launch, and whether `follow_up` contact with payers happens inside the app (notifications) or off-app (their GCash/phone). Pure client input; blocks nothing until detail-fill.

**Decision:**

---

## Q-M3-4 — `past_due` grace period length

Schema §7: a submitted-but-unverified payment holds the subscriber in `past_due` (access kept) instead of dropping them to `free_floor`, protecting payers from manual-verification delays. How long may a payment sit unverified before access drops anyway — 3 days? 7? (Recommended: **7 days**, generous for a manual queue; it's one scheduler constant, changeable anytime.) Lead Developer's call, boss FYI.

**Decision:**

---

## Q-M3-5 — USD pricing: defer to the PayMongo/Maya milestone?

The brief lists ₱ + USD, and `plan_prices` supports both — but GCash is PHP-only, so M3 has no way to *collect* USD. **Recommended: seed PHP prices only in M3**; USD rows land with the PayMongo/Maya integration (deferred list), where card/international payment makes USD real. Schema unchanged either way.

**Decision:**
