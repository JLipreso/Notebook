# Phase 004 complete — Auth: Firebase → Sanctum

_2026-09-13 · branch `Workstation-Laptop` · task 2026-09-13-005._

A user signs up / signs in with Firebase (email+password or Google) in either app, the backend verifies the Firebase ID token and issues a Sanctum bearer, and every subsequent call carries it. D-015 implemented end to end.

## What shipped

**Backend**

- `kreait/laravel-firebase` ^6.2, configured from `FIREBASE_CREDENTIALS`.
- `AuthController` (`app/Http/Controllers/Api/AuthController.php`) — four endpoints under the `AUTH (2026-09-13-005 Phase 004)` banner:

| Endpoint | Behaviour |
|---|---|
| `POST /api/auth/firebase` (public, `throttle:auth`) | Verify token → find by `firebase_uid`, else link by `email`, else create (validating the brief's profile fields) → issue bearer with the role as an ability |
| `GET /api/user` (auth) | The caller, contract shape |
| `POST /api/auth/logout` (auth) | Revoke **only** the calling device's token |
| `POST /api/auth/device` (auth) | Upsert `user_devices` on `UNIQUE(user_id, device_identifier)` |

**Frontend**

- `packages/services/firebase.ts` — the ONE module importing the Firebase SDK. Apps never touch `firebase` directly (verified by grep).
- Auth store in both apps: `user`, `token`, `ready`, `signUp`/`signIn`/`signInWithGoogle`/`signOut`/`restore`, bearer persisted to `localStorage` behind try/catch.
- `packages/ui/auth/` — `FormField`, `FormErrors`, `GoogleButton`, and `useAuthForm()` (submit/loading/error extraction). Both form factors compose these; neither re-implements them.
- Sign-in and sign-up views per app — browser as a centred card, mobile as a full-screen portrait flow.
- Route guard: **private by default**, routes opt out with `meta.public`.

## Three things worth knowing

### 1. The `FIREBASE_CREDENTIALS` path format is fragile on Windows

kreait detects an absolute Windows path by checking for `:\` (`FirebaseProjectManager.php:50`). Consequences, both hit during this phase:

- `C:/credentials/...` (forward slashes) fails that check → treated as **relative to the app root** → `SplFileObject` error with the backend path prepended.
- `"C:\credentials\..."` (quoted) → dotenv reads `\c` as an escape sequence → *"The environment file is invalid!"*

**The only form that works is unquoted backslashes:** `FIREBASE_CREDENTIALS=C:\credentials\notebook-firebase.json`. Documented in `.env.example` above the var.

### 2. Framework-thrown errors did not match the frozen envelope

`ValidationException` (422) and `AuthenticationException` (401) returned Laravel's default `{message, errors}` — **no `success` key**. The shared `ApiResponse<T>` declares `success` on every response, so any client checking `response.success` would read `undefined` on exactly the paths auth exercises most.

Fixed in `bootstrap/app.php` with two `$exceptions->render()` handlers scoped to `api/*`. This is a **whole-API fix**, not an auth one — every later phase's validation now conforms for free. Regression-tested in `AuthTest::test_framework_errors_use_the_frozen_envelope`.

### 3. `tsconfig.app.json` did not include package `.vue` files

`packages/ui/**/*.ts` was listed; `.vue` was not. The first shared Vue component broke `pnpm build:all` with TS6307 — and only on build, since `pnpm dev` skips strict checks. Exactly the CLAUDE.md §4 warning, hit for real. Added `packages/ui/**/*.vue` to both apps. The vite aliases already resolved `@notebook/ui`, so no vite change was needed.

## Security decisions

- **Role never comes from the client after creation.** Validated `in:student,teacher` at sign-up (admin is seeded only), and on every later exchange the stored role wins — tested with a teacher account sending `role: student`.
- **Disabled accounts are refused** with 403 before a token is minted.
- **Logout revokes one token**, so signing out on the phone does not sign out the laptop.
- **Token abilities carry the role**, so a stale token cannot outlive a role change.
- `firebase_uid` and `password` stay `$hidden` — neither reaches a client.

## Verified

- **29 backend tests pass** (80 assertions). Auth covers: first exchange creates, second reuses, existing email links the uid, bad token → 401 envelope, missing profile fields → 422, `role: admin` rejected, client role cannot overwrite a stored role, disabled → 403, `/api/user` needs a bearer, logout revokes only the current token, device upsert idempotent, role lands in token abilities, framework errors use the envelope.
- **Live check against the real Firebase project** (`notebook-app-ph`): the SDK resolves with the service-account credential and rejects a garbage token with `FailedToVerifyToken`; `POST /api/auth/firebase` returns the 401 envelope; validation returns the 422 envelope; unauthenticated `/api/user` returns the 401 envelope.
- **Mock mode still works with no backend** — `exchangeFirebaseToken` returns the fixture user and never calls Firebase, so Phases 005–010 stay backend-optional.
- Root `pnpm typecheck` (7 projects) + `pnpm build:all` green; `refresh-docs` run (6 routes).
- No app imports `firebase` directly.

## Not done — needs a real browser

**The Google popup flow and a genuine end-to-end sign-up have not been exercised against live Firebase.** Both need a browser with a real user completing the flow; no headless driver is installed and adding one is outside this phase. The code paths are wired and typecheck, but *"Sign-up + sign-in + Google flow work in browser AND mobile apps (live mode)"* is **unverified by me** — it needs a manual pass:

1. Set `VITE_USE_MOCK=false` in `apps/student/browser/.env`
2. `pnpm dev:backend` and `pnpm dev:student:browser`
3. Sign up at `/sign-up`, confirm the user row lands with the right role, then sign out and back in
4. Repeat for Google

Firebase Console needs **Email/Password and Google enabled** under Authentication → Sign-in method. If sign-up returns `auth/operation-not-allowed`, that is the cause.

Similarly the 401 silent re-exchange is wired (`setUnauthorizedHandler` → `getIdToken(true)` → retry once) and unit-shaped, but has not been watched end to end against a manually expired token.

## Notes for Phase 005

The profile screen needs `AddressSelect.vue` in `packages/ui/address/` and the `AddressController` endpoints. **Phase 005 is still blocked on the PSGC data file** (see the Phase-002 note) — the dropdown chain works in mock mode against the trimmed fixture, but live mode has no address rows until the PSA CSV is committed.
