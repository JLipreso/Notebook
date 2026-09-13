# Phase 004 — Auth: Firebase → Sanctum

**Goal:** a user signs up / signs in with Firebase (email+password, Google) in either app, the backend verifies the Firebase ID token and issues a Sanctum bearer, and every subsequent API call carries it. Implements D-015 end-to-end.

**Prereq:** Phase-003; Firebase credentials from Phase-001 §6 in place. **Decisions:** D-014, D-015, D-030 (trial-at-registration semantics recorded now, billing lands M3).

## 1. Backend

### Package
`composer require kreait/laravel-firebase` — configure via `FIREBASE_CREDENTIALS` env (absolute path OUTSIDE the repo). Add the var to `backend/.env.example` (placeholder comment, no value) ⇒ **`refresh-docs` in the same commit**.

### `AuthController` (`app/Http/Controllers/Api/AuthController.php`)
Flat controller extending `ApiController`, docblocks per §5. Routes in `routes/api.php` under a banner `// ==== AUTH (2026-09-13-005 Phase 004) ====`, one `Route::` per line, literal routes before any wildcard:

| Endpoint | Behavior |
|---|---|
| `POST /api/auth/firebase` (public, `throttle:auth`) | Body: `id_token`, and on FIRST sign-up the profile fields (`first_name`, `last_name`, `middle_name?`, `birthday`, `mobile_number`, `role` limited to `student`/`teacher`). Verify token via kreait ⇒ `firebase_uid` + verified `email`. Find user by `firebase_uid`, else by `email` (link uid), else create (validating profile fields inline). Issue `$user->createToken('device', [$user->role])` → return `{ token, user }` in the envelope |
| `GET /api/user` (auth:sanctum) | already scaffolded — extend to return the full `User` contract shape |
| `POST /api/auth/logout` (auth:sanctum) | revoke current token |
| `POST /api/auth/device` (auth:sanctum) | upsert `user_devices` row (platform, device_identifier, fcm_token?) — UNIQUE(user_id, device_identifier) |

Notes: role NEVER comes from the client after creation. Sanctum stays stateless bearer (`SANCTUM_STATEFUL_DOMAINS` empty). Store `birthday` as date; require all brief-required fields.

## 2. Frontend (both form factors — remember the D-027 guardrail)

- **Firebase JS SDK** (`firebase` package) initialized from `VITE_FIREBASE_*` — put the tiny init + `signIn`/`signUp`/`signInWithGoogle`/`getIdToken` wrapper in `packages/services/firebase.ts` (shared; apps never import firebase directly).
- `auth.service.ts` live path: firebase sign-in → `exchangeFirebaseToken(idToken)` → returns `{token, user}`.
- **Auth store** (exists in both apps): extend with `user: User | null`, `signIn`, `signUp`, `signOut`; persist the bearer (localStorage web; the native shell reuses it — Preferences plugin can come with Phase-011 if needed). On app boot: token present ⇒ `me()`, 401 ⇒ silent re-exchange via firebase `getIdToken(true)`, still failing ⇒ signed-out state. Wire the same 401 handling into `packages/services/http.ts` interceptor.
- **Screens** — shared pieces (form fields, validation display, Google button) in `packages/ui/auth/`; each app composes its own layout (browser: centered card; mobile: full-screen portrait flow): Sign-in, Sign-up (all required profile fields incl. role choice student/teacher), route guard redirecting unauthenticated → sign-in.
- Demo affordances (prefilled test creds etc.) ONLY behind `VITE_DEMO_MODE` (CLAUDE.md §4).

## 3. Verification

- Backend feature tests: exchange with a mocked kreait verifier (bind a fake in the container) — first-time creates user, second-time links, bad token → 401 envelope; logout revokes.
- Manual: sign up in browser app (live mode: `VITE_USE_MOCK=false`), see `/api/user` succeed; kill backend token manually and watch the silent re-exchange path.
- Mock mode still fully works (mock sign-in returns fixture user) — UI phases stay backend-optional.

## Acceptance checklist

- [ ] Sign-up + sign-in + Google flow work in browser AND mobile apps (live mode)
- [ ] Bearer attached to every request; 401 → silent re-exchange → retry works
- [ ] Auth tests green; `throttle:auth` on the exchange endpoint
- [ ] `.env.example` updated ⇒ refresh-docs ran in that commit; no credential committed
- [ ] typecheck + build:all green · PR (`M1 Phase 004 — auth`) · completion note
