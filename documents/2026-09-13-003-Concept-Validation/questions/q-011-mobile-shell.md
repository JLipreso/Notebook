# Q-011 — Mobile/tablet shell

The brief targets phone + tablet for students, three device types for teachers. D-001 locks Vue for the web. What wraps it on devices?

**Option A — Capacitor wrapping the same Vue apps (recommended).** `@capacitor-community/sqlite` for the local DB, Capacitor plugins for camera/filesystem/push.
- ✅ One codebase for web + Android + iOS; D-001 stack unchanged; MIIT precedent (its mobile apps are Ionic/Capacitor over the same core library); SQLite requirement satisfied natively.
- ❌ WebView performance ceiling — relevant mainly if Q-006 ever goes full ink-canvas (heavy stylus drawing is the one place WebViews hurt).

**Option B — Flutter.**
- ✅ Best canvas/stylus performance short of native; single codebase for devices.
- ❌ Second language (Dart) and a full parallel UI implementation — the Vue apps don't carry over; splits a solo-lead team across two stacks.

**Option C — Native (Kotlin/Swift).**
- ✅ Maximum performance and platform fidelity.
- ❌ Three codebases; not viable at current team size.

**Decision:** Option A — Capacitor wrapping the same Vue apps, `@capacitor-community/sqlite`. Locked 2026-09-13 as **D-017** (Lead Developer via option prompt).
