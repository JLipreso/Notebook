import type { SyncTable } from '@notebook/types'

// Storage adapter contract. Implementations:
//   sqlite.adapter.ts — Capacitor @capacitor-community/sqlite (native shells)
//   memory.adapter.ts — web dev/tests (web MVP runs API-direct)
//   an Electron adapter joins when the desktop shell lands (D-025/D-026)

/** Every locally-stored row carries at least this. */
export interface LocalRow {
  id: string
  updated_at?: string | null
  deleted_at?: string | null
  client_updated_at?: string | null
}

export interface StorageAdapter {
  /** Open (and migrate if needed) the local store. */
  init(): Promise<void>

  get<T>(table: string, id: string): Promise<T | null>
  list<T>(table: string): Promise<T[]>

  /**
   * Write a row AND mark it dirty — this is a LOCAL EDIT, so it belongs in the
   * outbox. Rows arriving from the server use putClean().
   */
  put<T extends LocalRow>(table: string, row: T): Promise<void>

  /**
   * Write a row WITHOUT marking it dirty. Used by pull and by LWW rejections:
   * a row the server just gave us must never be pushed straight back.
   */
  putClean<T extends LocalRow>(table: string, row: T): Promise<void>

  remove(table: string, id: string): Promise<void>

  /** Rows changed locally since the last successful push (the outbox reads this). */
  dirtyRows<T>(table: string): Promise<T[]>

  /** Clear the dirty flag once the server has accepted these ids. */
  clearDirty(table: string, ids: string[]): Promise<void>

  /** Per-table pull cursor (the last server `updated_at` we saw). */
  getCursor(table: SyncTable): Promise<string | null>
  setCursor(table: SyncTable, cursor: string | null): Promise<void>
}
