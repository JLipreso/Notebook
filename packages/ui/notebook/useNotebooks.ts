import { computed, ref } from 'vue'
import { notebookService } from '@notebook/services'
import { mintId } from '@notebook/sync'
import type { Notebook, NotebookType } from '@notebook/types'
import { extractErrors, type AuthFormErrors } from '../auth/useAuthForm'

/**
 * The shelf's state (Phase 006). Per-view state lives in a composable, NOT
 * Pinia — the auth store stays auth-only (CLAUDE.md §3).
 */
export function useNotebooks() {
  const notebooks = ref<Notebook[]>([])
  const types = ref<NotebookType[]>([])

  const loading = ref(false)
  const saving = ref(false)
  const errors = ref<AuthFormErrors>({ message: null, fields: null })

  const schoolYearFilter = ref<string | null>(null)

  const active = computed(() =>
    notebooks.value
      .filter((n) => n.status === 'active')
      .filter((n) => !schoolYearFilter.value || n.school_year === schoolYearFilter.value),
  )

  const archived = computed(() => notebooks.value.filter((n) => n.status === 'archived'))

  /** Every school year present, newest first — drives the filter control. */
  const schoolYears = computed(() =>
    [...new Set(notebooks.value.map((n) => n.school_year))].sort((a, b) => b.localeCompare(a)),
  )

  function typeFor(notebook: Notebook): NotebookType | null {
    return types.value.find((t) => t.id === notebook.notebook_type_id) ?? null
  }

  async function load(): Promise<void> {
    loading.value = true
    errors.value = { message: null, fields: null }

    try {
      const [listResult, typeResult] = await Promise.all([
        notebookService.list(),
        notebookService.types(),
      ])
      notebooks.value = listResult.data ?? []
      types.value = typeResult.data ?? []
    } catch (error) {
      errors.value = extractErrors(error)
    } finally {
      loading.value = false
    }
  }

  /**
   * The CLIENT mints the id (D-013) and the server stores it verbatim, so this
   * exact call shape still works when Phase 009 reroutes it through the outbox.
   */
  async function create(input: {
    notebook_type_id: string
    title: string
    school_year: string
  }): Promise<Notebook | null> {
    saving.value = true
    errors.value = { message: null, fields: null }

    try {
      const { data } = await notebookService.create({ id: mintId(), ...input })
      if (data) notebooks.value.push(data)
      return data ?? null
    } catch (error) {
      errors.value = extractErrors(error)
      return null
    } finally {
      saving.value = false
    }
  }

  function replace(updated: Notebook): void {
    const index = notebooks.value.findIndex((n) => n.id === updated.id)
    if (index !== -1) notebooks.value[index] = updated
  }

  async function rename(id: string, title: string): Promise<void> {
    try {
      const { data } = await notebookService.update(id, { title })
      if (data) replace(data)
    } catch (error) {
      errors.value = extractErrors(error)
    }
  }

  async function setArchived(id: string, archive: boolean): Promise<void> {
    try {
      const { data } = archive
        ? await notebookService.archive(id)
        : await notebookService.unarchive(id)
      if (data) replace(data)
    } catch (error) {
      errors.value = extractErrors(error)
    }
  }

  /** Soft delete — the row becomes a tombstone server-side (schema §2.4). */
  async function remove(id: string): Promise<void> {
    try {
      await notebookService.remove(id)
      notebooks.value = notebooks.value.filter((n) => n.id !== id)
    } catch (error) {
      errors.value = extractErrors(error)
    }
  }

  return {
    notebooks,
    types,
    active,
    archived,
    schoolYears,
    schoolYearFilter,
    loading,
    saving,
    errors,
    typeFor,
    load,
    create,
    rename,
    setArchived,
    remove,
  }
}
