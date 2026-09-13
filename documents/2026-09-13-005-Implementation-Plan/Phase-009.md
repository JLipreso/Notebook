# Phase 009 — Offline sync engine

**Goal:** notebooks, pages and attachment rows are fully usable offline on the mobile app and synchronize when a connection returns. Implements [database-schema.md §2](../2026-09-13-004-M1-Foundation/plan/database-schema.md) **exactly** — that section is the contract; this file only sequences the work. The hairy code lives in `packages/sync` with real tests (the package exists precisely so this has ONE home).

**Prereq:** Phase-007 (008 recommended first for the attachment path). **Decisions:** D-013, D-016, D-017.

## 1. Backend — `SyncController`

Banner `// ==== SYNC (2026-09-13-005 Phase 009) ====`, `auth:sanctum`. Syncable tables are a fixed map (`config/notebook.php` → `sync_tables`): RW = `notebooks`, `notebook_pages`, `page_attachments`; RO adds `notebook_types` + own `users` row (PSGC ships in the app fixtures/seed — huge and quarterly-static; pull it only if the Lead Developer asks).

| Endpoint | Contract (schema §2) |
|---|---|
| `GET /api/sync/{table}?since=&limit=` | rows `updated_at > since` **including tombstones** (`withTrashed()`), ownership-scoped, ordered by `updated_at`, max 500/pull; returns `{ rows, next_cursor }` (`SyncPullResponse<T>`) |
| `POST /api/sync/{table}` (RW tables only) | body `{ rows: [...] }` (≤100). Per row: enforce `user_id = auth` (reject otherwise), sanitize `content` (Phase-007 sanitizer), then **LWW on `client_updated_at`**: incoming older than stored ⇒ reject, return winning server copy; else upsert (soft-delete when `deleted_at` set), stamp fresh server `updated_at`, maintain `search_text`. Returns `{ accepted: [ids], rejected: [{row, server_copy}] }` |

`{table}` guarded by an `in:` whitelist — NEVER a free string into a query.

## 2. `packages/sync` — fill the skeleton

| File | Work |
|---|---|
| `storage/sqlite.adapter.ts` | implement `StorageAdapter` over `@capacitor-community/sqlite` (dep added HERE). On-device DDL for the RW+RO tables mirroring the Laravel columns (TEXT/INTEGER affinity; JSON as TEXT) + a `_dirty` flag column and a `_cursors` table. `init()` creates/migrates by a local `PRAGMA user_version` |
| `outbox.ts` | collect dirty rows per table, order parent-before-child (notebooks → pages → attachments — FK order matters), push via `sync.service`, clear `_dirty` on accept, **apply `server_copy` on reject** (LWW loss = server wins locally too) |
| `pull.ts` | per-table cursor loop until `rows < limit`; upsert/soft-delete locally; store cursor |
| `push.ts` | batching + retry with backoff; single-flight (never two concurrent sync runs — a module-level lock) |
| `attachment-uploader.ts` | rows with `upload_status='pending'` + `local_ref`: read file (`@capacitor/filesystem`), `POST /api/files`, patch row with `file_upload_id`, status `uploaded`; failures → `failed` + retry on next run |
| `index.ts` | `runSync()` orchestrator: push → pull → attachments; emits progress events; triggered on app foreground, network-regain (`@capacitor/network`), and a manual button |

**Datasource routing (the golden-rule extension):** on platforms where `hasNativeStorage()` is true, `notebook.service`/`page.service`/`attachment.service` read/write the LOCAL adapter (marking rows dirty) and `runSync()` reconciles; on web they stay API-direct. Implement this switch INSIDE the services (one `resolveStore()` helper) — views and composables change ZERO lines. This is the payoff of Phase-003's discipline.

## 3. Tests (the one place this plan demands real coverage)

- `packages/sync` unit tests (vitest — add to the package) against `MemoryAdapter`: outbox ordering, cursor paging, LWW-reject applies server copy, tombstone propagation both directions, single-flight lock.
- Backend feature tests: pull excludes other users, includes tombstones, cursor pagination exact-boundary; push rejects stale `client_updated_at`, rejects foreign `user_id`, sanitizes content.
- Manual device test happens in Phase-011 (needs the Android shell); until then verify web-side no-regression: browser app stays API-direct and everything from Phases 006–008 still works.

## Acceptance checklist

- [ ] `SyncController` implements §2 verbatim (tombstones, LWW, ownership) — tests prove each clause
- [ ] `packages/sync` tests green; services route by `hasNativeStorage()` with zero view changes
- [ ] Browser app behavior unchanged end-to-end
- [ ] typecheck + build:all green · refresh-docs ran · PR (`M1 Phase 009 — offline sync`) · completion note (include any §2 deviation — there should be none without a decision)
