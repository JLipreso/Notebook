// @notebook/services — one service per domain, each routing through
// datasource.ts (mock) or http.ts (Laravel). Domain services
// (auth, notebook, page, share, notification, address) land with M1 implementation.

export { http, setBearerToken } from './http'
export { useMock } from './datasource'
