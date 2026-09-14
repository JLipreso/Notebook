# Phase 009 complete — Offline sync engine

_2026-09-14 · branch `Workstation-Laptop` · task 2026-09-13-005._

Notebooks, pages and attachment rows synchronise through a server implementing [database-schema.md §2](../../2026-09-13-004-M1-Foundation/plan/database-schema.md) and a client engine in `packages/sync` with real tests. **No §2 deviations.**

## What shipped

**Backend — `SyncController`** (2 routes, `auth:sanctum`):

| Endpoint | Contract |
|---|---|
| `GET /api/sync/{table}?since&limit` | rows `updated_at > since` **including tombstones**, ownership-scoped, cursor-paged |
| `POST /api/sync/{table}` | LWW on `client_updated_at`; returns `{accepted, rejected}` with the winning server copy |

`config/notebook.php` gained `sync_tables` (rw/ro), `sync_pull_limit` (500), `sync_push_limit` (100). `{table}` is checked against that fixed map and **never interpolated into a query**.

**Client — `packages/sync`:** `pull.ts` · `outbox.ts` · `run.ts` (orchestrator + single-flight lock) · `attachment-uploader.ts` · `storage/sqlite.adapter.ts` · an extended `StorageAdapter` contract · 19 vitest tests.

**Datasource routing:** `packages/services/store.ts` — the native shell registers an adapter at boot; web leaves it null and stays API-direct.

## The acceptance criterion, verified literally

> *services route by `hasNativeStorage()` with zero view changes*

**Zero files under `apps/` or `packages/ui/` mention `resolveStore`, `runSync` or `StorageAdapter`.** Verified by grep. Phases 006–008 shipped before this file existed and none of them changed a line when it landed — that is Phase 003's discipline paying off exactly as designed.

## Design decisions

1. **`putClean()` added to the adapter contract.** A row arriving from the server must NOT look like a local edit, or the next push sends it straight back in an infinite loop. `put()` marks dirty (local edit); `putClean()` does not (pull, and LWW convergence).

2. **Push order is re-stated in `outbox.ts`, not inherited from the types const.** Parent-before-child matters: a page whose notebook the server has never seen is rejected as `forbidden`. Relying on `SYNC_RW_TABLES` ordering would mean a future reorder of a types file silently breaks sync.

3. **Equal `client_updated_at` is NOT a conflict.** A retried push of the same edit must be idempotent. Only *strictly older* is rejected — otherwise a flaky connection turns every retry into a spurious conflict.

4. **`existsElsewhere()` keeps "missing" opaque.** Pushing at an id owned by someone else returns `forbidden` identically to an id that does not exist, so push cannot be used as an existence oracle.

5. **The SQLite driver and the file reader are both INJECTED, not imported.** `@capacitor-community/sqlite` and `@capacitor/filesystem` only resolve in the native shell; importing either would break the web build and the tests. Phase 011 passes the real ones in.

6. **Rows are stored as JSON blobs plus the few columns the engine queries** (`id`, `updated_at`, `deleted_at`, `_dirty`). The device never runs domain queries over these — the app reads through the services — so a column-per-field mirror would be a second schema to keep in sync with Laravel's, for no gain.

7. **`resetSyncLock()` exists for tests only**, and says so. A module-level lock survives between test cases; without a reset, one test parking a run wedges every later one.

## Three bugs my own tests found

Worth recording, because each would have shipped as a green suite proving nothing:

1. **Two tests asserted ordering and error handling with an EMPTY outbox** — so `push()` was never called and both assertions passed vacuously. Caught only because the ordering one failed for an unrelated reason. Fixed by seeding a dirty row; a passing version of either test would have been worthless.

2. **The single-flight test raced its own deferred.** `runSync` does async work before reaching `push()`, so a `release` captured *inside* the promise body was still null when the assertions ran. Fixed by resolving the deferred at creation time.

3. **The module-level lock leaked between test cases**, wedging four later tests at 5s timeouts each. That is a real design gap, not just a test problem — hence `resetSyncLock()`.

## Verified

- **115 backend tests** (341 assertions); 24 new, one per §2 clause: unknown table rejected, RO tables refuse pushes, pull excludes other users and **includes tombstones**, cursor filtering, null cursor when caught up, exact-boundary paging with no overlap, child scoping through the notebook, client-minted ids, `user_id` ignored, foreign rows untouchable, **stale rejected with the server copy returned**, newer accepted, equal-clock idempotent, soft delete propagated, tombstone restorable, client `updated_at` ignored, content sanitized + `search_text` derived on push, foreign-parent children rejected, batch cap, per-row reporting.
- **19 engine tests** (vitest, `MemoryAdapter`): clean writes on pull, cursor loop and persistence, tombstones applied, **parent-before-child push order**, dirty clearing, **LWW convergence on the server copy**, forbidden rows not retried forever, batching, deletes as ordinary rows, the attachment `pending → uploaded` path with failure and requeue, push-before-pull, the single-flight lock, error reporting, progress events, and the web path skipping attachments.
- Root `pnpm typecheck` (7 projects) + `pnpm build:all` green; `refresh-docs` run (35 routes).

## Not done — Phase 011's job by design

**No device test.** The phase file says so explicitly: the real offline round trip needs the Android shell. What is verified here is the engine against `MemoryAdapter` and the server against real HTTP; what is *not* is SQLite on a real device, `@capacitor/network` triggering, and airplane-mode behaviour. `SqliteAdapter` is written and typechecks but **has never executed** — no driver exists outside the native shell.

Two carry-overs unchanged: the `⋯` card menu is still a `prompt()`, and the Phase 007/008 visual passes are still open.

## Notes for Phase 011

The shell's boot sequence is now a short list: open SQLite → `new SqliteAdapter(driver)` → `registerLocalStore(adapter)` → wire `runSync()` to app-foreground and `@capacitor/network` regain, passing a `readFile` backed by `@capacitor/filesystem`. Nothing else in the app changes.

The five-step airplane-mode test in Phase-011 §4 is the real acceptance for this phase.
