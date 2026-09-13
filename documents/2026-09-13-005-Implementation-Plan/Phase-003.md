# Phase 003 — Type contract & services layer

**Goal:** `@notebook/types` mirrors the M1 tables field-for-field, `@notebook/services` exposes one service per M1 domain routed through the datasource switch, and the mock db has fixtures — so all UI phases can be built mock-first and the backend swap is a one-flag change.

**Prereq:** Phase-002 (types mirror the REAL migrated columns — write them with the migrations open in a split pane). **Decisions:** D-005, D-012, D-016.

## 1. `packages/types/` — one file per domain, snake_case, exact

Create and export from `index.ts`:

| File | Interfaces (mirror schema §4–§5 exactly) |
|---|---|
| `user.ts` | `User` (incl. `role: UserRole`), `UserRole = 'student' \| 'teacher' \| 'admin'`, `UserDevice` |
| `address.ts` | `Region`, `Province`, `CityMunicipality`, `Barangay` (all `code`-keyed) |
| `notebook.ts` | `Notebook`, `NotebookType`, `PageTemplate` (the typed shape of `notebook_types.page_template`: ruling, spacing, margin, header/footer fields, default block preset — THE shared contract between the seeder and `PaperPage.vue`) |
| `notebook-page.ts` | `NotebookPage`, `PageContent` (Tiptap document type), **the allowed node/mark whitelist as a const array** (`ALLOWED_NODES`) — Phase-007's editor and the backend sanitizer both import their truth from here conceptually; backend mirrors it in one PHP config |
| `attachment.ts` | `PageAttachment` (incl. `upload_status`), `FileUpload` |
| `share.ts` | `NotebookShare` |
| `notification.ts` | `AppNotification` (not `Notification` — DOM name collision) |
| `sync.ts` | `SyncPullResponse<T>` (rows + `next_cursor`), `SyncPushRequest<T>` / `SyncPushResponse<T>` (accepted ids + rejected rows with winning server copies) — mirrors schema §2 |

Rules: nullable DB column ⇒ `field: T | null`. Timestamps are ISO strings (`string`). No field the migration doesn't have; no field missing. `api.ts` (envelopes) already exists.

## 2. `packages/services/` — one service per domain

Each service: plain functions returning `Promise<ApiResponse<T>>` / `PaginatedResponse<T>`, branching on `useMock` from `datasource.ts` (mock) or calling `http` (live). Views never know which ran.

| File | Surface (M1) |
|---|---|
| `auth.service.ts` | `exchangeFirebaseToken(idToken)`, `me()`, `logout()` — implemented for real in Phase-004; mock returns a fixture user + fake bearer now |
| `address.service.ts` | `regions()`, `provinces(regionCode)`, `citiesMunicipalities(provinceCode\|regionCode)`, `barangays(cityMuniCode)` |
| `notebook.service.ts` | `list(filters)`, `create(payload)`, `update(id, payload)`, `archive(id)`, `remove(id)` (soft) |
| `page.service.ts` | `listByNotebook(notebookId)`, `create`, `update`, `remove` |
| `attachment.service.ts` | `upload(file)` (multipart → `FileUpload`), `attach(pageId, payload)` |
| `share.service.ts` | `create(notebookId, pageId?)`, `revoke(id)`, `resolve(token)` (public) |
| `notification.service.ts` | `list(page)`, `markRead(id)`, `markAllRead()` |
| `sync.service.ts` | `pull(table, cursor)`, `push(table, rows)` — typed by `sync.ts`; used by `@notebook/sync` in Phase-009 |

## 3. `packages/services/mock/`

- `fixtures/`: one JSON/TS module per domain — 1 student user, a trimmed PSGC sample (2 regions → … → ~20 barangays), the 7 notebook types **with page_template JSON identical to the Phase-002 seeder**, 3 notebooks (one archived, school years differing), ~6 pages of real Tiptap JSON, a couple of notifications.
- Route every read through `withLatency()`; mock writes mutate an in-memory copy so the UI feels real within a session.
- Only `datasource.ts` imports `mock/` — keep it that way.

## 4. Verification

- `pnpm typecheck` green (this is the phase where a wrong type SHOULD fail the app builds — that's the contract working).
- Temporary proof in `HomeView` of either app: list notebooks from `notebook.service` under mock mode, render titles. Remove before PR or keep behind the existing scaffold text — reviewer's call; note it in the PR.

## Acceptance checklist

- [ ] Every M1 table has a matching interface; a spot-check of 3 tables shows field-for-field parity with the migrations
- [ ] All eight services exist, typed against `@notebook/types`, mock paths working
- [ ] `VITE_USE_MOCK=true` renders fixture notebooks in both apps with the backend stopped
- [ ] `pnpm typecheck` + `build:all` green · PR to `staging` (`M1 Phase 003 — contract & services`) · completion note
