import type { SyncTable } from '@notebook/types'
import type { LocalRow, StorageAdapter } from './adapter'

/**
 * On-device SQLite (D-017), for the Capacitor shells.
 *
 * ======================= WHY THE DRIVER IS INJECTED =======================
 * @capacitor-community/sqlite only resolves inside the native shell. Importing
 * it here would break the web build AND the unit tests, which run this package
 * in Node. So the adapter takes a minimal driver interface and Phase 011's
 * native app passes the real plugin in. The engine never knows the difference —
 * that is the whole point of the StorageAdapter seam.
 * =========================================================================
 *
 * Storage shape: one table per synced table, with the row kept as a JSON blob
 * plus the few columns the engine actually queries. That is deliberate — the
 * device never runs domain queries over these (the app reads through the
 * services), it only needs "give me this row", "give me dirty rows" and
 * "give me everything". A faithful column-per-field mirror would be a second
 * schema to keep in sync with Laravel's for no gain.
 */

/** The slice of @capacitor-community/sqlite this adapter needs. */
export interface SqliteDriver {
  execute(statement: string): Promise<unknown>
  run(statement: string, values?: unknown[]): Promise<unknown>
  query(statement: string, values?: unknown[]): Promise<{ values?: unknown[] }>
}

/** Tables materialised on the device: the RW set plus the RO caches. */
const LOCAL_TABLES = [
  'notebooks',
  'notebook_pages',
  'page_attachments',
  'notebook_types',
  'users',
] as const

const SCHEMA_VERSION = 1

export class SqliteAdapter implements StorageAdapter {
  private ready = false

  constructor(private readonly db: SqliteDriver) {}

  async init(): Promise<void> {
    if (this.ready) return

    const result = await this.db.query('PRAGMA user_version')
    const current = Number((result.values?.[0] as { user_version?: number })?.user_version ?? 0)

    if (current < SCHEMA_VERSION) {
      for (const table of LOCAL_TABLES) {
        // `row` holds the full JSON. The sidecar columns exist only so the
        // engine can filter without parsing every blob.
        await this.db.execute(`
          CREATE TABLE IF NOT EXISTS ${table} (
            id TEXT PRIMARY KEY NOT NULL,
            row TEXT NOT NULL,
            updated_at TEXT,
            deleted_at TEXT,
            _dirty INTEGER NOT NULL DEFAULT 0
          )
        `)

        await this.db.execute(
          `CREATE INDEX IF NOT EXISTS ${table}_dirty_idx ON ${table} (_dirty)`,
        )
      }

      await this.db.execute(`
        CREATE TABLE IF NOT EXISTS _cursors (
          table_name TEXT PRIMARY KEY NOT NULL,
          cursor TEXT
        )
      `)

      await this.db.execute(`PRAGMA user_version = ${SCHEMA_VERSION}`)
    }

    this.ready = true
  }

  /** Table names come from a FIXED list — never from caller input. */
  private assertTable(table: string): void {
    if (!(LOCAL_TABLES as readonly string[]).includes(table)) {
      throw new Error(`[sync] unknown local table: ${table}`)
    }
  }

  async get<T>(table: string, id: string): Promise<T | null> {
    this.assertTable(table)

    const result = await this.db.query(`SELECT row FROM ${table} WHERE id = ?`, [id])
    const first = result.values?.[0] as { row?: string } | undefined

    return first?.row ? (JSON.parse(first.row) as T) : null
  }

  async list<T>(table: string): Promise<T[]> {
    this.assertTable(table)

    const result = await this.db.query(`SELECT row FROM ${table}`)

    return (result.values ?? []).map((entry) => JSON.parse((entry as { row: string }).row) as T)
  }

  async put<T extends LocalRow>(table: string, row: T): Promise<void> {
    await this.write(table, row, 1)
  }

  async putClean<T extends LocalRow>(table: string, row: T): Promise<void> {
    await this.write(table, row, 0)
  }

  private async write<T extends LocalRow>(table: string, row: T, dirty: 0 | 1): Promise<void> {
    this.assertTable(table)

    await this.db.run(
      `INSERT INTO ${table} (id, row, updated_at, deleted_at, _dirty)
       VALUES (?, ?, ?, ?, ?)
       ON CONFLICT(id) DO UPDATE SET
         row = excluded.row,
         updated_at = excluded.updated_at,
         deleted_at = excluded.deleted_at,
         _dirty = excluded._dirty`,
      [row.id, JSON.stringify(row), row.updated_at ?? null, row.deleted_at ?? null, dirty],
    )
  }

  async remove(table: string, id: string): Promise<void> {
    this.assertTable(table)
    await this.db.run(`DELETE FROM ${table} WHERE id = ?`, [id])
  }

  async dirtyRows<T>(table: string): Promise<T[]> {
    this.assertTable(table)

    const result = await this.db.query(`SELECT row FROM ${table} WHERE _dirty = 1`)

    return (result.values ?? []).map((entry) => JSON.parse((entry as { row: string }).row) as T)
  }

  async clearDirty(table: string, ids: string[]): Promise<void> {
    this.assertTable(table)
    if (ids.length === 0) return

    const placeholders = ids.map(() => '?').join(', ')
    await this.db.run(`UPDATE ${table} SET _dirty = 0 WHERE id IN (${placeholders})`, ids)
  }

  async getCursor(table: SyncTable): Promise<string | null> {
    const result = await this.db.query('SELECT cursor FROM _cursors WHERE table_name = ?', [table])

    return (result.values?.[0] as { cursor?: string | null } | undefined)?.cursor ?? null
  }

  async setCursor(table: SyncTable, cursor: string | null): Promise<void> {
    await this.db.run(
      `INSERT INTO _cursors (table_name, cursor) VALUES (?, ?)
       ON CONFLICT(table_name) DO UPDATE SET cursor = excluded.cursor`,
      [table, cursor],
    )
  }
}
