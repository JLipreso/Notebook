# Q-008 — Auth flow

The brief mandates Firebase Authentication ("optional password" implies OTP/social flows). CLAUDE.md §5 mandates Sanctum stateless bearer tokens for the API. How do they meet?

**Option A — Firebase for identity, Sanctum for the API (recommended).** Client signs in with Firebase (email/password, Google, phone OTP); backend verifies the Firebase ID token once (kreait, as MIIT already does for Google login) and issues a Sanctum bearer token; every API call thereafter uses Sanctum.
- ✅ Keeps §5 conventions intact; Firebase handles password reset/OTP/social for free; MIIT-proven exchange; API works even if Firebase is briefly unreachable (existing tokens keep working).
- ❌ Two token lifecycles to reason about (refresh Firebase → re-exchange on 401).

**Option B — Firebase ID tokens on every API call.** Backend middleware verifies the Firebase JWT per request.
- ✅ One token system.
- ❌ Violates §5 (Sanctum) without a decision override; per-request verification latency or JWKS caching complexity; harder token revocation.

**Option C — Sanctum-only, no Firebase.** Laravel handles registration/login/reset directly.
- ✅ Simplest backend.
- ❌ Contradicts the brief; we rebuild OTP/social/reset flows Firebase gives for free.

**Decision:** Option A — Firebase for identity, Sanctum for the API (verify once, exchange for a bearer). Locked 2026-09-13 as **D-015** (Lead Developer via option prompt).
