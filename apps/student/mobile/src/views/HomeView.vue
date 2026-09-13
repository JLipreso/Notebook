<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { deriveSchoolYear, formatSchoolYear } from '@notebook/utility'
import { notebookService, useMock } from '@notebook/services'
import { currentPlatform } from '@notebook/sync'
import type { Notebook, NotebookType } from '@notebook/types'

const schoolYear = formatSchoolYear(deriveSchoolYear())
const platform = currentPlatform()

// Phase 003 contract proof: identical service calls to the browser app, zero
// shared view code (D-027 — apps are layout only). Replaced in Phase 006.
const notebooks = ref<Notebook[]>([])
const types = ref<NotebookType[]>([])
const loading = ref(true)
const error = ref<string | null>(null)

function typeName(id: string): string {
  return types.value.find((t) => t.id === id)?.name ?? 'Unknown type'
}

onMounted(async () => {
  try {
    const [list, typeList] = await Promise.all([notebookService.list(), notebookService.types()])
    notebooks.value = list.data ?? []
    types.value = typeList.data ?? []
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to load notebooks'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <main class="mx-auto flex min-h-screen max-w-sm flex-col items-center justify-center gap-3 p-6 text-center">
    <h1 class="font-display text-4xl font-bold">Notebook</h1>
    <p class="text-ink-soft">Your notebooks for {{ schoolYear }}, in your pocket.</p>
    <p class="text-sm text-ink-faint">
      Student · mobile (portrait) · running on <span class="font-semibold">{{ platform }}</span>
      · datasource: <span class="font-semibold">{{ useMock ? 'mock' : 'live API' }}</span>
    </p>
    <div class="mt-2 h-1 w-16 rounded bg-margin" aria-hidden="true"></div>

    <RouterLink to="/profile" class="text-sm text-ink underline">Profile &amp; address</RouterLink>

    <section class="mt-6 w-full text-left">
      <p v-if="loading" class="text-sm text-ink-faint">Loading notebooks…</p>
      <p v-else-if="error" class="text-sm text-margin">{{ error }}</p>
      <ul v-else class="flex flex-col gap-2">
        <li
          v-for="notebook in notebooks"
          :key="notebook.id"
          class="rounded border border-paper-shade bg-paper-shade/40 px-3 py-2"
        >
          <span class="font-semibold">{{ notebook.title }}</span>
          <span class="block text-sm text-ink-soft">
            {{ typeName(notebook.notebook_type_id) }} · {{ formatSchoolYear(notebook.school_year) }}
            <span v-if="notebook.status === 'archived'" class="text-ink-faint">· archived</span>
          </span>
        </li>
      </ul>
    </section>
  </main>
</template>
