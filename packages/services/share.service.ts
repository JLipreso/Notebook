import type { ApiResponse, NotebookShare, SharedNotebookView } from '@notebook/types'

import { datasource } from './datasource'
import { http } from './http'
import { mockOk } from './mock'

// Sharing (Phase 010). ONLINE-ONLY (D-016). M1 ships read-only token links.

export interface CreateSharePayload {
  notebook_id: string
  /** Omit for the whole notebook; set for a single page. */
  page_id?: string | null
  expires_at?: string | null
}

export function create(payload: CreateSharePayload): Promise<ApiResponse<NotebookShare>> {
  return datasource(
    () =>
      mockOk({
        id: 'mock-share',
        notebook_id: payload.notebook_id,
        page_id: payload.page_id ?? null,
        shared_by: 'mock-user',
        shared_with_user_id: null,
        share_token: 'a'.repeat(64),
        access: 'read',
        expires_at: payload.expires_at ?? null,
        revoked_at: null,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
      } satisfies NotebookShare),
    async () => {
      const { data } = await http.post<ApiResponse<NotebookShare>>('/shares', payload)
      return data
    },
  )
}

export function list(notebookId: string): Promise<ApiResponse<NotebookShare[]>> {
  return datasource(
    () => mockOk([] as NotebookShare[]),
    async () => {
      const { data } = await http.get<ApiResponse<NotebookShare[]>>('/shares', {
        params: { notebook_id: notebookId },
      })
      return data
    },
  )
}

export function revoke(id: string): Promise<ApiResponse<null>> {
  return datasource(
    () => mockOk(null, 'Link revoked'),
    async () => {
      const { data } = await http.post<ApiResponse<null>>(`/shares/${id}/revoke`)
      return data
    },
  )
}

/**
 * GET /api/shared/{token} — PUBLIC. Must work signed-out: the browser app's
 * /shared/:token route is outside the auth guard (Phase 010).
 */
export function resolve(token: string): Promise<ApiResponse<SharedNotebookView>> {
  return datasource(
    () => Promise.reject(new Error('Mock: share resolution needs the live API')),
    async () => {
      const { data } = await http.get<ApiResponse<SharedNotebookView>>(`/shared/${token}`)
      return data
    },
  )
}
