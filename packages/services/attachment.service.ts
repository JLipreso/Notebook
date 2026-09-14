import type {
  ApiResponse,
  AttachmentKind,
  FileUploadWithUrl,
  PageAttachmentWithFile,
} from '@notebook/types'

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
export function upload(
  file: File,
  kind: AttachmentKind | 'cover',
): Promise<ApiResponse<FileUploadWithUrl>> {
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
        url: URL.createObjectURL(file),
      } satisfies FileUploadWithUrl),
    async () => {
      const form = new FormData()
      form.append('file', file)
      form.append('kind', kind)
      const { data } = await http.post<ApiResponse<FileUploadWithUrl>>('/files', form, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      return data
    },
  )
}

export function attach(
  pageId: string,
  payload: AttachPayload,
): Promise<ApiResponse<PageAttachmentWithFile>> {
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
        file_upload: null,
      } satisfies PageAttachmentWithFile),
    async () => {
      const { data } = await http.post<ApiResponse<PageAttachmentWithFile>>(
        `/pages/${pageId}/attachments`,
        payload,
      )
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

/** GET /api/pages/{page}/attachments — rows with their served file URLs. */
export function listByPage(pageId: string): Promise<ApiResponse<PageAttachmentWithFile[]>> {
  return datasource(
    () => mockOk([] as PageAttachmentWithFile[]),
    async () => {
      const { data } = await http.get<ApiResponse<PageAttachmentWithFile[]>>(
        `/pages/${pageId}/attachments`,
      )
      return data
    },
  )
}

/** GET /api/files/usage — what the quota indicator needs. */
export function usage(): Promise<ApiResponse<{ used_bytes: number; quota_bytes: number; quota_mb: number }>> {
  return datasource(
    () => mockOk({ used_bytes: 0, quota_bytes: 500 * 1024 * 1024, quota_mb: 500 }),
    async () => {
      const { data } = await http.get<ApiResponse<{ used_bytes: number; quota_bytes: number; quota_mb: number }>>(
        '/files/usage',
      )
      return data
    },
  )
}
