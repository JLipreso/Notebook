// @notebook/types — the frozen API contract (D-005).
// One file per domain, mirroring database-schema.md table-for-table in snake_case.
// Laravel emits exactly these fields; the backend adapts to the types, never
// the reverse. A type error here fails EVERY app's build — that is the point.

export * from './api'
export * from './user'
export * from './address'
export * from './notebook'
export * from './notebook-page'
export * from './attachment'
export * from './share'
export * from './notification'
export * from './sync'
