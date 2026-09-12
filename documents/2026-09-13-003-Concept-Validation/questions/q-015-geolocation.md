# Q-015 — Student geolocation

The brief collects device location on every app open (latest only, no history). No purpose is stated. Most students are minors; under the Data Privacy Act (RA 10173) location is personal data requiring a declared purpose, proportionality, and consent — and app-store reviews (Google Play / App Store) also require a stated purpose for location permission.

**Option A — Drop it from MVP (recommended).** The PSGC address dropdowns already give region-level demographics for analytics; no runtime location permission needed at all.
- ✅ Zero privacy exposure; one less permission prompt at first launch (permission prompts measurably hurt onboarding conversion).
- ❌ No device-location signal, if a future feature needs one.

**Option B — Coarse + consented.** IP-based or city-level location, captured once at signup with an explicit consent checkbox and stated purpose (e.g. regional usage analytics), overwrite-only as the brief says.
- ✅ Keeps the analytics value with a defensible legal posture.
- ❌ Still needs a privacy-policy section, consent copy, and a parental-consent stance for minors.

**Option C — As written (GPS on every open).**
- ❌ Not recommended without a concrete feature that needs it: highest legal exposure, permission-prompt friction, and store-review scrutiny for no stated benefit.

**Decision:** Option A — geolocation dropped from MVP; PSGC address dropdowns cover regional demographics. Locked 2026-09-13 as **D-020** (Lead Developer via option prompt).
