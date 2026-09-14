// @notebook/sync — the offline engine, isolated so the hairy part has ONE home
// and real tests. Implements database-schema.md §2 (D-013/D-016/D-017).

export { mintId } from './ids'
export { currentPlatform, hasNativeStorage, type Platform } from './platform'

export type { LocalRow, StorageAdapter } from './storage/adapter'
export { MemoryAdapter } from './storage/memory.adapter'
export { SqliteAdapter, type SqliteDriver } from './storage/sqlite.adapter'

export { pullTable } from './pull'
export { pushAll, pushTable, PUSH_ORDER, type PushOutcome } from './outbox'
export {
  uploadPending,
  requeueFailed,
  type LocalFileReader,
  type UploadOutcome,
} from './attachment-uploader'
export {
  runSync,
  isSyncing,
  resetSyncLock,
  type SyncPhase,
  type SyncProgress,
  type SyncResult,
  type RunSyncOptions,
} from './run'
