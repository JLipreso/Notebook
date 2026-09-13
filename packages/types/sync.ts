// The offline-sync wire contract — mirrors database-schema.md §2 exactly.
// Phase 009 implements both sides against THESE types. D-013, D-016, D-017.

/** Tables the device may push. RW only — everything else is pull or online-only. */
export const SYNC_RW_TABLES = ['notebooks', 'notebook_pages', 'page_attachments'] as const

export type SyncRwTable = (typeof SYNC_RW_TABLES)[number]

/** Read-only caches the device pulls but never writes (§2). */
export const SYNC_RO_TABLES = ['notebook_types', 'users'] as const

export type SyncRoTable = (typeof SYNC_RO_TABLES)[number]

export type SyncTable = SyncRwTable | SyncRoTable

/**
 * Every synced row carries these. Tombstones travel as normal rows with
 * `deleted_at` set (§2.4) — a pull that dropped them would resurrect deletes.
 */
export interface SyncRow {
  id: string
  updated_at: string
  deleted_at: string | null
  client_updated_at?: string | null
}

/**
 * GET /api/sync/{table}?since=&limit=
 *
 * `next_cursor` is the last row's server `updated_at`. Null means caught up;
 * otherwise pull again from it until fewer rows than the limit come back.
 */
export interface SyncPullResponse<T = SyncRow> {
  rows: T[]
  next_cursor: string | null
}

/** POST /api/sync/{table} — at most 100 rows per batch (§2.3). */
export interface SyncPushRequest<T = SyncRow> {
  rows: T[]
}

/**
 * A row the server refused. LWW loss is NOT an error: `server_copy` is the
 * winning version and the client overwrites its local row with it (§2.3).
 */
export interface SyncRejection<T = SyncRow> {
  row: T
  server_copy: T | null
  reason: 'stale' | 'forbidden' | 'invalid'
}

export interface SyncPushResponse<T = SyncRow> {
  /** Ids that landed; the client clears their dirty flag. */
  accepted: string[]
  rejected: SyncRejection<T>[]
}

/** Per-table pull cursor, persisted on the device. */
export interface SyncCursor {
  table: SyncTable
  cursor: string | null
  synced_at: string | null
}
