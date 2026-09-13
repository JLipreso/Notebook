<script setup lang="ts">
import { computed } from 'vue'
import { formatSchoolYear } from '@notebook/utility'
import type { Notebook, NotebookType } from '@notebook/types'
import NotebookGrid from './NotebookGrid.vue'

// Archived notebooks grouped by school year, newest year first.
// D-023: archives are FOREVER and stay fully readable — this is a shelf, not a
// recycle bin, and nothing here is pending deletion.
const props = defineProps<{
  notebooks: Notebook[]
  types: NotebookType[]
  coverHeight?: number
}>()

defineEmits<{ open: [notebook: Notebook]; menu: [notebook: Notebook] }>()

const groups = computed(() => {
  const byYear = new Map<string, Notebook[]>()

  for (const notebook of props.notebooks) {
    const list = byYear.get(notebook.school_year) ?? []
    list.push(notebook)
    byYear.set(notebook.school_year, list)
  }

  return [...byYear.entries()]
    .sort(([a], [b]) => b.localeCompare(a))
    .map(([schoolYear, items]) => ({ schoolYear, items }))
})
</script>

<template>
  <div class="flex flex-col gap-6">
    <p v-if="groups.length === 0" class="py-8 text-center text-sm text-ink-soft">
      Nothing archived yet. Archived notebooks stay readable forever.
    </p>

    <section v-for="group in groups" :key="group.schoolYear" class="flex flex-col gap-3">
      <h3 class="text-xs font-semibold uppercase tracking-wider text-ink-soft">
        {{ formatSchoolYear(group.schoolYear) }}
      </h3>
      <NotebookGrid
        :notebooks="group.items"
        :types="types"
        :cover-height="coverHeight"
        @open="$emit('open', $event)"
        @menu="$emit('menu', $event)"
      />
    </section>
  </div>
</template>
