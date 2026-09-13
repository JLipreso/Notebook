// In-app notifications — mirrors 0001_01_01_000009
// (database-schema.md §5, table 13). ONE table for every role (improvement #3).

/** Open set by design — M2/M3 add types without a schema change. */
export type NotificationType =
  | 'welcome'
  | 'share_received'
  | 'invite_received'
  | 'invite_answered'
  | 'quiz_submitted'
  | 'payment_verified'
  | (string & {})

/** Deep-link payload the UI follows when a notification is tapped (Phase 010). */
export interface NotificationData {
  route?: string
  params?: Record<string, string | number>
  [key: string]: unknown
}

/**
 * Named AppNotification, not Notification — the DOM already owns that name.
 * The backend model dodges the same collision (Illuminate\Notifications).
 */
export interface AppNotification {
  id: string
  user_id: string
  actor_user_id: string | null
  type: NotificationType
  title: string
  body: string | null
  data: NotificationData | null
  read_at: string | null
  created_at: string
  updated_at: string
}
