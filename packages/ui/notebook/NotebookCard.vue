<script setup lang="ts">
import { computed } from 'vue'
import type { Notebook, NotebookType } from '@notebook/types'
import NotebookCover from './NotebookCover.vue'

// One shelf card: cover, title, type + school year, archived badge.
const props = defineProps<{
  notebook: Notebook
  type?: NotebookType | null
  coverHeight?: number
}>()

defineEmits<{ open: [notebook: Notebook]; menu: [notebook: Notebook] }>()

const subtitle = computed(() => {
  const parts = [props.type?.name ?? 'Notebook', props.notebook.school_year]
  return parts.join(' · ')
})
</script>

<template>
  <div class="flex flex-col text-left">
    <button type="button" class="text-left" @click="$emit('open', notebook)">
      <NotebookCover :title="notebook.title" :type="type" :height="coverHeight" />
    </button>

    <div class="mt-2 flex items-start justify-between gap-2">
      <div class="min-w-0">
        <p class="truncate text-sm font-semibold text-ink-text">{{ notebook.title }}</p>
        <p class="truncate text-xs text-ink-soft">{{ subtitle }}</p>
      </div>

      <button
        type="button"
        class="shrink-0 rounded px-1 text-ink-soft hover:text-ink"
        :aria-label="`Options for ${notebook.title}`"
        @click="$emit('menu', notebook)"
      >
        ⋯
      </button>
    </div>

    <span
      v-if="notebook.status === 'archived'"
      class="mt-1 self-start rounded-full bg-paper-shade px-2 py-0.5 text-[11px] font-medium text-ink-soft"
    >
      Archived
    </span>
  </div>
</template>
