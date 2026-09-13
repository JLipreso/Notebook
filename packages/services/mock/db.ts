import type { AppNotification, Notebook, NotebookPage } from '@notebook/types'

import { mockNotebooks, mockNotifications, mockPages } from './fixtures'

// A mutable in-session copy of the fixtures, so mock-mode writes behave like
// real ones: create a notebook and it stays in the list until reload.
//
// Deliberately NOT persisted. Mock mode is for building UI before the backend
// exists; persistence would just be a second, worse offline engine (that is
// @notebook/sync's job, Phase 009).

function clone<T>(rows: T[]): T[] {
  return rows.map((row) => ({ ...row }))
}

export const db = {
  notebooks: clone(mockNotebooks),
  pages: clone(mockPages),
  notifications: clone(mockNotifications),
}

/** Restore the fixtures — used by tests and the demo reset affordance. */
export function resetDb(): void {
  db.notebooks = clone(mockNotebooks)
  db.pages = clone(mockPages)
  db.notifications = clone(mockNotifications)
}

export function nowIso(): string {
  return new Date().toISOString()
}

/** Soft delete, mirroring the server: the row stays, deleted_at is set. */
export function softDelete<T extends { deleted_at: string | null }>(row: T): T {
  row.deleted_at = nowIso()
  return row
}

export function isLive<T extends { deleted_at: string | null }>(row: T): boolean {
  return row.deleted_at === null
}

export type { AppNotification, Notebook, NotebookPage }
