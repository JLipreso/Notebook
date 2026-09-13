import type { ApiResponse, PaginatedResponse } from '@notebook/types'

// Mock db — fixtures + an in-memory mutable copy + a latency shim, for
// pre-backend UI work. Imported ONLY by ../datasource.ts and the service
// modules' mock branches. Apps NEVER import this (CLAUDE.md §3).

export * from './fixtures'
export * from './db'

/** Every mock read goes through this so loading states are real in mock mode. */
export async function withLatency<T>(value: T, ms = 250): Promise<T> {
  await new Promise((resolve) => setTimeout(resolve, ms))
  return value
}

/** Wrap a value in the success envelope the backend would have sent. */
export async function mockOk<T>(data: T, message = 'Success'): Promise<ApiResponse<T>> {
  return withLatency({ success: true, message, data })
}

/** The paginated envelope, for services whose live path is paginated. */
export async function mockPaginated<T>(
  rows: T[],
  page = 1,
  perPage = 15,
): Promise<PaginatedResponse<T>> {
  const start = (page - 1) * perPage
  return withLatency({
    success: true,
    data: rows.slice(start, start + perPage),
    meta: {
      current_page: page,
      last_page: Math.max(1, Math.ceil(rows.length / perPage)),
      per_page: perPage,
      total: rows.length,
    },
  })
}
