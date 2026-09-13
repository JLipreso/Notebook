import type { ApiResponse, User } from '@notebook/types'

import { datasource } from './datasource'
import { http } from './http'
import { mockOk, mockUser } from './mock'

// Profile (Phase 005). Email and role are NOT editable here — role never comes
// from the client after creation.

export type UpdateProfilePayload = Partial<
  Pick<
    User,
    | 'first_name'
    | 'last_name'
    | 'middle_name'
    | 'birthday'
    | 'mobile_number'
    | 'barangay_code'
    | 'address_line'
    | 'avatar_path'
  >
>

export function get(): Promise<ApiResponse<User>> {
  return datasource(
    () => mockOk(mockUser),
    async () => {
      const { data } = await http.get<ApiResponse<User>>('/profile')
      return data
    },
  )
}

export function update(payload: UpdateProfilePayload): Promise<ApiResponse<User>> {
  return datasource(
    () => {
      Object.assign(mockUser, payload, { updated_at: new Date().toISOString() })
      return mockOk(mockUser, 'Profile updated')
    },
    async () => {
      const { data } = await http.put<ApiResponse<User>>('/profile', payload)
      return data
    },
  )
}
