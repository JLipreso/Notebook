// Storage adapter contract. Implementations:
//   sqlite.adapter.ts — Capacitor @capacitor-community/sqlite (M1 implementation)
//   memory.adapter.ts — web dev/tests (web MVP runs API-direct)
//   an Electron adapter joins when the desktop shell lands (D-025/D-026)

export interface StorageAdapter {
  /** Open (and migrate if needed) the local store. */
  init(): Promise<void>

  get<T>(table: string, id: string): Promise<T | null>
  list<T>(table: string): Promise<T[]>
  put<T extends { id: string }>(table: string, row: T): Promise<void>
  remove(table: string, id: string): Promise<void>

  /** Rows changed locally since the last successful push (the outbox reads this). */
  dirtyRows<T>(table: string): Promise<T[]>
}
