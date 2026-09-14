import { ref } from 'vue'
import { shareService } from '@notebook/services'
import type { NotebookShareWithStatus } from '@notebook/types'
import { extractErrors, type AuthFormErrors } from '../auth/useAuthForm'

/**
 * Share links for one notebook (Phase 010). ONLINE-ONLY (D-016) — there is no
 * local-store branch here on purpose: a link the server has never seen cannot
 * be opened by anyone, so creating one offline would be a lie.
 */
export function useShares(notebookId: string) {
  const shares = ref<NotebookShareWithStatus[]>([])
  const loading = ref(false)
  const creating = ref(false)
  const errors = ref<AuthFormErrors>({ message: null, fields: null })

  async function load(): Promise<void> {
    loading.value = true
    errors.value = { message: null, fields: null }

    try {
      const { data } = await shareService.list(notebookId)
      shares.value = data ?? []
    } catch (error) {
      errors.value = extractErrors(error)
    } finally {
      loading.value = false
    }
  }

  async function create(payload: {
    page_id?: string | null
    expires_at?: string | null
  }): Promise<NotebookShareWithStatus | null> {
    creating.value = true
    errors.value = { message: null, fields: null }

    try {
      const { data } = await shareService.create({
        notebook_id: notebookId,
        page_id: payload.page_id ?? null,
        expires_at: payload.expires_at ?? null,
      })

      if (data) {
        const created = data as NotebookShareWithStatus
        shares.value.unshift(created)
        return created
      }

      return null
    } catch (error) {
      errors.value = extractErrors(error)
      return null
    } finally {
      creating.value = false
    }
  }

  async function revoke(id: string): Promise<void> {
    try {
      await shareService.revoke(id)
      // Reflect the server's state without a round trip — the link is dead now.
      const share = shares.value.find((s) => s.id === id)
      if (share) {
        share.revoked_at = new Date().toISOString()
        share.is_revoked = true
        share.is_active = false
      }
    } catch (error) {
      errors.value = extractErrors(error)
    }
  }

  /** The URL a student actually pastes. */
  function linkFor(share: NotebookShareWithStatus): string {
    const origin = typeof window === 'undefined' ? '' : window.location.origin
    return `${origin}/shared/${share.share_token}`
  }

  return { shares, loading, creating, errors, load, create, revoke, linkFor }
}
