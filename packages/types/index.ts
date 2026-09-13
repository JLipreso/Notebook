// @notebook/types — the frozen API contract (D-005).
// One file per domain, mirroring database-schema.md table-for-table in snake_case.
// Domain files (user, address, notebook, notebook-page, share, notification, sync, …)
// are authored TOGETHER with their Phase 4 migrations so type and table can never drift.

export * from './api'
