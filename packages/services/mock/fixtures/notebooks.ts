import type { Notebook } from '@notebook/types'

import { mockUser } from './users'

// Three notebooks across two school years, one archived — enough to exercise
// the library filters, the archive shelf and the school-year grouping.

const T = '2026-09-01T02:00:00.000Z'

export const mockNotebooks: Notebook[] = [
  {
    id: '01930000-0000-7000-8000-000000000201',
    user_id: mockUser.id,
    notebook_type_id: '01930000-0000-7000-8000-000000000101', // composition
    title: 'English Notes',
    school_year: '2026-2027',
    cover_upload_id: null,
    font_family: null,
    status: 'active',
    archived_at: null,
    position: 0,
    client_updated_at: T,
    created_at: T,
    updated_at: T,
    deleted_at: null,
  },
  {
    id: '01930000-0000-7000-8000-000000000202',
    user_id: mockUser.id,
    notebook_type_id: '01930000-0000-7000-8000-000000000102', // writing
    title: 'Penmanship Practice',
    school_year: '2026-2027',
    cover_upload_id: null,
    font_family: null,
    status: 'active',
    archived_at: null,
    position: 1,
    client_updated_at: T,
    created_at: T,
    updated_at: T,
    deleted_at: null,
  },
  {
    id: '01930000-0000-7000-8000-000000000203',
    user_id: mockUser.id,
    notebook_type_id: '01930000-0000-7000-8000-000000000107', // timesheet
    title: 'Science Log 2025',
    school_year: '2025-2026',
    cover_upload_id: null,
    font_family: null,
    status: 'archived',
    archived_at: '2026-04-15T02:00:00.000Z',
    position: 2,
    client_updated_at: T,
    created_at: '2025-06-01T02:00:00.000Z',
    updated_at: '2026-04-15T02:00:00.000Z',
    deleted_at: null,
  },
]
