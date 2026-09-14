/**
 * The slice of @notebook/sync's StorageAdapter the services need.
 *
 * Declared here rather than imported ON PURPOSE: @notebook/sync already imports
 * @notebook/services (for syncService), so importing back would make a package
 * cycle. Structural typing means the real adapter satisfies this without either
 * package knowing about the other's file.
 */
export interface LocalStore {
  get<T>(table: string, id: string): Promise<T | null>
  list<T>(table: string): Promise<T[]>
  put<T extends { id: string }>(table: string, row: T): Promise<void>
  putClean<T extends { id: string }>(table: string, row: T): Promise<void>
  remove(table: string, id: string): Promise<void>
}

/**
 * The local-store seam (Phase 009, "datasource routing").
 *
 * ======================== THE GOLDEN RULE, EXTENDED ========================
 * CLAUDE.md §3 says exactly one module decides mock vs live. This is the same
 * idea for the third source: on a platform with native storage, the RW tables
 * are read and written LOCALLY and reconciled by runSync(); on web they stay
 * API-direct.
 *
 * The decision lives HERE, inside the services — not in a view, not in a
 * composable, not in a store. Phases 006–008 shipped before this file existed
 * and none of them changed a line when it landed. That is the whole point.
 * ==========================================================================
 *
 * The adapter is REGISTERED by the native shell at boot rather than imported,
 * because @capacitor-community/sqlite does not resolve on web. No registration
 * means no local store, which means API-direct — the correct web behaviour and
 * a safe default everywhere else.
 */
let adapter: LocalStore | null = null

/** Called once by the native shell (Phase 011) after opening SQLite. */
export function registerLocalStore(store: LocalStore): void {
  adapter = store
}

export function clearLocalStore(): void {
  adapter = null
}

/**
 * The local store, or null when this platform has none.
 *
 * Services call this and branch: a null means "talk to the API directly",
 * which is what the web has always done.
 */
export function resolveStore(): LocalStore | null {
  return adapter
}

export function hasLocalStore(): boolean {
  return adapter !== null
}
