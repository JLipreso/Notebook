// THE single mock↔API switch point (CLAUDE.md §3 golden rule).
// This is the ONLY module allowed to read VITE_USE_MOCK and the ONLY importer
// of ./mock. Apps import services; services ask this module which source to use.
//
// Keeping this rule is what makes the backend swap a one-flag change instead of
// a rewrite. If you find yourself importing ./mock anywhere else, stop.

export const useMock: boolean = import.meta.env.VITE_USE_MOCK === 'true'

/**
 * Pick the mock or live implementation for one service call.
 *
 * Every service method is written as `datasource(() => mockFn(), () => liveFn())`
 * so the branch is identical everywhere and the call sites stay one line.
 */
export function datasource<T>(mock: () => Promise<T>, live: () => Promise<T>): Promise<T> {
  return useMock ? mock() : live()
}
