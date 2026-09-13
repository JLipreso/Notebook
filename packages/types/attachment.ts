// Attachments & uploads — mirrors 0001_01_01_000006 / 000007
// (database-schema.md §5, tables 10-11). §2.5.

export type AttachmentKind = 'image' | 'pdf' | 'document'

/** Upload lifecycle: the ROW syncs offline, the binary does not (§2.5). */
export type UploadStatus = 'pending' | 'uploaded' | 'failed'

/**
 * [RW] offline-writable row. Created offline with upload_status='pending' and a
 * device-side `local_ref`; the uploader patches in `file_upload_id` and flips
 * the status once the file reaches the server (Phase 009).
 */
export interface PageAttachment {
  id: string
  page_id: string
  file_upload_id: string | null
  kind: AttachmentKind
  local_ref: string | null
  upload_status: UploadStatus
  client_updated_at: string | null
  created_at: string
  updated_at: string
  deleted_at: string | null
}

/** [ON] the stored file itself. Laravel disks only, never FTP (validation §5). */
export interface FileUpload {
  id: string
  user_id: string
  /** 'public' now, 's3' later — a config swap, not a migration. */
  disk: string
  path: string
  original_name: string | null
  mime_type: string
  size_bytes: number
  sha256: string | null
  created_at: string
  updated_at: string
}

/** Served URL for a stored file; the backend resolves it via Storage::url(). */
export interface FileUploadWithUrl extends FileUpload {
  url?: string
}

/** kind + file, as the attachment list renders it. */
export interface PageAttachmentWithFile extends PageAttachment {
  file_upload?: FileUploadWithUrl | null
}
