// @notebook/services — one service per domain, each routing through
// datasource.ts (mock) or http.ts (Laravel). Views and composables import from
// here and never know which source answered (CLAUDE.md §3 golden rule).

export { http, setBearerToken, getBearerToken, setUnauthorizedHandler } from './http'
export { useMock, datasource } from './datasource'

export * as authService from './auth.service'
export * as addressService from './address.service'
export * as profileService from './profile.service'
export * as notebookService from './notebook.service'
export * as pageService from './page.service'
export * as attachmentService from './attachment.service'
export * as shareService from './share.service'
export * as notificationService from './notification.service'
export * as syncService from './sync.service'

export type { AuthSession, SignUpProfile } from './auth.service'
export type { NotebookFilters, CreateNotebookPayload, UpdateNotebookPayload } from './notebook.service'
export type { CreatePagePayload, UpdatePagePayload } from './page.service'
export type { UpdateProfilePayload } from './profile.service'
export type { AttachPayload } from './attachment.service'
export type { CreateSharePayload } from './share.service'
