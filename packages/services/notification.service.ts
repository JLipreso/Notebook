import type { ApiResponse, AppNotification, PaginatedResponse } from '@notebook/types'

import { datasource } from './datasource'
import { http } from './http'
import { db, mockOk, mockPaginated, nowIso } from './mock'

// Notifications (Phase 010). The list endpoint is PAGINATED — note the
// different envelope.

export function list(page = 1): Promise<PaginatedResponse<AppNotification>> {
  return datasource(
    () =>
      mockPaginated(
        [...db.notifications].sort((a, b) => b.created_at.localeCompare(a.created_at)),
        page,
      ),
    async () => {
      const { data } = await http.get<PaginatedResponse<AppNotification>>('/notifications', {
        params: { page },
      })
      return data
    },
  )
}

export function unreadCount(): Promise<ApiResponse<number>> {
  return datasource(
    () => mockOk(db.notifications.filter((n) => n.read_at === null).length),
    async () => {
      const { data } = await http.get<ApiResponse<number>>('/notifications/unread-count')
      return data
    },
  )
}

export function markRead(id: string): Promise<ApiResponse<null>> {
  return datasource(
    () => {
      const notification = db.notifications.find((n) => n.id === id)
      if (notification) notification.read_at = nowIso()
      return mockOk(null, 'Marked read')
    },
    async () => {
      const { data } = await http.post<ApiResponse<null>>(`/notifications/${id}/read`)
      return data
    },
  )
}

export function markAllRead(): Promise<ApiResponse<null>> {
  return datasource(
    () => {
      const now = nowIso()
      db.notifications.forEach((n) => {
        if (n.read_at === null) n.read_at = now
      })
      return mockOk(null, 'All marked read')
    },
    async () => {
      const { data } = await http.post<ApiResponse<null>>('/notifications/read-all')
      return data
    },
  )
}
