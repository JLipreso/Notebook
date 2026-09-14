import type { SyncTable } from '@notebook/types'
import type { LocalRow, StorageAdapter } from './adapter'

/**
 * In-memory adapter for web dev and the sync unit tests.
 *
 * Web MVP is API-direct, so this backs transient state only; nothing survives a
 * reload, by design. It is also the adapter the engine's tests run against —
 * the point of the StorageAdapter seam is that the engine cannot tell.
 */
export class MemoryAdapter implements StorageAdapter {
  private tables = new Map<string, Map<string, unknown>>()
  private dirty = new Map<string, Set<string>>()
  private cursors = new Map<string, string | null>()

  async init(): Promise<void> {
    // nothing to open
  }

  private table(name: string): Map<string, unknown> {
    let table = this.tables.get(name)
    if (!table) {
      table = new Map()
      this.tables.set(name, table)
    }
    return table
  }

  private dirtySet(name: string): Set<string> {
    let set = this.dirty.get(name)
    if (!set) {
      set = new Set()
      this.dirty.set(name, set)
    }
    return set
  }

  async get<T>(table: string, id: string): Promise<T | null> {
    return (this.table(table).get(id) as T | undefined) ?? null
  }

  async list<T>(table: string): Promise<T[]> {
    return [...this.table(table).values()] as T[]
  }

  async put<T extends LocalRow>(table: string, row: T): Promise<void> {
    this.table(table).set(row.id, row)
    this.dirtySet(table).add(row.id)
  }

  async putClean<T extends LocalRow>(table: string, row: T): Promise<void> {
    this.table(table).set(row.id, row)
    // Deliberately does NOT touch the dirty set — see the contract.
    this.dirtySet(table).delete(row.id)
  }

  async remove(table: string, id: string): Promise<void> {
    this.table(table).delete(id)
    this.dirty.get(table)?.delete(id)
  }

  async dirtyRows<T>(table: string): Promise<T[]> {
    const ids = this.dirty.get(table)
    if (!ids) return []
    const rows = this.table(table)
    return [...ids].map((id) => rows.get(id)).filter((row): row is T => row !== undefined)
  }

  async clearDirty(table: string, ids: string[]): Promise<void> {
    const set = this.dirty.get(table)
    if (!set) return
    ids.forEach((id) => set.delete(id))
  }

  async getCursor(table: SyncTable): Promise<string | null> {
    return this.cursors.get(table) ?? null
  }

  async setCursor(table: SyncTable, cursor: string | null): Promise<void> {
    this.cursors.set(table, cursor)
  }
}
