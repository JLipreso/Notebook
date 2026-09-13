# Q-009 — Primary-key strategy (offline sync forces this now)

A device that creates notebook pages offline in SQLite must mint IDs the server will accept later without collision or rewriting. This is decided in the FIRST migration and cannot be retrofitted (MIIT's mixed signed/unsigned auto-increment PKs are already a lesson in inconsistency pain).

**Option A — UUIDv7 client-generated, everywhere (recommended).** All synced tables use UUIDv7 (time-ordered, index-friendly) as PK; the device mints IDs offline; server inserts as-is.
- ✅ Offline creation just works — no ID mapping, no renumbering; relations created offline stay valid; UUIDv7's time prefix keeps B-tree locality (classic UUIDv4's index-fragmentation problem doesn't apply).
- ❌ 16-byte keys (`CHAR(36)` or `BINARY(16)`); slightly less ergonomic in manual SQL.

**Option B — Server auto-increment + client temp-ID mapping.** Devices use temporary negative/local IDs, server assigns real IDs on sync, client rewrites all references.
- ✅ Small integer keys.
- ❌ ID-rewrite machinery on every synced table is a notorious bug farm; every offline relation must be patched after push.

**Option C — Hybrid.** UUIDv7 only for offline-synced tables (notebooks, pages, attachments); auto-increment for server-only tables (payments, plans, PSGC address data).
- ✅ Best of both; keeps hot server-side tables compact.
- ❌ Two conventions to remember; the offline boundary (Q-010) must be locked first and moving a table across it later is a migration.

**Decision:** Option A — UUIDv7 client-generated, everywhere. Locked 2026-09-13 as **D-013** (Lead Developer via option prompt).
