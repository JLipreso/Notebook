import type { SyncPullResponse, SyncPushResponse, SyncRow, SyncRwTable, SyncTable } from '@notebook/types'

import { http } from './http'

// The sync wire calls (schema §2), used by @notebook/sync in Phase 009.
//
// NO mock branch: sync only ever runs against a real backend. In mock mode the
// engine is not running at all — mock mode IS the offline story until Phase 009.

/** GET /api/sync/{table}?since=&limit= — rows INCLUDING tombstones. */
export async function pull<T extends SyncRow = SyncRow>(
  table: SyncTable,
  since: string | null = null,
  limit = 500,
): Promise<SyncPullResponse<T>> {
  const { data } = await http.get<SyncPullResponse<T>>(`/sync/${table}`, {
    params: { since, limit },
  })
  return data
}

/**
 * POST /api/sync/{table} — at most 100 rows. Rejected rows come back with the
 * winning `server_copy`; the caller overwrites its local row with it (§2.3).
 */
export async function push<T extends SyncRow = SyncRow>(
  table: SyncRwTable,
  rows: T[],
): Promise<SyncPushResponse<T>> {
  const { data } = await http.post<SyncPushResponse<T>>(`/sync/${table}`, { rows })
  return data
}
