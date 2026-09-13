import type { ApiResponse, NotebookPage, PageContent } from '@notebook/types'

import { datasource } from './datasource'
import { http } from './http'
import { db, isLive, mockOk, nowIso, softDelete } from './mock'

// Pages (Phase 007). Autosave calls update() on a debounce — Phase 009 reroutes
// exactly these calls through the outbox without the composable changing.

export interface CreatePagePayload {
  /** Client-minted UUIDv7 (D-013). */
  id: string
  position: number
  title?: string | null
  content?: PageContent | null
}

export type UpdatePagePayload = Partial<Pick<NotebookPage, 'title' | 'position'>> & {
  content?: PageContent | null
}

export function listByNotebook(notebookId: string): Promise<ApiResponse<NotebookPage[]>> {
  return datasource(
    () =>
      mockOk(
        db.pages
          .filter(isLive)
          .filter((p) => p.notebook_id === notebookId)
          .sort((a, b) => a.position - b.position),
      ),
    async () => {
      const { data } = await http.get<ApiResponse<NotebookPage[]>>(`/notebooks/${notebookId}/pages`)
      return data
    },
  )
}

export function create(notebookId: string, payload: CreatePagePayload): Promise<ApiResponse<NotebookPage>> {
  return datasource(
    () => {
      const now = nowIso()
      const page: NotebookPage = {
        id: payload.id,
        notebook_id: notebookId,
        position: payload.position,
        title: payload.title ?? null,
        content: payload.content ?? { type: 'doc', content: [] },
        search_text: '',
        client_updated_at: now,
        created_at: now,
        updated_at: now,
        deleted_at: null,
      }
      db.pages.push(page)
      return mockOk(page, 'Page created')
    },
    async () => {
      const { data } = await http.post<ApiResponse<NotebookPage>>(`/notebooks/${notebookId}/pages`, payload)
      return data
    },
  )
}

export function update(id: string, payload: UpdatePagePayload): Promise<ApiResponse<NotebookPage>> {
  return datasource(
    () => {
      const page = db.pages.find((p) => p.id === id)
      if (!page) throw new Error(`Mock: page ${id} not found`)
      Object.assign(page, payload, { updated_at: nowIso(), client_updated_at: nowIso() })
      return mockOk(page, 'Page saved')
    },
    async () => {
      const { data } = await http.put<ApiResponse<NotebookPage>>(`/pages/${id}`, payload)
      return data
    },
  )
}

/** SOFT delete — tombstone (schema §2.4). */
export function remove(id: string): Promise<ApiResponse<null>> {
  return datasource(
    () => {
      const page = db.pages.find((p) => p.id === id)
      if (page) softDelete(page)
      return mockOk(null, 'Page deleted')
    },
    async () => {
      const { data } = await http.delete<ApiResponse<null>>(`/pages/${id}`)
      return data
    },
  )
}
