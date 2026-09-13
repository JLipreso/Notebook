import type { NotebookType } from '@notebook/types'

// The 7 launch types. page_template is IDENTICAL to the backend's
// NotebookTypeSeeder — generated from a seeded database, never retyped (D-010).
// If the seeder changes the template shape, regenerate this file so mock mode
// and live mode render the same paper.

export const mockNotebookTypes: NotebookType[] = [
  {
    id: '01930000-0000-7000-8000-000000000101',
    key: 'composition',
    name: 'Composition Notebook',
    description: 'The classic ruled notebook with a red margin line — the everyday workhorse for notes and essays.',
    page_template: {
      ruling: 'single_ruled',
      line_spacing_mm: 8,
      margin: {
        side: 'left',
        offset_mm: 25,
        color: 'margin'
      },
      grid: null,
      header_fields: [],
      footer_fields: [],
      default_blocks: [
        'paragraph'
      ]
    },
    min_level: null,
    audience_hint: null,
    requires_ink: false,
    is_active: true,
    sort_order: 0,
    created_at: '2026-09-01T02:00:00.000Z',
    updated_at: '2026-09-01T02:00:00.000Z'
  },
  {
    id: '01930000-0000-7000-8000-000000000102',
    key: 'writing',
    name: 'Writing Notebook',
    description: 'Penmanship guide lines for early learners, with date and signature chrome.',
    page_template: {
      ruling: 'penmanship_blue_red',
      line_spacing_mm: 12,
      margin: null,
      grid: null,
      header_fields: [
        {
          key: 'date',
          label: 'Date:',
          type: 'date'
        }
      ],
      footer_fields: [
        {
          key: 'teacher_signature',
          label: 'Teacher\'s Signature',
          type: 'signature'
        },
        {
          key: 'parent_signature',
          label: 'Parent\'s Signature',
          type: 'signature'
        }
      ],
      default_blocks: [
        'paragraph'
      ]
    },
    min_level: 'preschool',
    audience_hint: 'Preschool to Grade 3',
    requires_ink: false,
    is_active: true,
    sort_order: 1,
    created_at: '2026-09-01T02:00:00.000Z',
    updated_at: '2026-09-01T02:00:00.000Z'
  },
  {
    id: '01930000-0000-7000-8000-000000000103',
    key: 'drawing',
    name: 'Drawing Notebook',
    description: 'Blank pages for sketching and art.',
    page_template: {
      ruling: 'blank',
      line_spacing_mm: 0,
      margin: null,
      grid: null,
      header_fields: [],
      footer_fields: [],
      default_blocks: []
    },
    min_level: null,
    audience_hint: 'All levels',
    requires_ink: true,
    is_active: true,
    sort_order: 2,
    created_at: '2026-09-01T02:00:00.000Z',
    updated_at: '2026-09-01T02:00:00.000Z'
  },
  {
    id: '01930000-0000-7000-8000-000000000104',
    key: 'diary',
    name: 'Diary',
    description: 'A dated personal journal, one entry per page.',
    page_template: {
      ruling: 'single_ruled',
      line_spacing_mm: 8,
      margin: null,
      grid: null,
      header_fields: [
        {
          key: 'date',
          label: 'Date:',
          type: 'date'
        }
      ],
      footer_fields: [],
      default_blocks: [
        'paragraph'
      ]
    },
    min_level: null,
    audience_hint: null,
    requires_ink: false,
    is_active: true,
    sort_order: 3,
    created_at: '2026-09-01T02:00:00.000Z',
    updated_at: '2026-09-01T02:00:00.000Z'
  },
  {
    id: '01930000-0000-7000-8000-000000000105',
    key: 'scrapbook',
    name: 'Scrapbook',
    description: 'Blank pages built for photos, clippings and captions.',
    page_template: {
      ruling: 'blank',
      line_spacing_mm: 0,
      margin: null,
      grid: null,
      header_fields: [
        {
          key: 'title',
          label: 'Title',
          type: 'text'
        }
      ],
      footer_fields: [],
      default_blocks: [
        'image',
        'paragraph'
      ]
    },
    min_level: null,
    audience_hint: null,
    requires_ink: false,
    is_active: true,
    sort_order: 4,
    created_at: '2026-09-01T02:00:00.000Z',
    updated_at: '2026-09-01T02:00:00.000Z'
  },
  {
    id: '01930000-0000-7000-8000-000000000106',
    key: 'logbook',
    name: 'Log Book',
    description: 'Grid pages for records, observations and lab work.',
    page_template: {
      ruling: 'grid',
      line_spacing_mm: 5,
      margin: null,
      grid: {
        size_mm: 5
      },
      header_fields: [
        {
          key: 'date',
          label: 'Date:',
          type: 'date'
        }
      ],
      footer_fields: [],
      default_blocks: [
        'paragraph'
      ]
    },
    min_level: null,
    audience_hint: null,
    requires_ink: false,
    is_active: true,
    sort_order: 5,
    created_at: '2026-09-01T02:00:00.000Z',
    updated_at: '2026-09-01T02:00:00.000Z'
  },
  {
    id: '01930000-0000-7000-8000-000000000107',
    key: 'timesheet',
    name: 'Time Sheet',
    description: 'Grid pages that open with a table for hours and activities.',
    page_template: {
      ruling: 'grid',
      line_spacing_mm: 5,
      margin: null,
      grid: {
        size_mm: 5
      },
      header_fields: [
        {
          key: 'week_of',
          label: 'Week of:',
          type: 'date'
        }
      ],
      footer_fields: [
        {
          key: 'supervisor_signature',
          label: 'Supervisor\'s Signature',
          type: 'signature'
        }
      ],
      default_blocks: [
        'table'
      ]
    },
    min_level: null,
    audience_hint: null,
    requires_ink: false,
    is_active: true,
    sort_order: 6,
    created_at: '2026-09-01T02:00:00.000Z',
    updated_at: '2026-09-01T02:00:00.000Z'
  }
]
