// Notebooks & types — mirrors 0001_01_01_000005 / 000006
// (database-schema.md §5, tables 7-8). D-010, D-011, D-012, D-013, D-016.

/** CSS-drawn ruling patterns (D-010). No image assets — Phase 007 draws these. */
export type RulingPattern = 'single_ruled' | 'penmanship_blue_red' | 'blank' | 'grid'

/** Chrome rendered ON the paper, never an editable Tiptap block. */
export type PaperFieldType = 'date' | 'text' | 'signature'

export interface PaperField {
  key: string
  label: string
  type: PaperFieldType
}

export interface PaperMargin {
  side: 'left' | 'right'
  offset_mm: number
  /** A brand token NAME from the Tailwind preset (e.g. 'margin') — never a hex (CLAUDE.md §4). */
  color: string
}

export interface PaperGrid {
  size_mm: number
}

/**
 * THE paper-fidelity contract (D-010).
 *
 * This interface, `notebook_types.page_template` as written by the backend's
 * NotebookTypeSeeder, and `PaperPage.vue` in @notebook/ui (Phase 007) are ONE
 * contract in three places. Change a key here and you change all three — the
 * seeder's header comment says the same thing from the other side.
 */
export interface PageTemplate {
  ruling: RulingPattern
  /** Drives the --paper-line-height CSS variable so text sits ON the ruling. */
  line_spacing_mm: number
  margin: PaperMargin | null
  /** Only meaningful when ruling === 'grid'. */
  grid: PaperGrid | null
  header_fields: PaperField[]
  footer_fields: PaperField[]
  /** Tiptap node names pre-inserted into a new page (e.g. Timesheet -> ['table']). */
  default_blocks: string[]
}

export interface NotebookType {
  id: string
  key: string
  name: string
  description: string | null
  page_template: PageTemplate
  min_level: string | null
  audience_hint: string | null
  /** Types that only fully unlock once the ink block ships (D-012). */
  requires_ink: boolean
  is_active: boolean
  sort_order: number
  created_at: string
  updated_at: string
}

export type NotebookStatus = 'active' | 'archived'

/**
 * [RW] offline-writable (D-016). `id` is minted ON THE DEVICE (D-013) and the
 * server stores it verbatim; `client_updated_at` is the device clock used for
 * last-write-wins on sync push (schema §2.3).
 */
export interface Notebook {
  id: string
  user_id: string
  notebook_type_id: string
  title: string
  /** 'YYYY-YYYY' — validate with isValidSchoolYear() from @notebook/utility. */
  school_year: string
  cover_upload_id: string | null
  font_family: string | null
  status: NotebookStatus
  archived_at: string | null
  position: number
  client_updated_at: string | null
  created_at: string
  updated_at: string
  deleted_at: string | null
}

/** Optional eager-loaded relations (backend uses ->with('rel:id,col'), not Resources). */
export interface NotebookWithRelations extends Notebook {
  notebook_type?: NotebookType
}
