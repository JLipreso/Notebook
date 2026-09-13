import type { User } from '@notebook/types'

/** The signed-in student every mock-mode session runs as. */
export const mockUser: User = {
  id: '01930000-0000-7000-8000-000000000001',
  role: 'student',
  first_name: 'Maria',
  last_name: 'Santos',
  middle_name: 'Cruz',
  birthday: '2012-06-14',
  email: 'maria.santos@example.com',
  email_verified_at: '2026-09-01T02:00:00.000Z',
  mobile_number: '09171234567',
  barangay_code: '0421005001',
  address_line: '12 Mabini Street',
  avatar_path: null,
  status: 'active',
  created_at: '2026-09-01T02:00:00.000Z',
  updated_at: '2026-09-01T02:00:00.000Z',
  deleted_at: null,
}

export const mockBearerToken = 'mock-sanctum-token'
