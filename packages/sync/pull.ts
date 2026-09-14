import { syncService } from '@notebook/services'
import type { SyncRow, SyncTable } from '@notebook/types'
import type { StorageAdapter } from './storage/adapter'

/**
 * Pull one table to exhaustion (schema §2.2).
 *
 * Rows arrive INCLUDING tombstones and are written with putClean() — a row the
 * server just handed us must never look like a local edit, or the next push
 * would send it straight back.
 */
export async function pullTable(
  storage: StorageAdapter,
  table: SyncTable,
  limit = 500,
): Promise<number> {
  let cursor = await storage.getCursor(table)
  let pulled = 0

  // Loop until a page comes back short — that is the server saying "caught up".
  for (;;) {
    const response = await syncService.pull<SyncRow>(table, cursor, limit)
    const rows = response.rows ?? []

    for (const row of rows) {
      await storage.putClean(table, row)
    }

    pulled += rows.length

    // next_cursor is null when the page was not full.
    if (!response.next_cursor || rows.length === 0) {
      // Advance to the newest row we saw so the next run resumes from here.
      const newest = rows.at(-1)?.updated_at ?? cursor
      await storage.setCursor(table, newest ?? null)
      break
    }

    cursor = response.next_cursor
    await storage.setCursor(table, cursor)
  }

  return pulled
}
