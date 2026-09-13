import type { ApiResponse, User, UserDevice } from '@notebook/types'

import { datasource } from './datasource'
import { http } from './http'
import { mockOk, mockUser, mockBearerToken } from './mock'

// Firebase → Sanctum (D-015). The Firebase half lives in ./firebase.ts and is
// wired in Phase 004; this module only knows about the exchange endpoint.

export interface AuthSession {
  token: string
  user: User
}

/** Profile fields required on FIRST sign-up (the brief's required set). */
export interface SignUpProfile {
  first_name: string
  last_name: string
  middle_name?: string | null
  birthday: string
  mobile_number: string
  /** Only student|teacher may self-register; admin is seeded. */
  role: 'student' | 'teacher'
}

/**
 * POST /api/auth/firebase — verify the Firebase ID token, create or link the
 * user, return a Sanctum bearer. `profile` is required only on first sign-up.
 */
export function exchangeFirebaseToken(
  idToken: string,
  profile?: SignUpProfile,
): Promise<ApiResponse<AuthSession>> {
  return datasource(
    () => mockOk({ token: mockBearerToken, user: mockUser }),
    async () => {
      const { data } = await http.post<ApiResponse<AuthSession>>('/auth/firebase', {
        id_token: idToken,
        ...profile,
      })
      return data
    },
  )
}

/** GET /api/user — the caller behind the bearer. */
export function me(): Promise<ApiResponse<User>> {
  return datasource(
    () => mockOk(mockUser),
    async () => {
      const { data } = await http.get<ApiResponse<User>>('/user')
      return data
    },
  )
}

/** POST /api/auth/logout — revoke the current token. */
export function logout(): Promise<ApiResponse<null>> {
  return datasource(
    () => mockOk(null, 'Logged out'),
    async () => {
      const { data } = await http.post<ApiResponse<null>>('/auth/logout')
      return data
    },
  )
}

/** POST /api/auth/device — upsert this device for push targeting. */
export function registerDevice(payload: {
  platform: UserDevice['platform']
  device_identifier: string
  device_name?: string | null
  fcm_token?: string | null
}): Promise<ApiResponse<UserDevice>> {
  return datasource(
    () =>
      mockOk({
        id: 'mock-device',
        user_id: mockUser.id,
        platform: payload.platform,
        device_identifier: payload.device_identifier,
        device_name: payload.device_name ?? null,
        fcm_token: payload.fcm_token ?? null,
        last_seen_at: new Date().toISOString(),
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
      } satisfies UserDevice),
    async () => {
      const { data } = await http.post<ApiResponse<UserDevice>>('/auth/device', payload)
      return data
    },
  )
}
