<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  ArchiveShelf,
  FormErrors,
  NewNotebookDialog,
  NotebookGrid,
  useNotebooks,
} from '@notebook/ui'
import { formatSchoolYear } from '@notebook/utility'
import type { Notebook } from '@notebook/types'
import { useAuthStore } from '@/stores/auth'

// Mobile layout: portrait shelf, segmented control, FAB — per the approved
// canvas (2026-09-13-006, board 05). Same shared pieces and same composable as
// the browser app; only the composition differs (D-027).
const auth = useAuthStore()
const router = useRouter()

const { types, active, archived, schoolYears, loading, saving, errors, load, create, setArchived, remove } =
  useNotebooks()

const tab = ref<'active' | 'archived'>('active')
const dialogOpen = ref(false)

onMounted(load)

function open(notebook: Notebook): void {
  router.push({ name: 'home', query: { notebook: notebook.id } })
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
    `“${notebook.title}”\n\nType A to ${archive ? 'archive' : 'restore'}, or D to delete.`,
    'A',
  )

  if (choice === null) return

  const answer = choice.trim().toUpperCase()

  if (answer === 'A') await setArchived(notebook.id, archive)
  else if (answer === 'D' && window.confirm(`Delete “${notebook.title}”? It leaves your shelf.`)) {
    await remove(notebook.id)
  }
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
      <RouterLink to="/profile" class="shrink-0 text-sm text-ink-soft underline">Profile</RouterLink>
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
  </main>
</template>
