<script setup lang="ts">
import { computed } from 'vue'
import type { NotebookType } from '@notebook/types'

// The cover visual from the approved design canvas (2026-09-13-006, board 05):
// ink-navy board, darker spine down the left edge, one rule line near the top,
// title in the display face on cream. Cover PHOTOS land in Phase 008; until
// then every notebook gets this, tinted by its type.
const props = defineProps<{
  title: string
  type?: NotebookType | null
  /** Card height in px — the canvas uses 152 on mobile. */
  height?: number
}>()

/**
 * A type-specific accent so a shelf of notebooks is scannable. Token names
 * would be better, but Tailwind cannot build a class from a runtime value, so
 * these come from the brand preset's own palette (D-032) and must move with it.
 */
const tint = computed(() => {
  switch (props.type?.page_template.ruling) {
    case 'penmanship_blue_red':
      return { board: '#22344c', spine: '#18263a' }
    case 'blank':
      return { board: '#7a89a0', spine: '#5f6d82' }
    case 'grid':
      return { board: '#2f5233', spine: '#223d25' }
    default:
      return { board: '#1e3a5f', spine: '#16293f' }
  }
})
</script>

<template>
  <div
    class="relative overflow-hidden rounded-lg shadow-md"
    :style="{ height: `${height ?? 152}px`, background: tint.board }"
  >
    <div class="absolute bottom-0 left-0 top-0 w-[18px]" :style="{ background: tint.spine }" />
    <div class="absolute left-7 right-3 top-4 h-px bg-paper/30" />
    <div class="absolute bottom-4 left-7 right-3 font-display text-lg font-semibold leading-tight text-paper">
      {{ title }}
    </div>
  </div>
</template>
