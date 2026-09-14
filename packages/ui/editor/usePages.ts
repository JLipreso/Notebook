import { computed, ref } from 'vue'
import { notebookService, pageService } from '@notebook/services'
import { mintId } from '@notebook/sync'
import { flattenPageContent } from '@notebook/utility'
import type { Notebook, NotebookPage, NotebookType, PageContent } from '@notebook/types'
import { extractErrors, type AuthFormErrors } from '../auth/useAuthForm'

/** Autosave debounce. Long enough not to spam, short enough to feel safe. */
const AUTOSAVE_MS = 800

export type SaveState = 'idle' | 'dirty' | 'saving' | 'saved' | 'error'

/**
 * One notebook's pages, with debounced autosave (Phase 007).
 *
 * Every write goes through `pageService` — the SAME call shape Phase 009 will
 * reroute through the outbox. This composable must stay ignorant of transport:
 * if it ever learns whether it is online, offline sync gets harder, not easier.
 */
export function usePages(notebookId: string) {
  const notebook = ref<Notebook | null>(null)
  const type = ref<NotebookType | null>(null)
  const pages = ref<NotebookPage[]>([])

  const currentIndex = ref(0)
  const loading = ref(false)
  const saveState = ref<SaveState>('idle')
  const errors = ref<AuthFormErrors>({ message: null, fields: null })

  const current = computed<NotebookPage | null>(() => pages.value[currentIndex.value] ?? null)
  const pageCount = computed(() => pages.value.length)

  let timer: ReturnType<typeof setTimeout> | null = null
  /** Ids with edits not yet flushed — guards against navigating away mid-save. */
  const pending = new Set<string>()

  async function load(): Promise<void> {
    loading.value = true
    errors.value = { message: null, fields: null }

    try {
      const [notebookResult, typeResult, pageResult] = await Promise.all([
        notebookService.list(),
        notebookService.types(),
        pageService.listByNotebook(notebookId),
      ])

      notebook.value = (notebookResult.data ?? []).find((n) => n.id === notebookId) ?? null
      type.value =
        (typeResult.data ?? []).find((t) => t.id === notebook.value?.notebook_type_id) ?? null
      pages.value = pageResult.data ?? []

      // A notebook with no pages gets one, so the student never faces a void.
      if (pages.value.length === 0) await addPage()
    } catch (error) {
      errors.value = extractErrors(error)
    } finally {
      loading.value = false
    }
  }

  /** Called on every keystroke: updates locally, schedules the save. */
  function onContentChange(content: PageContent): void {
    const page = current.value
    if (!page) return

    page.content = content
    // Keep the local mirror searchable immediately — the server recomputes it
    // authoritatively on write, using the PHP mirror of this same function.
    page.search_text = flattenPageContent(content)

    pending.add(page.id)
    saveState.value = 'dirty'

    if (timer) clearTimeout(timer)
    timer = setTimeout(() => void flush(), AUTOSAVE_MS)
  }

  /** Push every pending page. Safe to call directly (e.g. before unmount). */
  async function flush(): Promise<void> {
    if (timer) {
      clearTimeout(timer)
      timer = null
    }

    if (pending.size === 0) return

    const ids = [...pending]
    pending.clear()
    saveState.value = 'saving'

    try {
      for (const id of ids) {
        const page = pages.value.find((p) => p.id === id)
        if (!page) continue

        await pageService.update(id, { content: page.content, title: page.title })
      }

      saveState.value = 'saved'
    } catch (error) {
      // Put them back so the next flush retries rather than losing the edit.
      ids.forEach((id) => pending.add(id))
      errors.value = extractErrors(error)
      saveState.value = 'error'
    }
  }

  async function addPage(): Promise<void> {
    try {
      const { data } = await pageService.create(notebookId, {
        // Client-minted (D-013) so the same path works offline later.
        id: mintId(),
        position: pages.value.length,
        content: { type: 'doc', content: [{ type: 'paragraph' }] },
      })

      if (data) {
        pages.value.push(data)
        currentIndex.value = pages.value.length - 1
      }
    } catch (error) {
      errors.value = extractErrors(error)
    }
  }

  async function removePage(id: string): Promise<void> {
    try {
      await pageService.remove(id)
      pages.value = pages.value.filter((p) => p.id !== id)
      pending.delete(id)

      if (currentIndex.value >= pages.value.length) {
        currentIndex.value = Math.max(0, pages.value.length - 1)
      }

      if (pages.value.length === 0) await addPage()
    } catch (error) {
      errors.value = extractErrors(error)
    }
  }

  /** Flush the current page before moving — never lose an edit to navigation. */
  async function goTo(index: number): Promise<void> {
    if (index < 0 || index >= pages.value.length) return
    await flush()
    currentIndex.value = index
  }

  const next = () => goTo(currentIndex.value + 1)
  const previous = () => goTo(currentIndex.value - 1)

  const saveLabel = computed(() => {
    switch (saveState.value) {
      case 'dirty':
        return 'Unsaved changes'
      case 'saving':
        return 'Saving…'
      case 'saved':
        return 'All changes saved'
      case 'error':
        return 'Could not save — will retry'
      default:
        return ''
    }
  })

  return {
    notebook,
    type,
    pages,
    current,
    currentIndex,
    pageCount,
    loading,
    saveState,
    saveLabel,
    errors,
    load,
    onContentChange,
    flush,
    addPage,
    removePage,
    goTo,
    next,
    previous,
  }
}
