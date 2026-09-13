// @notebook/sync — the offline engine, isolated so the hairy part has ONE home
// and real tests. outbox.ts / pull.ts / push.ts (per database-schema.md §2) and
// sqlite.adapter.ts / attachment-uploader.ts land with M1 implementation.

export { mintId } from './ids'
export { currentPlatform, hasNativeStorage, type Platform } from './platform'
export type { StorageAdapter } from './storage/adapter'
export { MemoryAdapter } from './storage/memory.adapter'
