import { SYNC_RO_TABLES, SYNC_RW_TABLES, type SyncTable } from '@notebook/types'
import { pushAll, type PushOutcome } from './outbox'
import { pullTable } from './pull'
import { uploadPending, type LocalFileReader, type UploadOutcome } from './attachment-uploader'
import type { StorageAdapter } from './storage/adapter'

export type SyncPhase = 'idle' | 'pushing' | 'pulling' | 'uploading' | 'done' | 'error'

export interface SyncProgress {
  phase: SyncPhase
  table?: SyncTable
  message?: string
}

export interface SyncResult {
  ran: boolean
  push?: PushOutcome
  pulled?: Record<string, number>
  uploads?: UploadOutcome
  error?: unknown
}

export interface RunSyncOptions {
  storage: StorageAdapter
  /** Native-only; omit on web and the attachment step is skipped. */
  readFile?: LocalFileReader
  onProgress?: (progress: SyncProgress) => void
}

/**
 * ============================== SINGLE FLIGHT ==============================
 * Module-level, deliberately. Sync is triggered from three places — app
 * foreground, network regain, and a manual button — and they fire together
 * constantly (unlocking a phone on wifi does all three at once). Two concurrent
 * runs would double-push the same outbox rows and interleave cursor writes.
 *
 * A second call while one is in flight returns `{ ran: false }` rather than
 * queuing: the run already underway will pick up whatever the caller wanted
 * synced anyway.
 * ==========================================================================
 */
let inFlight: Promise<SyncResult> | null = null

export function isSyncing(): boolean {
  return inFlight !== null
}

/**
 * Drop the in-flight lock.
 *
 * Exists for TESTS — a module-level lock survives between test cases, so one
 * test parking a run would wedge every later one. Calling this in production
 * would permit the concurrent runs the lock exists to prevent.
 */
export function resetSyncLock(): void {
  inFlight = null
}

/**
 * push → pull → attachments (phase file §2).
 *
 * Push first so local work reaches the server before the pull can hand back a
 * server copy that would look newer.
 */
export function runSync(options: RunSyncOptions): Promise<SyncResult> {
  if (inFlight) return inFlight

  inFlight = execute(options).finally(() => {
    inFlight = null
  })

  return inFlight
}

async function execute(options: RunSyncOptions): Promise<SyncResult> {
  const { storage, readFile, onProgress } = options
  const report = (progress: SyncProgress) => onProgress?.(progress)

  try {
    await storage.init()

    report({ phase: 'pushing' })
    const push = await pushAll(storage)

    const pulled: Record<string, number> = {}

    // RW first: a row we just pushed should come back stamped before anything
    // that references it.
    for (const table of [...SYNC_RW_TABLES, ...SYNC_RO_TABLES] as SyncTable[]) {
      report({ phase: 'pulling', table })
      pulled[table] = await pullTable(storage, table)
    }

    let uploads: UploadOutcome | undefined

    if (readFile) {
      report({ phase: 'uploading' })
      uploads = await uploadPending(storage, readFile)

      // Newly-uploaded rows carry a file_upload_id the server has not seen.
      if (uploads.uploaded > 0) await pushAll(storage)
    }

    report({ phase: 'done' })

    return { ran: true, push, pulled, uploads }
  } catch (error) {
    report({ phase: 'error', message: error instanceof Error ? error.message : 'Sync failed' })

    return { ran: true, error }
  }
}
