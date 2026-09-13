<script setup lang="ts">
import { computed } from 'vue'
import type { PageTemplate } from '@notebook/types'

// A thumbnail of what a type's paper looks like, drawn from page_template —
// the same contract Phase 007's full PaperPage renders from. Deliberately a
// SEPARATE component: this is a swatch, not the real paper, and Phase 007 must
// not be tempted to grow this one into the editor surface.
const props = defineProps<{ template: PageTemplate; height?: number }>()

/** Ruling drawn in CSS, no image assets (D-010). */
const background = computed(() => {
  const spacing = Math.max(4, (props.template.line_spacing_mm ?? 8) * 0.9)

  switch (props.template.ruling) {
    case 'single_ruled':
      return `repeating-linear-gradient(to bottom, transparent 0 ${spacing - 1}px, #b9cfe8 ${spacing - 1}px ${spacing}px)`
    case 'penmanship_blue_red':
      return [
        `repeating-linear-gradient(to bottom, transparent 0 ${spacing - 1}px, #b9cfe8 ${spacing - 1}px ${spacing}px)`,
        `repeating-linear-gradient(to bottom, transparent 0 ${spacing * 3 - 1}px, #e0928c ${spacing * 3 - 1}px ${spacing * 3}px)`,
      ].join(',')
    case 'grid':
      return [
        `repeating-linear-gradient(to bottom, transparent 0 ${spacing - 1}px, #b9cfe8 ${spacing - 1}px ${spacing}px)`,
        `repeating-linear-gradient(to right, transparent 0 ${spacing - 1}px, #b9cfe8 ${spacing - 1}px ${spacing}px)`,
      ].join(',')
    default:
      return 'none'
  }
})
</script>

<template>
  <div
    class="relative overflow-hidden rounded border border-paper-shade bg-paper"
    :style="{ height: `${height ?? 64}px` }"
  >
    <div class="absolute inset-0" :style="{ background }" />
    <div
      v-if="template.margin"
      class="absolute bottom-0 top-0 w-px bg-margin-soft"
      :style="{ left: `${(template.margin.offset_mm ?? 25) * 0.5}px` }"
    />
  </div>
</template>
