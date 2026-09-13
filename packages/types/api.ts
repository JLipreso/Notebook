// The response envelopes every Laravel endpoint emits (CLAUDE.md §3, D-005).
// These two shapes are frozen — backend ApiController::success()/error()/paginated()
// produce exactly this, in every app, for every domain.

export interface ApiResponse<T = unknown> {
  success: boolean
  message: string
  data?: T
  errors?: Record<string, string[]>
}

export interface PaginatedResponse<T = unknown> {
  success: boolean
  data: T[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}
