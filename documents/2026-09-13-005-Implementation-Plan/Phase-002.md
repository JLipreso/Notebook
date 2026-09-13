# Phase 002 — M1 database: migrations, models, seeders

**Goal:** tables 1–13 of [database-schema.md](../2026-09-13-004-M1-Foundation/plan/database-schema.md) exist as migrations + Eloquent models, with the PSGC and notebook_types seeders. The schema doc is **authoritative** — column names, types, indexes, and sync columns come from there, not from this file. Implements the §9 "Phase 4 cut" of that doc.

**Prereq:** Phase-001. **Decisions:** D-013, D-014, D-016, D-019, D-030.

## 1. Migrations — fake-timestamp counter (CLAUDE.md §5)

Continue the existing `0001_01_01_NNNNNN` counter in `backend/database/migrations/` (check the current highest NNNNNN and continue from it). One file per group, in this order (schema doc §9):

| File (`<counter>_create_…`) | Tables |
|---|---|
| `…_users_and_devices_tables` | `users` (extend the scaffold's users migration if present — reconcile, don't duplicate), `user_devices` |
| `…_psgc_tables` | `regions`, `provinces`, `cities_municipalities`, `barangays` — CHAR(10) natural PKs |
| `…_notebook_types_table` | `notebook_types` |
| `…_notebook_core_tables` | `notebooks`, `notebook_pages`, `page_attachments` |
| `…_file_uploads_table` | `file_uploads` |
| `…_notebook_shares_table` | `notebook_shares` |
| `…_notifications_table` | `notifications` |

Non-negotiables from schema §1–§2 (re-read them now):
- `CHAR(36)` UUID PKs everywhere except PSGC; `utf8mb4`; soft deletes exactly where §8's table says.
- **[RW] tables** (`notebooks`, `notebook_pages`, `page_attachments`) get `client_updated_at TIMESTAMP(3)` — the sync engine (Phase-009) depends on it existing NOW.
- Indexes as specified: `(user_id, status)`, `(user_id, updated_at)`, `(notebook_id, position)`, `(notebook_id, updated_at)`, FULLTEXT(`search_text`), `(user_id, read_at, created_at)` on notifications. Note: sqlite ignores FULLTEXT — guard it with `if (DB::getDriverName() === 'mysql')`.
- `notebook_shares` CHECK: exactly one of `shared_with_user_id` / `share_token`.

## 2. Models — `backend/app/Models/`

- All UUID models use a shared `HasUuids` setup returning **UUIDv7** (`Str::uuid7()`); PSGC models set `$incrementing = false`, `$keyType = 'string'`, no uuid trait.
- `SoftDeletes` on `User`, `Notebook`, `NotebookPage`, `PageAttachment` (per §8).
- `$casts`: `page_template` / `content` / `data` → `array`; date/timestamp fields as usual.
- Relationships exactly as the ER diagrams: `User hasMany Notebook`, `Notebook belongsTo NotebookType / hasMany NotebookPage`, `NotebookPage hasMany PageAttachment`, `PageAttachment belongsTo FileUpload`, etc.
- **No API Resource classes** — serialization happens in controllers via `->select()` / `with('rel:id,col')` (CLAUDE.md §5).

## 3. Seeders — `backend/database/seeders/`

### `PsgcSeeder` (D-030: latest snapshot)
1. Download the **latest quarterly PSGC publication** from the PSA site (psa.gov.ph → Philippine Standard Geographic Code). It ships as XLSX.
2. Convert to CSV and commit as `backend/database/seeders/data/psgc-<YYYYQN>.csv` (one file, the quarter in the name). Data files ARE committed — they're public reference data, not credentials.
3. Seeder parses the CSV by the PSGC 10-digit code structure (region/province/city-muni/barangay levels from the code's segments + the level column), inserts in FK order, is idempotent (`upsert` on `code`).
4. Expect ~42k barangays — chunk inserts (1k rows) or seeding will crawl.

### `NotebookTypeSeeder` (D-010, D-012)
Seven launch rows — `composition`, `writing`, `drawing`, `diary`, `scrapbook`, `logbook`, `timesheet` — each with its `page_template` JSON per schema §5: ruling pattern, line spacing, margin config, header/footer fields (Writing: `Date:` header, Teacher's/Parent's Signature footers), default block preset (Timesheet → table). `drawing` sets `requires_ink = true`. Keep the JSON shape identical to what `@notebook/ui`'s `PaperPage` will consume (Phase-007 reads THESE rows — coordinate the shape there before finalizing).

Register both in `DatabaseSeeder`.

## 4. Verification

```bash
cd backend
php artisan migrate:fresh --seed        # clean run, twice (idempotency)
php artisan tinker --execute="echo App\Models\Barangay::count();"   # ~42k
php artisan tinker --execute="echo App\Models\NotebookType::count();" # 7
node ../scripts/refresh-docs.mjs        # routes unchanged, but run it — cheap and habit-forming
```

Write a model factory + one smoke test per RW table (create → soft delete → restore) in `backend/tests/Feature/`.

## Acceptance checklist

- [ ] `migrate:fresh --seed` green twice in a row
- [ ] Barangay count plausible (~42k), 7 notebook types with valid `page_template` JSON
- [ ] RW tables have `client_updated_at` TIMESTAMP(3) + `deleted_at`
- [ ] `php artisan test` green · root `pnpm typecheck` + `build:all` still green
- [ ] PR to `staging` titled `M1 Phase 002 — database (2026-09-13-005)`; completion note written
