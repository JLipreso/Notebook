<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  ArchiveShelf,
  FormErrors,
  NewNotebookDialog,
  NotebookGrid,
  useNotebooks,
  useNotifications,
} from '@notebook/ui'
import { formatSchoolYear } from '@notebook/utility'
import type { Notebook } from '@notebook/types'
import { useAuthStore } from '@/stores/auth'

// Mobile layout: portrait shelf, segmented control, FAB — per the approved
// canvas (2026-09-13-006, board 05). Same shared pieces and same composable as
// the browser app; only the composition differs (D-027).
const auth = useAuthStore()
const router = useRouter()

const {
  types,
  active,
  archived,
  schoolYears,
  loading,
  saving,
  errors,
  load,
  create,
  setArchived,
  remove,
  setCover,
  coverUrls,
} = useNotebooks()

// Just the badge here — the list lives on its own screen (Phase 010).
const { unread, refreshCount } = useNotifications()
onMounted(refreshCount)

const tab = ref<'active' | 'archived'>('active')
const dialogOpen = ref(false)

onMounted(load)

function open(notebook: Notebook): void {
  router.push({ name: 'notebook', params: { id: notebook.id } })
}

async function onCreate(payload: {
  notebook_type_id: string
  title: string
  school_year: string
}): Promise<void> {
  const created = await create(payload)
  if (created) dialogOpen.value = false
}

async function onMenu(notebook: Notebook): Promise<void> {
  const archive = notebook.status === 'active'

  const choice = window.prompt(
    `“${notebook.title}”\n\nType A to ${archive ? 'archive' : 'restore'}, C to set a cover photo, or D to delete.`,
    'A',
  )

  if (choice === null) return

  const answer = choice.trim().toUpperCase()

  if (answer === 'A') await setArchived(notebook.id, archive)
  else if (answer === 'C') pickCover(notebook.id)
  else if (answer === 'D' && window.confirm(`Delete “${notebook.title}”? It leaves your shelf.`)) {
    await remove(notebook.id)
  }
}

// Cover photo (Phase 008): pick a file, upload it as kind=cover, point the
// notebook at it. A plain file input works inside the Capacitor WebView too.
const coverInput = ref<HTMLInputElement | null>(null)
const coverTarget = ref<string | null>(null)

function pickCover(notebookId: string): void {
  coverTarget.value = notebookId
  coverInput.value?.click()
}

async function onCoverPicked(event: Event): Promise<void> {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  input.value = ''

  const id = coverTarget.value
  coverTarget.value = null
  if (!file || !id) return

  await setCover(id, file)
}
</script>

<template>
  <main class="relative flex min-h-screen flex-col bg-paper px-6 pb-28 pt-8">
    <header class="mb-5 flex items-start justify-between gap-3">
      <div>
        <h1 class="font-display text-2xl font-bold text-ink">
          Magandang umaga{{ auth.user ? `, ${auth.user.first_name}` : '' }}!
        </h1>
        <p class="text-sm text-ink-soft">{{ formatSchoolYear(schoolYears[0] ?? '') || 'Your notebooks' }}</p>
      </div>
      <div class="flex shrink-0 items-center gap-3">
        <RouterLink to="/notifications" class="relative text-xl" aria-label="Notifications">
          🔔
          <span
            v-if="unread > 0"
            class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-margin px-1 text-[10px] font-bold text-paper"
          >
            {{ unread > 9 ? '9+' : unread }}
          </span>
        </RouterLink>
        <RouterLink to="/profile" class="text-sm text-ink-soft underline">Profile</RouterLink>
      </div>
    </header>

    <div class="mb-5 flex rounded-lg bg-paper-shade p-1">
      <button
        type="button"
        class="flex-1 rounded px-3 py-1.5 text-sm"
        :class="tab === 'active' ? 'bg-paper font-semibold text-ink shadow-sm' : 'text-ink-soft'"
        @click="tab = 'active'"
      >
        Active
      </button>
      <button
        type="button"
        class="flex-1 rounded px-3 py-1.5 text-sm"
        :class="tab === 'archived' ? 'bg-paper font-semibold text-ink shadow-sm' : 'text-ink-soft'"
        @click="tab = 'archived'"
      >
        Archived
      </button>
    </div>

    <div class="mb-3 flex items-baseline justify-between">
      <span class="text-xs font-semibold uppercase tracking-wider text-ink-soft">
        {{ tab === 'active' ? 'Active notebooks' : 'Archived' }}
      </span>
      <span class="text-xs font-semibold text-ink-soft">
        {{ tab === 'active' ? active.length : archived.length }}
      </span>
    </div>

    <FormErrors :message="errors.message" :errors="errors.fields" class="mb-4" />

    <p v-if="loading" class="text-sm text-ink-faint">Loading your shelf…</p>

    <template v-else>
      <NotebookGrid
        v-if="tab === 'active'"
        :notebooks="active"
        :types="types"
        :cover-urls="coverUrls"
        empty-message="No notebooks yet — tap + to create your first."
        @open="open"
        @menu="onMenu"
      />
      <ArchiveShelf v-else :notebooks="archived" :types="types" @open="open" @menu="onMenu" />
    </template>

    <button
      type="button"
      class="fixed bottom-7 right-6 flex h-14 w-14 items-center justify-center rounded-full bg-margin text-3xl leading-none text-paper shadow-lg"
      aria-label="New notebook"
      @click="dialogOpen = true"
    >
      +
    </button>

    <NewNotebookDialog
      :open="dialogOpen"
      :types="types"
      :saving="saving"
      :error-message="errors.message"
      :error-fields="errors.fields"
      fullscreen
      @close="dialogOpen = false"
      @create="onCreate"
    />

    <input
      ref="coverInput"
      type="file"
      accept="image/jpeg,image/png,image/webp"
      class="hidden"
      @change="onCoverPicked"
    />
  </main>
</template>
