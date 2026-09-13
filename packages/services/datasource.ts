// THE single mock↔API switch point (CLAUDE.md §3 golden rule).
// This is the ONLY module allowed to read VITE_USE_MOCK and the ONLY importer
// of ./mock. Apps import services; services ask this module which source to use.

export const useMock: boolean = import.meta.env.VITE_USE_MOCK === 'true'
