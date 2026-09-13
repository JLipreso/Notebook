import type { NotebookPage, PageContent, PageNode } from '@notebook/types'

// Real Tiptap documents — every node here is in ALLOWED_NODES (@notebook/types).
// Phase 007's editor mounts these directly, so anything invalid would surface
// as a silent drop rather than a type error.

const T = '2026-09-01T02:00:00.000Z'

function doc(...content: PageNode[]): PageContent {
  return { type: 'doc', content }
}

function paragraph(text: string): PageNode {
  return { type: 'paragraph', content: [{ type: 'text', text }] }
}

function heading(text: string, level = 1): PageNode {
  return { type: 'heading', attrs: { level }, content: [{ type: 'text', text }] }
}

function bulletList(...items: string[]): PageNode {
  return {
    type: 'bulletList',
    content: items.map((text) => ({
      type: 'listItem',
      content: [paragraph(text)],
    })),
  }
}

export const mockPages: NotebookPage[] = [
  {
    id: '01930000-0000-7000-8000-000000000301',
    notebook_id: '01930000-0000-7000-8000-000000000201',
    position: 0,
    title: 'Parts of Speech',
    content: doc(
      heading('Parts of Speech'),
      paragraph('There are eight parts of speech in English.'),
      bulletList('Noun — a person, place or thing', 'Verb — an action word', 'Adjective — describes a noun'),
    ),
    search_text: 'Parts of Speech There are eight parts of speech in English. Noun Verb Adjective',
    client_updated_at: T,
    created_at: T,
    updated_at: T,
    deleted_at: null,
  },
  {
    id: '01930000-0000-7000-8000-000000000302',
    notebook_id: '01930000-0000-7000-8000-000000000201',
    position: 1,
    title: 'Reading Notes',
    content: doc(
      paragraph('Chapter 1 introduces the main character and the setting.'),
      paragraph('The story takes place in a small coastal town.'),
    ),
    search_text: 'Chapter 1 introduces the main character and the setting. The story takes place in a small coastal town.',
    client_updated_at: T,
    created_at: T,
    updated_at: T,
    deleted_at: null,
  },
  {
    id: '01930000-0000-7000-8000-000000000303',
    notebook_id: '01930000-0000-7000-8000-000000000201',
    position: 2,
    title: null,
    content: doc(paragraph('')),
    search_text: '',
    client_updated_at: T,
    created_at: T,
    updated_at: T,
    deleted_at: null,
  },
  {
    id: '01930000-0000-7000-8000-000000000304',
    notebook_id: '01930000-0000-7000-8000-000000000202',
    position: 0,
    title: 'Aa Bb Cc',
    content: doc(paragraph('Aa Bb Cc Dd Ee'), paragraph('The quick brown fox jumps over the lazy dog.')),
    search_text: 'Aa Bb Cc Dd Ee The quick brown fox jumps over the lazy dog.',
    client_updated_at: T,
    created_at: T,
    updated_at: T,
    deleted_at: null,
  },
  {
    id: '01930000-0000-7000-8000-000000000305',
    notebook_id: '01930000-0000-7000-8000-000000000202',
    position: 1,
    title: 'Cursive Practice',
    content: doc(paragraph('Practice writing on the guide lines.')),
    search_text: 'Practice writing on the guide lines.',
    client_updated_at: T,
    created_at: T,
    updated_at: T,
    deleted_at: null,
  },
  {
    id: '01930000-0000-7000-8000-000000000306',
    notebook_id: '01930000-0000-7000-8000-000000000203',
    position: 0,
    title: 'Week 1 Hours',
    content: doc(
      heading('Week 1', 2),
      // Timesheet's default_blocks preset is ['table'] — this mirrors it.
      {
        type: 'table',
        content: [
          {
            type: 'tableRow',
            content: [
              { type: 'tableHeader', content: [paragraph('Day')] },
              { type: 'tableHeader', content: [paragraph('Hours')] },
            ],
          },
          {
            type: 'tableRow',
            content: [
              { type: 'tableCell', content: [paragraph('Monday')] },
              { type: 'tableCell', content: [paragraph('4')] },
            ],
          },
        ],
      },
    ),
    search_text: 'Week 1 Day Hours Monday 4',
    client_updated_at: T,
    created_at: '2025-06-02T02:00:00.000Z',
    updated_at: '2025-06-02T02:00:00.000Z',
    deleted_at: null,
  },
]
