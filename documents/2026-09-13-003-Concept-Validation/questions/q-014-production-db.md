# Q-014 — Production database engine

Already flagged open in current-status. Dev DB is SQLite (scaffolded); on-device is SQLite (locked). The server engine is undecided.

**Option A — MySQL 8 (recommended).**
- ✅ Matches the intended Hostinger VPS + CyberPanel deploy pattern (§9) and MIIT operational familiarity; JSON column support is adequate for Tiptap documents; phpMyAdmin tooling on the VPS.
- ❌ Weaker JSON querying/indexing than Postgres; fewer managed-sync options.

**Option B — Postgres 16.**
- ✅ Best JSON(B) support for a JSON-document-heavy product; opens managed offline-sync engines (PowerSync, ElectricSQL) as alternatives to a custom sync layer; better full-text search for "search my notebooks".
- ❌ Off the org's standard VPS pattern — new operational surface for deploy, backup, and the runbooks in Claude-AI-Guide.

Note: if the custom sync engine (README §5) ever feels too expensive to build, Postgres+PowerSync is the escape hatch — worth weighing before locking.

**Decision:** Option A — MySQL 8 in production; SQLite for dev and on-device. Locked 2026-09-13 as **D-019** (Lead Developer via option prompt).
