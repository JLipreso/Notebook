import { attachmentService } from '@notebook/services'
import type { PageAttachment } from '@notebook/types'
import type { StorageAdapter } from './storage/adapter'

/**
 * Upload files for attachment rows created offline (schema §2.5).
 *
 * Binary files are NOT synced as rows. A device creates the row with
 * upload_status 'pending' and a local_ref, then this walks those rows when a
 * connection returns, uploads the bytes, and patches in the server's
 * file_upload_id.
 *
 * `readFile` is injected rather than imported: @capacitor/filesystem only
 * exists in the native shell, and making this module import it would break the
 * web build and the tests. Phase 011 passes the real reader in.
 */
export type LocalFileReader = (localRef: string) => Promise<File | null>

export interface UploadOutcome {
  uploaded: number
  failed: number
}

export async function uploadPending(
  storage: StorageAdapter,
  readFile: LocalFileReader,
): Promise<UploadOutcome> {
  const rows = await storage.list<PageAttachment>('page_attachments')
  const outcome: UploadOutcome = { uploaded: 0, failed: 0 }

  const pending = rows.filter(
    (row) => row.upload_status === 'pending' && row.local_ref && !row.deleted_at,
  )

  for (const row of pending) {
    try {
      const file = await readFile(row.local_ref as string)

      if (!file) {
        // The local file is gone — mark failed rather than retrying forever.
        await storage.put('page_attachments', { ...row, upload_status: 'failed' })
        outcome.failed += 1
        continue
      }

      const kind = row.kind === 'pdf' || row.kind === 'document' ? row.kind : 'image'
      const { data: upload } = await attachmentService.upload(file, kind)

      if (!upload) {
        await storage.put('page_attachments', { ...row, upload_status: 'failed' })
        outcome.failed += 1
        continue
      }

      // Patch the row and mark it dirty, so the next push carries the
      // file_upload_id to the server.
      await storage.put('page_attachments', {
        ...row,
        file_upload_id: upload.id,
        upload_status: 'uploaded',
        client_updated_at: new Date().toISOString(),
      })

      outcome.uploaded += 1
    } catch {
      // A transient failure (offline mid-run) stays 'failed' and is retried on
      // the next sync — the row is not lost either way.
      await storage.put('page_attachments', { ...row, upload_status: 'failed' })
      outcome.failed += 1
    }
  }

  return outcome
}

/** Rows that failed earlier become eligible again on the next run. */
export async function requeueFailed(storage: StorageAdapter): Promise<number> {
  const rows = await storage.list<PageAttachment>('page_attachments')
  const failed = rows.filter((row) => row.upload_status === 'failed' && row.local_ref)

  for (const row of failed) {
    await storage.putClean('page_attachments', { ...row, upload_status: 'pending' })
  }

  return failed.length
}
