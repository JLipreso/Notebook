import type { PageContent, PageNode } from '@notebook/types'

/**
 * Flatten a Tiptap document to plain text.
 *
 * ============================ TWO-SIDED CONTRACT ============================
 * This mirrors App\Support\TiptapText::flatten() on the backend (Phase 007),
 * which maintains notebook_pages.search_text on every write. Both must produce
 * the SAME string for the same document, or client-side offline search and
 * server-side search silently disagree.
 * ===========================================================================
 */
export function flattenPageContent(content: PageContent | null | undefined): string {
  if (!content?.content) return ''
  return collect(content.content).join(' ').replace(/\s+/g, ' ').trim()
}

function collect(nodes: PageNode[]): string[] {
  const out: string[] = []

  for (const node of nodes) {
    if (typeof node.text === 'string') out.push(node.text)
    if (node.content?.length) out.push(...collect(node.content))
  }

  return out
}

/** Case- and accent-insensitive match against a page's search_text. */
export function matchesSearch(searchText: string | null | undefined, query: string): boolean {
  const needle = normalize(query)
  if (needle === '') return true
  return normalize(searchText ?? '').includes(needle)
}

function normalize(value: string): string {
  return value
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim()
}
