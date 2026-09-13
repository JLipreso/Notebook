import type { StorageAdapter } from './adapter'

// In-memory adapter for web dev and unit tests. Web MVP is API-direct, so this
// only backs transient state; nothing here survives a reload — by design.
export class MemoryAdapter implements StorageAdapter {
  private tables = new Map<string, Map<string, unknown>>()
  private dirty = new Map<string, Set<string>>()

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

  async get<T>(table: string, id: string): Promise<T | null> {
    return (this.table(table).get(id) as T | undefined) ?? null
  }

  async list<T>(table: string): Promise<T[]> {
    return [...this.table(table).values()] as T[]
  }

  async put<T extends { id: string }>(table: string, row: T): Promise<void> {
    this.table(table).set(row.id, row)
    let dirtySet = this.dirty.get(table)
    if (!dirtySet) {
      dirtySet = new Set()
      this.dirty.set(table, dirtySet)
    }
    dirtySet.add(row.id)
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
}
