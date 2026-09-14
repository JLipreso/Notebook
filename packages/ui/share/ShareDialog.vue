<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { NotebookShareWithStatus } from '@notebook/types'
import FormErrors from '../auth/FormErrors.vue'
import { useShares } from './useShares'

/**
 * Create and manage read-only share links (Phase 010, D-016).
 *
 * Shared by both form factors: the browser shows it as a modal, mobile
 * full-screen. Neither reimplements the link logic (D-027).
 */
const props = withDefaults(
  defineProps<{
    open: boolean
    notebookId: string
    notebookTitle?: string
    /** When set, the scope picker offers "this page only". */
    currentPageId?: string | null
    currentPageNumber?: number | null
    fullscreen?: boolean
  }>(),
  { notebookTitle: '', currentPageId: null, currentPageNumber: null, fullscreen: false },
)

defineEmits<{ close: [] }>()

const { shares, loading, creating, errors, load, create, revoke, linkFor } = useShares(
  props.notebookId,
)

const scope = ref<'notebook' | 'page'>('notebook')
const expiresAt = ref('')
const copiedId = ref<string | null>(null)

const canScopeToPage = computed(() => props.currentPageId !== null)

watch(
  () => props.open,
  (open) => {
    if (!open) return
    scope.value = 'notebook'
    expiresAt.value = ''
    copiedId.value = null
    void load()
  },
)

async function onCreate(): Promise<void> {
  const created = await create({
    page_id: scope.value === 'page' ? props.currentPageId : null,
    // <input type="datetime-local"> has no timezone; the server parses it.
    expires_at: expiresAt.value ? new Date(expiresAt.value).toISOString() : null,
  })

  if (created) await copy(created)
}

async function copy(share: NotebookShareWithStatus): Promise<void> {
  const link = linkFor(share)

  try {
    await navigator.clipboard.writeText(link)
    copiedId.value = share.id
    window.setTimeout(() => {
      if (copiedId.value === share.id) copiedId.value = null
    }, 2000)
  } catch {
    // Clipboard is blocked in some WebViews — show the link so it can be
    // selected by hand rather than failing silently.
    window.prompt('Copy this link:', link)
  }
}

function statusLabel(share: NotebookShareWithStatus): string {
  if (share.is_revoked) return 'Revoked'
  if (share.is_expired) return 'Expired'
  return share.page_id ? 'Active · one page' : 'Active · whole notebook'
}
</script>

<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex bg-ink/40"
    :class="fullscreen ? '' : 'items-center justify-center p-6'"
    role="dialog"
    aria-modal="true"
    aria-label="Share notebook"
    @click.self="$emit('close')"
  >
    <div
      class="flex w-full flex-col overflow-y-auto bg-paper p-6"
      :class="fullscreen ? 'h-full' : 'max-h-[85vh] max-w-lg rounded-lg shadow-xl'"
    >
      <header class="mb-4 flex items-start justify-between gap-4">
        <div>
          <h2 class="font-display text-2xl font-bold text-ink">Share</h2>
          <p class="text-sm text-ink-soft">
            {{ notebookTitle }} · anyone with the link can read it
          </p>
        </div>
        <button type="button" class="text-2xl leading-none text-ink-soft" aria-label="Close" @click="$emit('close')">
          ×
        </button>
      </header>

      <FormErrors :message="errors.message" :errors="errors.fields" class="mb-4" />

      <section class="flex flex-col gap-3">
        <fieldset class="flex flex-col gap-2">
          <legend class="text-sm font-medium text-ink">What to share</legend>

          <label class="flex items-center gap-2 text-sm text-ink">
            <input v-model="scope" type="radio" value="notebook" />
            The whole notebook
          </label>
          <label
            class="flex items-center gap-2 text-sm"
            :class="canScopeToPage ? 'text-ink' : 'text-ink-faint'"
          >
            <input v-model="scope" type="radio" value="page" :disabled="!canScopeToPage" />
            <span v-if="canScopeToPage">Only page {{ currentPageNumber }}</span>
            <span v-else>Only this page (open a page first)</span>
          </label>
        </fieldset>

        <label class="flex flex-col gap-1">
          <span class="text-sm font-medium text-ink">Stop working on (optional)</span>
          <input
            v-model="expiresAt"
            type="datetime-local"
            class="rounded border border-paper-shade bg-paper px-3 py-2 text-ink outline-none focus:border-ink"
          />
        </label>

        <button
          type="button"
          :disabled="creating"
          class="rounded bg-ink px-4 py-2.5 font-medium text-paper disabled:opacity-50"
          @click="onCreate"
        >
          {{ creating ? 'Creating…' : 'Create link' }}
        </button>
      </section>

      <section class="mt-6">
        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-ink-soft">
          Existing links
        </h3>

        <p v-if="loading" class="text-sm text-ink-faint">Loading…</p>
        <p v-else-if="shares.length === 0" class="text-sm text-ink-soft">
          No links yet. Anyone you give a link to can read this notebook without signing in.
        </p>

        <ul v-else class="flex flex-col gap-2">
          <li
            v-for="share in shares"
            :key="share.id"
            class="rounded border border-paper-shade p-3"
            :class="share.is_active ? '' : 'opacity-60'"
          >
            <div class="flex items-center justify-between gap-3">
              <span class="text-xs font-medium text-ink-soft">{{ statusLabel(share) }}</span>

              <div class="flex gap-3 text-xs">
                <button
                  v-if="share.is_active"
                  type="button"
                  class="text-ink underline"
                  @click="copy(share)"
                >
                  {{ copiedId === share.id ? 'Copied!' : 'Copy link' }}
                </button>
                <button
                  v-if="share.is_active"
                  type="button"
                  class="text-margin underline"
                  @click="revoke(share.id)"
                >
                  Revoke
                </button>
              </div>
            </div>

            <p v-if="share.is_active" class="mt-1 truncate font-mono text-[11px] text-ink-faint">
              {{ linkFor(share) }}
            </p>
            <p v-if="share.expires_at && !share.is_revoked" class="mt-1 text-[11px] text-ink-faint">
              Expires {{ new Date(share.expires_at).toLocaleString() }}
            </p>
          </li>
        </ul>
      </section>
    </div>
  </div>
</template>
