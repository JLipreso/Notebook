import type { ApiResponse, AttachmentKind, FileUpload, PageAttachment } from '@notebook/types'

import { datasource } from './datasource'
import { http } from './http'
import { mockOk, nowIso } from './mock'

// Attachments (Phase 008). The ROW syncs offline; the FILE uploads when online
// (schema §2.5), which is why upload() and attach() are separate calls.

export interface AttachPayload {
  /** Client-minted UUIDv7 (D-013). */
  id: string
  kind: AttachmentKind
  file_upload_id?: string | null
  local_ref?: string | null
}

/** POST /api/files — multipart. Quota + content-based MIME check server-side. */
export function upload(file: File, kind: AttachmentKind | 'cover'): Promise<ApiResponse<FileUpload>> {
  return datasource(
    () =>
      mockOk({
        id: `mock-upload-${Date.now()}`,
        user_id: 'mock-user',
        disk: 'public',
        path: `uploads/mock/${file.name}`,
        original_name: file.name,
        mime_type: file.type,
        size_bytes: file.size,
        sha256: null,
        created_at: nowIso(),
        updated_at: nowIso(),
      } satisfies FileUpload),
    async () => {
      const form = new FormData()
      form.append('file', file)
      form.append('kind', kind)
      const { data } = await http.post<ApiResponse<FileUpload>>('/files', form, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      return data
    },
  )
}

export function attach(pageId: string, payload: AttachPayload): Promise<ApiResponse<PageAttachment>> {
  return datasource(
    () =>
      mockOk({
        id: payload.id,
        page_id: pageId,
        file_upload_id: payload.file_upload_id ?? null,
        kind: payload.kind,
        local_ref: payload.local_ref ?? null,
        upload_status: payload.file_upload_id ? 'uploaded' : 'pending',
        client_updated_at: nowIso(),
        created_at: nowIso(),
        updated_at: nowIso(),
        deleted_at: null,
      } satisfies PageAttachment),
    async () => {
      const { data } = await http.post<ApiResponse<PageAttachment>>(`/pages/${pageId}/attachments`, payload)
      return data
    },
  )
}

/** SOFT delete: the row goes, the file never does (it may be referenced again). */
export function detach(id: string): Promise<ApiResponse<null>> {
  return datasource(
    () => mockOk(null, 'Attachment removed'),
    async () => {
      const { data } = await http.delete<ApiResponse<null>>(`/attachments/${id}`)
      return data
    },
  )
}
