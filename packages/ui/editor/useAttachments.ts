import { ref } from 'vue'
import { attachmentService } from '@notebook/services'
import { mintId } from '@notebook/sync'
import type { AttachmentKind, PageAttachmentWithFile } from '@notebook/types'
import { extractErrors, type AuthFormErrors } from '../auth/useAuthForm'

/**
 * Page attachments (Phase 008).
 *
 * Upload THEN attach, in that order: the row carries `file_upload_id`, so the
 * bytes must land first. Phase 009 inverts this for the offline path — row
 * first with `upload_status: 'pending'`, bytes when a connection returns — and
 * that is exactly why the two calls are separate rather than one endpoint.
 */
export function useAttachments() {
  const attachments = ref<PageAttachmentWithFile[]>([])
  const uploading = ref(false)
  const errors = ref<AuthFormErrors>({ message: null, fields: null })

  async function load(pageId: string): Promise<void> {
    try {
      const { data } = await attachmentService.listByPage(pageId)
      attachments.value = data ?? []
    } catch (error) {
      errors.value = extractErrors(error)
    }
  }

  /**
   * Upload a file and attach it to the page.
   *
   * @returns the served URL, so an image can be inserted into the editor.
   */
  async function attachFile(
    pageId: string,
    file: File,
    kind: AttachmentKind,
  ): Promise<string | null> {
    uploading.value = true
    errors.value = { message: null, fields: null }

    try {
      const uploaded = await attachmentService.upload(file, kind)
      const upload = uploaded.data
      if (!upload) return null

      const { data } = await attachmentService.attach(pageId, {
        // Client-minted (D-013) so the offline path uses the same shape.
        id: mintId(),
        kind,
        file_upload_id: upload.id,
      })

      if (data) attachments.value.push(data)

      return upload.url ?? null
    } catch (error) {
      errors.value = extractErrors(error)
      return null
    } finally {
      uploading.value = false
    }
  }

  /** Removes the ROW. The file is never deleted — another row may use it. */
  async function detach(id: string): Promise<void> {
    try {
      await attachmentService.detach(id)
      attachments.value = attachments.value.filter((a) => a.id !== id)
    } catch (error) {
      errors.value = extractErrors(error)
    }
  }

  /** Only image/pdf are user-attachable in M1; 'document' is schema-reserved. */
  function kindFor(file: File): AttachmentKind | null {
    if (file.type.startsWith('image/')) return 'image'
    if (file.type === 'application/pdf') return 'pdf'
    return null
  }

  return { attachments, uploading, errors, load, attachFile, detach, kindFor }
}
