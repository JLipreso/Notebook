# Phase 011 — Android packaging

**Goal:** the mobile build runs as a real Android app — portrait-locked, offline-capable, installable on a device. iOS is explicitly NOT this phase (D-026: after ALL features are complete on Android); Electron likewise later.

**Prereq:** Phases 008–010 (a feature-complete-enough mobile build worth packaging); Android Studio + SDK installed. **Decisions:** D-017, D-026, D-027, D-028, **D-030 (appId `com.notebook.student` — locked, permanent)**.

## 1. Generate the Android project (one-time)

```bash
pnpm build:student:mobile                 # native wraps the BUILT app
cd apps/student/native
pnpm assemble                             # assemble-www.mjs → www/ from ../mobile/dist
pnpm add @capacitor/android               # platform package lives in native/
pnpm exec cap add android                 # generates android/ — COMMITTED (plan §1)
```

`capacitor.config.ts` already carries `appId: 'com.notebook.student'` — verify it BEFORE `cap add`; the generated package structure is permanent (`com.notebook.teacher` is reserved for the teacher app, don't touch it here).

## 2. Configure the shell

- **Portrait lock (D-027):** in `android/app/src/main/AndroidManifest.xml` set `android:screenOrientation="portrait"` on the main activity. That's the hard lock; the `@capacitor/screen-orientation` plugin is only needed if we ever unlock per-screen — skip it for M1.
- **Plugins** (add in `native/`, then `pnpm exec cap sync`): `@capacitor-community/sqlite` (Phase-009's adapter expects it), `@capacitor/network` + `@capacitor/filesystem` (sync engine + attachment uploader), `@capacitor/app` (foreground sync trigger). `@capacitor/push-notifications` (FCM) is OPTIONAL for M1 — in-app notifications already work; add push only if the Lead Developer asks (needs `google-services.json` from Firebase — that file is environment config: gitignore it, document it in `.env.example`-style comment in `native/README` note).
- **API base URL:** a device can't reach `127.0.0.1`. For device testing point `VITE_API_URL` at your machine's LAN IP (`http://<lan-ip>:8000/api`) in `apps/student/mobile/.env`, rebuild, re-assemble; `php artisan serve --host=0.0.0.0`. Android 9+ blocks cleartext HTTP: for local testing add `android:usesCleartextTraffic="true"` to the manifest **application tag under a debug-only manifest** (`android/app/src/debug/AndroidManifest.xml`) — NEVER in main (production will be HTTPS when Q-004 closes).

## 3. The loop you'll live in

```bash
pnpm build:student:mobile && cd apps/student/native && pnpm assemble && pnpm exec cap sync android && pnpm exec cap open android   # → Android Studio → Run on device/emulator
```

## 4. Device verification (the real Phase-009 test)

1. Sign in on the device (live API over LAN). Create a notebook, type a page.
2. **Airplane mode ON**: create another notebook, edit pages, attach an image (goes `pending`) — everything must work with zero errors.
3. Airplane mode OFF, foreground the app: sync runs — verify on the backend (`php artisan tinker`: rows arrived, ids match the client-minted ones, attachment uploaded and row patched).
4. Edit the SAME page on web while the device is offline, then let the device push an older edit — LWW: server copy wins, device shows the winning content after sync.
5. Kill and relaunch offline — data persists (SQLite, not memory).

## Acceptance checklist

- [ ] `android/` project committed, appId `com.notebook.student`, portrait-locked (rotate the device — UI must not rotate)
- [ ] Full offline round-trip (steps 1–5) passes on a physical device
- [ ] Cleartext only in the debug manifest; no `google-services.json` or keystore committed
- [ ] typecheck + build:all green · PR (`M1 Phase 011 — android shell`) · completion note (device/OS tested on, gotchas → 001-Learnings.md — Windows+Android Studio quirks are exactly what that ledger is for)
