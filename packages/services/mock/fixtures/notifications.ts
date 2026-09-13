import type { AppNotification } from '@notebook/types'

import { mockUser } from './users'

// The welcome note seeded at registration (Phase 004/010) plus one share event,
// so the bell has a read and an unread row to render.

export const mockNotifications: AppNotification[] = [
  {
    id: '01930000-0000-7000-8000-000000000401',
    user_id: mockUser.id,
    actor_user_id: null,
    type: 'welcome',
    title: 'Welcome to Notebook',
    body: 'Your notebooks are yours forever. Start by creating your first one.',
    data: { route: 'library' },
    read_at: null,
    created_at: '2026-09-01T02:00:00.000Z',
    updated_at: '2026-09-01T02:00:00.000Z',
  },
  {
    id: '01930000-0000-7000-8000-000000000402',
    user_id: mockUser.id,
    actor_user_id: null,
    type: 'share_received',
    title: 'A notebook was shared with you',
    body: 'English Notes is now viewable through a shared link.',
    data: { route: 'notebook', params: { id: '01930000-0000-7000-8000-000000000201' } },
    read_at: '2026-09-02T06:30:00.000Z',
    created_at: '2026-09-02T06:00:00.000Z',
    updated_at: '2026-09-02T06:30:00.000Z',
  },
]
