import { syncService } from '@notebook/services'
import { SYNC_RW_TABLES, type SyncRow, type SyncRwTable } from '@notebook/types'
import type { StorageAdapter } from './storage/adapter'

/**
 * Push locally-changed rows (schema §2.3).
 *
 * ============================== ORDER MATTERS ==============================
 * Tables are pushed parent-before-child — notebooks, then pages, then
 * attachments. A page whose notebook the server has never seen is rejected as
 * 'forbidden' (its parent is not owned because it does not exist yet), so
 * pushing children first would drop real work on the floor.
 *
 * SYNC_RW_TABLES is already in that order; this re-states it so a future
 * reorder of that const cannot silently break sync.
 * ===========================================================================
 */
const PUSH_ORDER: readonly SyncRwTable[] = ['notebooks', 'notebook_pages', 'page_attachments']

export interface PushOutcome {
  accepted: number
  rejected: number
}

/** Push every dirty row in every RW table, in dependency order. */
export async function pushAll(storage: StorageAdapter, batchSize = 100): Promise<PushOutcome> {
  const outcome: PushOutcome = { accepted: 0, rejected: 0 }

  for (const table of PUSH_ORDER) {
    const result = await pushTable(storage, table, batchSize)
    outcome.accepted += result.accepted
    outcome.rejected += result.rejected
  }

  return outcome
}

export async function pushTable(
  storage: StorageAdapter,
  table: SyncRwTable,
  batchSize = 100,
): Promise<PushOutcome> {
  const dirty = await storage.dirtyRows<SyncRow>(table)
  const outcome: PushOutcome = { accepted: 0, rejected: 0 }

  for (let i = 0; i < dirty.length; i += batchSize) {
    const batch = dirty.slice(i, i + batchSize)
    const response = await syncService.push<SyncRow>(table, batch)

    if (response.accepted?.length) {
      await storage.clearDirty(table, response.accepted)
      outcome.accepted += response.accepted.length
    }

    for (const rejection of response.rejected ?? []) {
      outcome.rejected += 1

      // LWW loss: the server's copy wins locally too, or the device would keep
      // re-pushing a row it can never win with (§2.3).
      if (rejection.server_copy) {
        await storage.putClean(table, rejection.server_copy)
        await storage.clearDirty(table, [rejection.server_copy.id])
      } else {
        // 'forbidden' or 'invalid' — no server copy to converge on. Stop
        // retrying it forever; it stays local until a human intervenes.
        await storage.clearDirty(table, [rejection.row.id])
      }
    }
  }

  return outcome
}

export { PUSH_ORDER, SYNC_RW_TABLES }
