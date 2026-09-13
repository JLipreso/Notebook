// Pages & their Tiptap content — mirrors 0001_01_01_000006
// (database-schema.md §5, table 9). D-012, D-013, D-018.

/**
 * A ProseMirror/Tiptap document node. `content` is JSON in, JSON out — NEVER
 * HTML (D-018). Deliberately loose: the shape is recursive and Tiptap owns the
 * detail; what we DO pin down is which node types are allowed (below).
 */
export interface PageNode {
  type: string
  attrs?: Record<string, unknown>
  content?: PageNode[]
  marks?: { type: string; attrs?: Record<string, unknown> }[]
  text?: string
}

/** The root document stored in notebook_pages.content. */
export interface PageContent {
  type: 'doc'
  content?: PageNode[]
}

/**
 * ============================ TWO-SIDED CONTRACT ============================
 * The node whitelist. Phase 007's NotebookEditor enables exactly these, and the
 * backend sanitizer strips anything not in this list on EVERY content write
 * (its PHP mirror lives in backend/config/notebook.php -> allowed_nodes).
 *
 * Adding a node type is ALWAYS a two-sided change: this array AND the PHP
 * config, in the same commit. A node allowed on only one side either silently
 * vanishes on save or slips past sanitization.
 *
 * 'image' is listed but only becomes reachable in Phase 008 (attachments).
 * The drawing/ink node is deliberately NOT here — reserved for post-M1 (D-012).
 * ===========================================================================
 */
export const ALLOWED_NODES = [
  'doc',
  'paragraph',
  'text',
  'heading',
  'bulletList',
  'orderedList',
  'listItem',
  'table',
  'tableRow',
  'tableHeader',
  'tableCell',
  'image',
  'horizontalRule',
  'hardBreak',
] as const

export type AllowedNode = (typeof ALLOWED_NODES)[number]

/** Marks (inline formatting) the editor enables — same two-sided rule. */
export const ALLOWED_MARKS = ['bold', 'italic', 'underline', 'strike'] as const

export type AllowedMark = (typeof ALLOWED_MARKS)[number]

/** Headings are capped at two levels (Phase 007 MVP). */
export const ALLOWED_HEADING_LEVELS = [1, 2] as const

/**
 * [RW] offline-writable (D-016): client-minted `id`, `client_updated_at` for
 * last-write-wins. The page is the conflict unit (schema §5).
 */
export interface NotebookPage {
  id: string
  notebook_id: string
  position: number
  title: string | null
  content: PageContent | null
  /** Plain-text extraction of `content`, maintained server-side on every write. */
  search_text: string | null
  client_updated_at: string | null
  created_at: string
  updated_at: string
  deleted_at: string | null
}
