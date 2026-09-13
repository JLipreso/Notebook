# Phase 002 complete — M1 database: migrations, models, seeders

_2026-09-13 · branch `Workstation-Laptop` · task 2026-09-13-005._

Tables 1–13 of [database-schema.md](../../2026-09-13-004-M1-Foundation/plan/database-schema.md) §9 exist as migrations + Eloquent models, with the two reference seeders. The schema doc was treated as authoritative throughout; the phase file only sequenced the work.

## What shipped

**Migrations** — continued the `0001_01_01_NNNNNN` counter from the scaffold's `000003`:

| File | Tables |
|---|---|
| `000000_create_users_table` (rewritten) | `users`, `password_reset_tokens`, `sessions`, `user_devices` |
| `000004_create_psgc_tables` | `regions`, `provinces`, `cities_municipalities`, `barangays` |
| `000005_create_notebook_types_table` | `notebook_types` |
| `000006_create_notebook_core_tables` | `notebooks`, `notebook_pages`, `page_attachments` |
| `000007_create_file_uploads_table` | `file_uploads` |
| `000008_create_notebook_shares_table` | `notebook_shares` |
| `000009_create_notifications_table` | `notifications` |

**Models** (13) — `User`, `UserDevice`, `Region`, `Province`, `CityMunicipality`, `Barangay`, `NotebookType`, `Notebook`, `NotebookPage`, `PageAttachment`, `FileUpload`, `NotebookShare`, `AppNotification`, plus the shared `Concerns\HasUuidV7` trait.

**Seeders** — `PsgcSeeder`, `NotebookTypeSeeder` (7 launch types), registered in `DatabaseSeeder`.

**Tests** — `tests/Feature/M1SchemaTest.php`, 15 tests. Factories for `User`, `NotebookType`, `Notebook`, `NotebookPage`, `PageAttachment`, `NotebookShare`.

## Deviations from the phase file — read these

1. **Sanctum's `tokenable_id` had to change, and the phase file does not mention it.**
   `personal_access_tokens` was scaffolded with `$table->morphs('tokenable')`, which is `BIGINT UNSIGNED`. The moment `users.id` became `CHAR(36)`, that column could no longer store a token owner — **Phase 004 auth would have failed at the first `createToken()` on MySQL**. Changed to `uuidMorphs`. The schema doc lists framework tables as "already in the scaffold" and so never flagged it. **Any future table pointing at a UUID PK needs the same treatment.**

2. **The `notebook_shares` CHECK constraint is engine-split.**
   Schema §5 requires "exactly one of (`shared_with_user_id`, `share_token`)". sqlite has no `ALTER TABLE ADD CONSTRAINT`, so on sqlite the table is dropped and recreated with the CHECK inline; MySQL takes the normal ALTER path. Both enforce it — there is a test proving rejection. The sqlite branch hardcodes the column list, so **if that table's columns change, update both branches.**

3. **`users.barangay_code` and `notebooks.cover_upload_id` FKs are MySQL-only.**
   Both point at tables created in a later migration, so the constraint is added by `Schema::table()` afterwards — which sqlite cannot do. Validation at the request layer (`exists:barangays,code`) is the real guard in both engines; Phase 005 already specifies it.

4. **`DatabaseSeeder` no longer creates a test user.**
   The stock seeder made `Test User <test@example.com>` with a known password, and it referenced `name`, a column that no longer exists. Rather than port it, it was removed: `migrate:fresh --seed` runs against real databases and a known-credential account is an auth hole. The demo account is Phase 012's job, in its own seeder.

5. **`AppNotification`, not `Notification`** — collides with `Illuminate\Notifications\Notification`. Same collision the TS contract dodges (Phase 003 names it `AppNotification` too). `$table` is set explicitly to `notifications`.

## ⚠ Open — PSGC data file is NOT committed

`PsgcSeeder` is written, tested, and idempotent, but **the PSA source file does not exist yet**. Phase-002 §3 step 1 is a manual download (psa.gov.ph → PSGC quarterly XLSX → save as CSV). That cannot be faked: seeding invented address data would put fictional barangays into a government-reference table.

Current behaviour with no file: prints `PSGC: no database/seeders/data/psgc-*.csv found — SKIPPING.` and seeds nothing. Everything else seeds normally. See [database/seeders/data/README.md](../../../backend/database/seeders/data/README.md) for the acquisition steps.

**Phase-002's acceptance checklist item "Barangay count plausible (~42k)" is therefore NOT met.** The parser was verified against a hand-built 10-row fixture shaped like the real PSA export (deleted before commit) — it produced 2 regions, 1 province, 3 cities, 4 barangays, resolved the full chain, and correctly left NCR's Manila with `province_code = NULL`. Re-running the seeder twice left counts unchanged.

**Blocks Phase 005** (the address dropdown chain has nothing to serve). Does not block Phases 003 or 004.

## Verified

- `migrate:fresh --seed` clean **twice in a row**
- `php artisan test` — **16 passed** (26 assertions), including: UUIDv7 version nibble, client-minted id stored verbatim, soft-delete + restore per RW table, `client_updated_at` present on all three RW tables, Tiptap JSON round-trip, share CHECK rejects an empty target, `owned()` scope isolates users
- 7 notebook types seeded; `writing` → `penmanship_blue_red` + 2 signature footers, `timesheet` → `default_blocks: ["table"]`, `drawing` → `requires_ink: true`
- `tokenable_id` is `varchar` after the fix; `users.id` is `varchar`
- Root `pnpm typecheck` + `pnpm build:all` green
- `node scripts/refresh-docs.mjs` run in the same commit

## Notes for Phase 003

`notebook_types.page_template` is now a **live contract**. Its shape is documented in the header of `NotebookTypeSeeder` and must be mirrored exactly by the `PageTemplate` interface in `@notebook/types`, which Phase 007's `PaperPage.vue` renders from. Keys: `ruling` · `line_spacing_mm` · `margin` · `grid` · `header_fields[]` · `footer_fields[]` · `default_blocks[]`. Colors are brand token **names** (`margin`), never hex — a hex here would end up in a view and break CLAUDE.md §4.

Write the TS interfaces with the migrations open alongside: the phase file asks for field-for-field parity, and these migrations are now the truth.
