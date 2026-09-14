// Sharing — mirrors 0001_01_01_000008 (database-schema.md §5, table 12). D-016.
//
import type { PageContent } from './notebook-page'
import type { PageTemplate } from './notebook'

// [ON] online-only: shared editing never merges offline. M1 ships read-only
// token links; 'read_write' and direct user shares are schema-ready but land
// after M1 (Phase 010 scope note).

export type ShareAccess = 'read' | 'read_write'

/**
 * DB CHECK enforces exactly one of (shared_with_user_id, share_token) — the
 * types cannot express that, so the backend validates it and the CHECK backstops.
 */
export interface NotebookShare {
  id: string
  notebook_id: string
  /** NULL = the whole notebook; set = that one page. */
  page_id: string | null
  shared_by: string
  shared_with_user_id: string | null
  share_token: string | null
  access: ShareAccess
  expires_at: string | null
  revoked_at: string | null
  created_at: string
  updated_at: string
}

/** A share as the manage list renders it — derived flags come from the server. */
export interface NotebookShareWithStatus extends NotebookShare {
  is_revoked: boolean
  is_expired: boolean
  is_active: boolean
}

/**
 * What GET /api/shared/{token} returns to a SIGNED-OUT viewer (Phase 010).
 *
 * Deliberately a TRIMMED shape, not the full models: a public viewer gets the
 * paper and the words, and nothing else. No user_id, no timestamps, no
 * search_text — a share link must not leak more than it renders.
 */
export interface SharedNotebookView {
  notebook: {
    id: string
    title: string
    school_year: string
    notebook_type: {
      key: string
      name: string
      /** Typed, because the viewer renders real paper from it (Phase 007). */
      page_template: PageTemplate
    }
  }
  pages: {
    id: string
    position: number
    title: string | null
    content: PageContent | null
  }[]
  access: ShareAccess
  expires_at: string | null
}
