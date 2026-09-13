// Identity & devices — mirrors backend/database/migrations/0001_01_01_000000
// (database-schema.md §4, tables 1-2). D-014, D-015.

/** One users table for every role (D-014); a role upgrade is an UPDATE. */
export type UserRole = 'student' | 'teacher' | 'admin'

export type UserStatus = 'active' | 'disabled'

export interface User {
  id: string
  role: UserRole
  first_name: string
  last_name: string
  middle_name: string | null
  /** ISO date, no time component (DATE column). */
  birthday: string
  email: string
  email_verified_at: string | null
  mobile_number: string
  /** PSGC barangay; region/province/city are derivable by joins (D-020: no geolocation). */
  barangay_code: string | null
  address_line: string | null
  avatar_path: string | null
  status: UserStatus
  created_at: string
  updated_at: string
  deleted_at: string | null
}

// firebase_uid and password are deliberately absent: both are $hidden on the
// backend model and never reach the client (D-015).

export type DevicePlatform = 'android' | 'ios' | 'web'

export interface UserDevice {
  id: string
  user_id: string
  platform: DevicePlatform
  device_identifier: string
  device_name: string | null
  fcm_token: string | null
  last_seen_at: string | null
  created_at: string
  updated_at: string
}
