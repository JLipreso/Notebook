# Phase 003 complete — Type contract & services layer

_2026-09-13 · branch `Workstation-Laptop` · task 2026-09-13-005._

`@notebook/types` mirrors the M1 tables field-for-field, `@notebook/services` exposes one service per domain routed through the single datasource switch, and the mock db has fixtures — so every UI phase from here is buildable with the backend stopped.

## What shipped

**`packages/types/`** — eight domain files, all exported from `index.ts`:

| File | Contents |
|---|---|
| `user.ts` | `User`, `UserRole`, `UserStatus`, `UserDevice`, `DevicePlatform` |
| `address.ts` | `Region`, `Province`, `CityMunicipality`, `Barangay`, `AddressChain` |
| `notebook.ts` | `Notebook`, `NotebookType`, **`PageTemplate`** + its parts (`RulingPattern`, `PaperField`, `PaperMargin`, `PaperGrid`) |
| `notebook-page.ts` | `NotebookPage`, `PageContent`, `PageNode`, **`ALLOWED_NODES`**, `ALLOWED_MARKS`, `ALLOWED_HEADING_LEVELS` |
| `attachment.ts` | `PageAttachment`, `FileUpload`, `AttachmentKind`, `UploadStatus` |
| `share.ts` | `NotebookShare`, `ShareAccess`, `SharedNotebookView` |
| `notification.ts` | `AppNotification`, `NotificationType`, `NotificationData` |
| `sync.ts` | `SyncPullResponse`, `SyncPushRequest/Response`, `SyncRejection`, `SyncRow`, `SYNC_RW_TABLES` |

**`packages/services/`** — nine service modules (the phase asked for eight; `profile.service.ts` was split from auth because Phase 005 owns `GET/PUT /api/profile` and mixing it into auth would have made that phase edit an auth file). Plus `datasource.ts` gaining a `datasource(mock, live)` helper so every method branches identically in one line, and `http.ts` gaining the 401 re-exchange interceptor Phase 004 registers into.

**`packages/services/mock/`** — `fixtures/` (6 files), `db.ts` (mutable in-session copy), envelope helpers.

**`packages/utility/page-search.ts`** — `flattenPageContent()` + `matchesSearch()`, the client mirror of Phase 007's `TiptapText::flatten()`.

## Decisions worth knowing

1. **The notebook-types fixture is GENERATED, not retyped.** `mock/fixtures/notebook-types.ts` was produced from a seeded database, so its `page_template` JSON is byte-identical to `NotebookTypeSeeder`'s. Retyping it by hand would have guaranteed drift between what mock mode renders and what live mode renders — the exact failure Phase 007 would discover late. **If the seeder changes, regenerate this file.**

2. **`profile.service.ts` split out of auth** (see above).

3. **`sync.service.ts` has no mock branch.** Sync only ever runs against a real backend; in mock mode the engine is not running at all. A fake sync would be a second, worse offline implementation.

4. **`share.resolve()` rejects in mock mode.** Resolving a share token needs real server state; returning a fixture would make the Phase 010 public-viewer route look like it works when it cannot.

5. **`AppNotification`, not `Notification`** — the DOM owns that name. Matches the backend model's identical dodge.

## Two-sided contracts now live

Both are commented as such on both sides. Breaking either is silent, not a type error:

- **`ALLOWED_NODES`** (`types/notebook-page.ts`) ↔ `backend/config/notebook.php → allowed_nodes` (Phase 007 creates the PHP half). Adding a node type is always a two-file change in one commit.
- **`flattenPageContent()`** (`utility/page-search.ts`) ↔ `App\Support\TiptapText::flatten()` (Phase 007). Both must produce the same string for the same document or client and server search disagree.

Plus the one from Phase 002, now with its third participant named: **`PageTemplate`** ↔ `NotebookTypeSeeder` ↔ `PaperPage.vue` (Phase 007).

## Verified

- **Field-for-field parity** checked against live `PRAGMA table_info` output for all 13 tables. `firebase_uid` / `password` / `remember_token` are absent from `User` on purpose — all three are `$hidden` on the backend model and never reach a client. PSGC interfaces omit `created_at`/`updated_at` because the Phase 005 endpoints select only `code, name(, class)`.
- **Every mock path executed with the backend stopped** (`curl` confirmed `/api/health` unreachable first): 3 notebooks, 7 types with correct `page_template`, 3 pages, the archived filter, a create that mutates the session db, the NCR no-province branch, and the paginated notifications envelope.
- **The golden rule was proven, not assumed.** The verification harness aliased `axios` to a stub that *throws* on any call, and every mock path passed — so no mock branch can be secretly hitting the live path. Structural greps confirm: nothing under `apps/` imports `mock/` or `axios`, and `datasource.ts` is the only module reading `VITE_USE_MOCK`.
- Root `pnpm typecheck` (8 projects) and `pnpm build:all` green.

## Temporary code to remove in Phase 006

Both apps' `HomeView.vue` now lists fixture notebooks through `notebookService` — the phase file's requested contract proof, and a genuine end-to-end check that a view can reach fixtures without knowing the datasource. Both are marked with a `Phase 003 contract proof` comment. **Phase 006 replaces both with the real library view.**

## Notes for Phase 004

`auth.service.ts` is complete on the exchange side but its live path has never run — no `AuthController` exists yet. Phase 004 builds the backend half plus `packages/services/firebase.ts` (the Firebase SDK wrapper; apps must never import `firebase` directly).

`http.ts` already exposes `setUnauthorizedHandler()` for the silent re-exchange: register it from the auth store, return a fresh bearer, and the interceptor retries the failed request exactly once. Credentials are in place (service account at `C:\credentials\notebook-firebase.json`, web config in both app `.env` files) — nothing is blocked.
