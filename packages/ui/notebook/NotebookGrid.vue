<script setup lang="ts">
import type { Notebook, NotebookType } from '@notebook/types'
import NotebookCard from './NotebookCard.vue'

// The shelf. Two columns on mobile (per the canvas), more as width allows.
const props = defineProps<{
  notebooks: Notebook[]
  types: NotebookType[]
  coverHeight?: number
  emptyMessage?: string
  /** cover_upload_id -> served URL, resolved once by the caller (Phase 008). */
  coverUrls?: Record<string, string>
}>()

defineEmits<{ open: [notebook: Notebook]; menu: [notebook: Notebook] }>()

function typeFor(types: NotebookType[], id: string): NotebookType | null {
  return types.find((t) => t.id === id) ?? null
}

function coverFor(notebook: Notebook): string | null {
  if (!notebook.cover_upload_id) return null
  return props.coverUrls?.[notebook.cover_upload_id] ?? null
}
</script>

<template>
  <div>
    <p v-if="notebooks.length === 0" class="py-8 text-center text-sm text-ink-soft">
      {{ emptyMessage ?? 'No notebooks yet.' }}
    </p>

    <div v-else class="grid grid-cols-2 gap-x-3.5 gap-y-4 sm:grid-cols-3 lg:grid-cols-4">
      <NotebookCard
        v-for="notebook in notebooks"
        :key="notebook.id"
        :notebook="notebook"
        :type="typeFor(types, notebook.notebook_type_id)"
        :cover-height="coverHeight"
        :cover-url="coverFor(notebook)"
        @open="$emit('open', $event)"
        @menu="$emit('menu', $event)"
      />
    </div>
  </div>
</template>
