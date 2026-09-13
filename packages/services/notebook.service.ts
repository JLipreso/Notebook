import type { ApiResponse, Notebook, NotebookType } from '@notebook/types'

import { datasource } from './datasource'
import { http } from './http'
import { db, isLive, mockNotebookTypes, mockOk, mockUser, nowIso, softDelete } from './mock'

// The library (Phase 006). Ids are CLIENT-MINTED (D-013) — callers pass one in
// via mintId() from @notebook/sync so the same path works offline later.

export interface NotebookFilters {
  status?: Notebook['status']
  school_year?: string
}

export interface CreateNotebookPayload {
  /** Client-minted UUIDv7 — the server inserts it verbatim. */
  id: string
  notebook_type_id: string
  title: string
  school_year: string
  font_family?: string | null
  position?: number
}

export type UpdateNotebookPayload = Partial<
  Pick<Notebook, 'title' | 'font_family' | 'position' | 'notebook_type_id' | 'cover_upload_id'>
>

/** GET /api/notebook-types — the 7 launch types with their page_template. */
export function types(): Promise<ApiResponse<NotebookType[]>> {
  return datasource(
    () => mockOk(mockNotebookTypes),
    async () => {
      const { data } = await http.get<ApiResponse<NotebookType[]>>('/notebook-types')
      return data
    },
  )
}

export function list(filters: NotebookFilters = {}): Promise<ApiResponse<Notebook[]>> {
  return datasource(
    () =>
      mockOk(
        db.notebooks
          .filter(isLive)
          .filter((n) => (filters.status ? n.status === filters.status : true))
          .filter((n) => (filters.school_year ? n.school_year === filters.school_year : true))
          .sort((a, b) => a.position - b.position),
      ),
    async () => {
      const { data } = await http.get<ApiResponse<Notebook[]>>('/notebooks', { params: filters })
      return data
    },
  )
}

export function create(payload: CreateNotebookPayload): Promise<ApiResponse<Notebook>> {
  return datasource(
    () => {
      const now = nowIso()
      const notebook: Notebook = {
        id: payload.id,
        user_id: mockUser.id,
        notebook_type_id: payload.notebook_type_id,
        title: payload.title,
        school_year: payload.school_year,
        cover_upload_id: null,
        font_family: payload.font_family ?? null,
        status: 'active',
        archived_at: null,
        position: payload.position ?? db.notebooks.length,
        client_updated_at: now,
        created_at: now,
        updated_at: now,
        deleted_at: null,
      }
      db.notebooks.push(notebook)
      return mockOk(notebook, 'Notebook created')
    },
    async () => {
      const { data } = await http.post<ApiResponse<Notebook>>('/notebooks', payload)
      return data
    },
  )
}

export function update(id: string, payload: UpdateNotebookPayload): Promise<ApiResponse<Notebook>> {
  return datasource(
    () => {
      const notebook = db.notebooks.find((n) => n.id === id)
      if (!notebook) throw new Error(`Mock: notebook ${id} not found`)
      Object.assign(notebook, payload, { updated_at: nowIso(), client_updated_at: nowIso() })
      return mockOk(notebook, 'Notebook updated')
    },
    async () => {
      const { data } = await http.put<ApiResponse<Notebook>>(`/notebooks/${id}`, payload)
      return data
    },
  )
}

export function archive(id: string): Promise<ApiResponse<Notebook>> {
  return setArchived(id, true)
}

export function unarchive(id: string): Promise<ApiResponse<Notebook>> {
  return setArchived(id, false)
}

function setArchived(id: string, archived: boolean): Promise<ApiResponse<Notebook>> {
  return datasource(
    () => {
      const notebook = db.notebooks.find((n) => n.id === id)
      if (!notebook) throw new Error(`Mock: notebook ${id} not found`)
      notebook.status = archived ? 'archived' : 'active'
      notebook.archived_at = archived ? nowIso() : null
      notebook.updated_at = nowIso()
      return mockOk(notebook, archived ? 'Notebook archived' : 'Notebook restored')
    },
    async () => {
      const { data } = await http.post<ApiResponse<Notebook>>(
        `/notebooks/${id}/${archived ? 'archive' : 'unarchive'}`,
      )
      return data
    },
  )
}

/** DELETE — SOFT delete. The row survives as a tombstone (schema §2.4). */
export function remove(id: string): Promise<ApiResponse<null>> {
  return datasource(
    () => {
      const notebook = db.notebooks.find((n) => n.id === id)
      if (notebook) softDelete(notebook)
      return mockOk(null, 'Notebook deleted')
    },
    async () => {
      const { data } = await http.delete<ApiResponse<null>>(`/notebooks/${id}`)
      return data
    },
  )
}
