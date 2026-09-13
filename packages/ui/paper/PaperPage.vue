<script setup lang="ts">
import { computed } from 'vue'
import type { PageTemplate } from '@notebook/types'

/**
 * The paper renderer (Phase 007, D-010) — the product's soul.
 *
 * ======================== THE ONE THING THAT MATTERS ========================
 * `--paper-line-height` is the single source of the line pitch. The ruling is
 * drawn at that pitch AND the editor's text line-height is set from it, so the
 * text baseline sits ON the line. Two independent numbers here is how paper
 * fidelity dies — if you change the pitch, change it in ONE place.
 *
 * Values come from the approved design canvas (2026-09-13-006, board 07):
 * 28px pitch at 16px text, ruling offset 2px, margin line at 44px.
 * ===========================================================================
 *
 * Everything is CSS-drawn — no image assets (D-010). Header/footer fields are
 * page CHROME, not editable blocks: they render from the template and the
 * editor never sees them.
 */
const props = withDefaults(
  defineProps<{
    template: PageTemplate
    /** Line pitch in px. The canvas uses 28 for 16px body text. */
    lineHeight?: number
    /** Hide chrome and padding — used by the thumbnail rail. */
    compact?: boolean
  }>(),
  { lineHeight: 28, compact: false },
)

/** Ruling colour tokens, resolved to the brand palette (D-032). */
const RULE_BLUE = '#b9cfe8'
const RULE_RED = '#e0928c'
const MARGIN_RED = '#c9463d'

const pitch = computed(() => props.lineHeight)

/**
 * The ruling, as a background image. Offset by 2px so the line falls just under
 * the text baseline rather than through it (canvas board 07).
 */
const ruling = computed(() => {
  const p = pitch.value

  switch (props.template.ruling) {
    case 'single_ruled':
      return {
        backgroundImage: `repeating-linear-gradient(to bottom, transparent 0, transparent ${p - 1}px, ${RULE_BLUE} ${p - 1}px, ${RULE_BLUE} ${p}px)`,
        backgroundPosition: '0 2px',
      }

    case 'penmanship_blue_red': {
      // Blue guide lines at the pitch, with a red baseline every third — the
      // classic penmanship band young writers learn on.
      const band = p * 3
      return {
        backgroundImage: [
          `repeating-linear-gradient(to bottom, transparent 0, transparent ${p - 1}px, ${RULE_BLUE} ${p - 1}px, ${RULE_BLUE} ${p}px)`,
          `repeating-linear-gradient(to bottom, transparent 0, transparent ${band - 1}px, ${RULE_RED} ${band - 1}px, ${RULE_RED} ${band}px)`,
        ].join(', '),
        backgroundPosition: '0 2px, 0 2px',
      }
    }

    case 'grid':
      return {
        backgroundImage: [
          `repeating-linear-gradient(to bottom, transparent 0, transparent ${p - 1}px, ${RULE_BLUE} ${p - 1}px, ${RULE_BLUE} ${p}px)`,
          `repeating-linear-gradient(to right, transparent 0, transparent ${p - 1}px, ${RULE_BLUE} ${p - 1}px, ${RULE_BLUE} ${p}px)`,
        ].join(', '),
        backgroundPosition: '0 2px, 0 0',
      }

    default:
      return { backgroundImage: 'none', backgroundPosition: '0 0' }
  }
})

/** Left inset for the text column: clears the margin rule when there is one. */
const textInset = computed(() => {
  if (props.compact) return 12
  return props.template.margin ? 58 : 24
})

const marginOffset = computed(() => props.template.margin?.offset_mm ?? 0)
</script>

