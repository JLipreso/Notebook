// Mock db — JSON fixtures + latency shim for pre-backend UI work.
// Imported ONLY by ../datasource.ts. Fixtures land with the first M1 views.

export async function withLatency<T>(value: T, ms = 250): Promise<T> {
  await new Promise((resolve) => setTimeout(resolve, ms))
  return value
}
