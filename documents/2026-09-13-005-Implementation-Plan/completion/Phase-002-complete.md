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

## PSGC data — RESOLVED 2026-09-13

The PSA source file arrived after this phase's first commit and is now converted, committed and seeded: `backend/database/seeders/data/psgc-2026Q2.csv` (2.0 MB, from *PSGC 2Q 2026 Publication Datafile*, D-030).

**Seeded: 18 regions · 80 provinces · 1,660 cities/municipalities · 42,010 barangays** — the phase file's "~42k" acceptance item is met. Idempotent across a second run; zero orphans at every tier; both the province path and the NCR no-province path resolve end to end.

A dependency-free converter ships with it: `node scripts/psgc-xlsx-to-csv.mjs <file.xlsx>`. An xlsx is a zip of XML and Node has zlib, so the next developer re-runs it on the next quarterly release with no install. It locates the `PSGC` sheet **by name**, because the PSA reorders and hides sheets between quarters.

**Two source-data traps, both real, both now handled:**

1. **Negros Island Region has a BLANK `Geographic Level` cell** while its three provinces carry theirs — so the region was skipped, its provinces were not, and the FK failed with `FOREIGN KEY constraint failed`. Fixed by falling back to the documented code structure (`RR PP MM BBB`) whenever the level cell is blank. 50 rows in this quarter's file have blank levels, spanning every tier.
2. **Excel drops leading zeros** — `0100000000` exports as `100000000`. The seeder pads back to 10 digits.

**Phase 005 is no longer blocked.**

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
