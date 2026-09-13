<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { deriveSchoolYear, formatSchoolYear } from '@notebook/utility'
import type { NotebookType } from '@notebook/types'
import FormField from '../auth/FormField.vue'
import FormErrors from '../auth/FormErrors.vue'
import PaperPreview from './PaperPreview.vue'

/**
 * Create-notebook flow, shared by both form factors (D-027). The browser app
 * shows it as a modal; the mobile app renders the same component full-screen.
 */
const props = defineProps<{
  open: boolean
  types: NotebookType[]
  saving?: boolean
  errorMessage?: string | null
  errorFields?: Record<string, string[]> | null
  /** Full-screen on mobile instead of a centred modal card. */
  fullscreen?: boolean
}>()

const emit = defineEmits<{
  close: []
  create: [payload: { notebook_type_id: string; title: string; school_year: string }]
}>()

const title = ref('')
const typeId = ref<string | null>(null)

// deriveSchoolYear() is the ONLY source of the default (Phase 006) — never
// re-derived inline anywhere else.
const schoolYear = ref(deriveSchoolYear())

const selectedType = computed(() => props.types.find((t) => t.id === typeId.value) ?? null)
const canSubmit = computed(() => title.value.trim() !== '' && typeId.value !== null)

// Reset whenever the dialog opens, so a cancelled attempt does not persist.
watch(
  () => props.open,
  (open) => {
    if (!open) return
    title.value = ''
    typeId.value = props.types[0]?.id ?? null
    schoolYear.value = deriveSchoolYear()
  },
)

function submit(): void {
  if (!canSubmit.value || props.saving) return

  emit('create', {
    notebook_type_id: typeId.value as string,
    title: title.value.trim(),
    school_year: schoolYear.value,
  })
}
</script>

<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex bg-ink/40"
    :class="fullscreen ? '' : 'items-center justify-center p-6'"
    role="dialog"
    aria-modal="true"
    aria-label="New notebook"
    @click.self="$emit('close')"
  >
    <div
      class="flex w-full flex-col overflow-y-auto bg-paper"
      :class="fullscreen ? 'h-full' : 'max-h-[90vh] max-w-lg rounded-lg p-6 shadow-xl'"
      :style="fullscreen ? 'padding: 1.5rem' : ''"
    >
      <header class="mb-4 flex items-start justify-between gap-4">
        <div>
          <h2 class="font-display text-2xl font-bold text-ink">New notebook</h2>
          <p class="text-sm text-ink-soft">{{ formatSchoolYear(schoolYear) }}</p>
        </div>
        <button type="button" class="text-2xl leading-none text-ink-soft" aria-label="Close" @click="$emit('close')">
          ×
        </button>
      </header>

      <form class="flex flex-col gap-4" @submit.prevent="submit">
        <FormErrors :message="errorMessage" :errors="errorFields" />

        <FormField v-model="title" label="Title" placeholder="Math 7" required />

        <fieldset class="flex flex-col gap-2">
          <legend class="mb-1 text-sm font-medium text-ink">Paper type</legend>

          <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <button
              v-for="type in types"
              :key="type.id"
              type="button"
              class="flex flex-col gap-1.5 rounded-lg border p-2 text-left transition"
              :class="
                typeId === type.id
                  ? 'border-ink bg-paper-shade/50'
                  : 'border-paper-shade hover:border-ink-faint'
              "
              :aria-pressed="typeId === type.id"
              @click="typeId = type.id"
            >
              <PaperPreview :template="type.page_template" />
              <span class="text-xs font-medium text-ink-text">{{ type.name }}</span>
            </button>
          </div>

          <p v-if="selectedType?.description" class="text-xs text-ink-soft">
            {{ selectedType.description }}
          </p>
          <p v-if="selectedType?.requires_ink" class="text-xs text-ink-faint">
            Drawing tools arrive in a later update — pages are blank for now.
          </p>
        </fieldset>

        <FormField v-model="schoolYear" label="School year" placeholder="2026-2027" required />

        <div class="mt-2 flex gap-3">
          <button
            type="submit"
            :disabled="!canSubmit || saving"
            class="flex-1 rounded bg-ink px-4 py-2.5 font-medium text-paper disabled:opacity-50"
          >
            {{ saving ? 'Creating…' : 'Create notebook' }}
          </button>
          <button
            type="button"
            class="rounded border border-paper-shade px-4 py-2.5 font-medium text-ink"
            @click="$emit('close')"
          >
            Cancel
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