<template>
  <div
    class="paper-page relative flex h-full flex-col overflow-hidden bg-white"
    :style="{ '--paper-line-height': `${pitch}px` }"
  >
    <!-- Header chrome: rendered, never editable (D-010). -->
    <div
      v-if="!compact && template.header_fields?.length"
      class="flex flex-none flex-wrap items-end gap-x-6 gap-y-1 px-6 pb-2 pt-4"
      :style="{ paddingLeft: `${textInset}px` }"
    >
      <div
        v-for="field in template.header_fields"
        :key="field.key"
        class="flex min-w-[140px] flex-1 items-end gap-2"
      >
        <span class="whitespace-nowrap text-sm text-ink-soft">{{ field.label }}</span>
        <span class="h-px flex-1 bg-rule-blue" aria-hidden="true" />
      </div>
    </div>

    <!-- The ruled writing area. The slot is the editor. -->
    <div class="relative min-h-0 flex-1">
      <!-- Margin rule, drawn behind the text. -->
      <div
        v-if="template.margin && !compact"
        class="pointer-events-none absolute bottom-0 top-0 w-[1.5px]"
        :style="{
          left: `${marginOffset * 1.76}px`,
          background: MARGIN_RED,
        }"
        aria-hidden="true"
      />

      <div
        class="paper-surface h-full overflow-y-auto pr-5"
        :style="{
          ...ruling,
          paddingLeft: `${textInset}px`,
          paddingTop: compact ? '4px' : '9px',
        }"
      >
        <slot />
      </div>
    </div>

    <!-- Footer chrome: signature rules etc. -->
    <div
      v-if="!compact && template.footer_fields?.length"
      class="flex flex-none flex-wrap items-end gap-x-8 gap-y-2 px-6 pb-4 pt-3"
      :style="{ paddingLeft: `${textInset}px` }"
    >
      <div v-for="field in template.footer_fields" :key="field.key" class="flex-1">
        <span class="block h-px w-full bg-ink-faint" aria-hidden="true" />
        <span class="mt-1 block text-xs text-ink-soft">{{ field.label }}</span>
      </div>
    </div>
  </div>
</template>

<style>
/*
 * The line-height sync. EVERY block inside the paper inherits the pitch, so
 * text advances exactly one ruled line per line of type. Scoped deliberately
 * loose (not `scoped`) because the editor renders into the slot and its nodes
 * are not this component's children at build time.
 */
.paper-page .paper-surface,
.paper-page .paper-surface .ProseMirror {
  line-height: var(--paper-line-height);
  font-size: 16px;
}

.paper-page .paper-surface p,
.paper-page .paper-surface li,
.paper-page .paper-surface h1,
.paper-page .paper-surface h2 {
  line-height: var(--paper-line-height);
  margin: 0;
}

/* Headings stay on the grid: bolder and slightly larger, same advance. */
.paper-page .paper-surface h1 {
  font-size: 19px;
  font-weight: 700;
}

.paper-page .paper-surface h2 {
  font-size: 17px;
  font-weight: 700;
}

.paper-page .paper-surface ul,
.paper-page .paper-surface ol {
  margin: 0;
  padding-left: 1.4em;
}

.paper-page .paper-surface ul {
  list-style: disc;
}

.paper-page .paper-surface ol {
  list-style: decimal;
}

.paper-page .paper-surface table {
  border-collapse: collapse;
  width: 100%;
}

.paper-page .paper-surface th,
.paper-page .paper-surface td {
  border: 1px solid #b9cfe8;
  padding: 0 6px;
  line-height: var(--paper-line-height);
  text-align: left;
}

.paper-page .paper-surface .ProseMirror:focus {
  outline: none;
}

/* Placeholder for an empty document (Tiptap sets data-placeholder). */
.paper-page .paper-surface .is-editor-empty:first-child::before {
  content: attr(data-placeholder);
  float: left;
  height: 0;
  color: #a9b6c8;
  pointer-events: none;
}

/*
 * Print keeps the rulings AND the content — a printed page must look like the
 * screen (feeds M2's lesson PDF, D-009).
 */
@media print {
  .paper-page {
    box-shadow: none !important;
    height: auto !important;
  }

  .paper-page .paper-surface {
    overflow: visible !important;
    /* Browsers strip background images on print unless asked not to. */
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
}
</style>
