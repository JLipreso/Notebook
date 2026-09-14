import { computed, ref } from 'vue'
import { notificationService } from '@notebook/services'
import type { AppNotification } from '@notebook/types'
import { extractErrors, type AuthFormErrors } from '../auth/useAuthForm'

/**
 * In-app notifications (Phase 010).
 *
 * Only `welcome` fires in M1. The plumbing is here because M2's invitations and
 * M3's payment confirmations reuse it unchanged — they add a `type`, not a UI.
 */
export function useNotifications() {
  const notifications = ref<AppNotification[]>([])
  const unread = ref(0)
  const page = ref(1)
  const lastPage = ref(1)
  const loading = ref(false)
  const errors = ref<AuthFormErrors>({ message: null, fields: null })

  const hasMore = computed(() => page.value < lastPage.value)

  async function load(nextPage = 1): Promise<void> {
    loading.value = true
    errors.value = { message: null, fields: null }

    try {
      const response = await notificationService.list(nextPage)
      const rows = response.data ?? []

      notifications.value = nextPage === 1 ? rows : [...notifications.value, ...rows]
      page.value = response.meta?.current_page ?? nextPage
      lastPage.value = response.meta?.last_page ?? nextPage

      await refreshCount()
    } catch (error) {
      errors.value = extractErrors(error)
    } finally {
      loading.value = false
    }
  }

  async function loadMore(): Promise<void> {
    if (hasMore.value && !loading.value) await load(page.value + 1)
  }

  async function refreshCount(): Promise<void> {
    try {
      const { data } = await notificationService.unreadCount()
      unread.value = data ?? 0
    } catch {
      // A failed badge count is not worth surfacing — the list still works.
    }
  }

  async function markRead(id: string): Promise<void> {
    const notification = notifications.value.find((n) => n.id === id)
    if (!notification || notification.read_at) return

    // Optimistic: the badge should drop the instant it is tapped.
    notification.read_at = new Date().toISOString()
    unread.value = Math.max(0, unread.value - 1)

    try {
      await notificationService.markRead(id)
    } catch (error) {
      notification.read_at = null
      unread.value += 1
      errors.value = extractErrors(error)
    }
  }

  async function markAllRead(): Promise<void> {
    const now = new Date().toISOString()
    notifications.value.forEach((n) => {
      if (!n.read_at) n.read_at = now
    })
    unread.value = 0

    try {
      await notificationService.markAllRead()
    } catch (error) {
      errors.value = extractErrors(error)
      await load(1)
    }
  }

  /**
   * The deep-link a notification carries. `{ route, params }` is the shape
   * every later feature must emit (schema §5) — defined here so M2 and M3
   * inherit it rather than inventing one each.
   */
  function destinationFor(notification: AppNotification): { name: string; params?: Record<string, unknown> } | null {
    const route = notification.data?.route
    if (typeof route !== 'string') return null

    return { name: route, params: notification.data?.params }
  }

  return {
    notifications,
    unread,
    loading,
    errors,
    hasMore,
    load,
    loadMore,
    refreshCount,
    markRead,
    markAllRead,
    destinationFor,
  }
}
