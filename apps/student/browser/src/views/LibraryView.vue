<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  ArchiveShelf,
  FormErrors,
  NewNotebookDialog,
  NotebookGrid,
  NotificationList,
  useNotebooks,
  useNotifications,
} from '@notebook/ui'
import { formatSchoolYear } from '@notebook/utility'
import type { AppNotification, Notebook } from '@notebook/types'
import { useAuthStore } from '@/stores/auth'

// Browser layout: shelf + sidebar filters, per the approved canvas
// (2026-09-13-006). The mobile app composes the SAME shared pieces into a
// portrait shelf with a FAB (D-027 — apps hold layout only).
const auth = useAuthStore()
const router = useRouter()

const {
  types,
  active,
  archived,
  schoolYears,
  schoolYearFilter,
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

// Notifications (Phase 010): a bell with an unread badge.
const {
  notifications,
  unread,
  loading: notificationsLoading,
  hasMore: moreNotifications,
  load: loadNotifications,
  loadMore: loadMoreNotifications,
  markRead,
  markAllRead,
  destinationFor,
} = useNotifications()

const bellOpen = ref(false)

async function openBell(): Promise<void> {
  bellOpen.value = !bellOpen.value
  if (bellOpen.value) await loadNotifications(1)
}

async function onNotification(notification: AppNotification): Promise<void> {
  await markRead(notification.id)

  const destination = destinationFor(notification)
  bellOpen.value = false

  // 'library' IS this screen — following it would be a no-op reload.
  if (destination && destination.name !== 'library') {
    router.push({ name: destination.name, params: destination.params as never }).catch(() => {})
  }
}

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

  // A real menu lands with the editor in Phase 007; a confirm keeps the action
  // reachable and honest until then.
  const choice = window.prompt(
    `“${notebook.title}”

Type A to ${archive ? 'archive' : 'restore'}, C to set a cover photo, or D to delete.`,
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
  <main class="mx-auto flex max-w-5xl gap-8 bg-paper p-8">
    <aside class="hidden w-48 shrink-0 flex-col gap-6 lg:flex">
      <div>
        <h2 class="mb-2 text-xs font-semibold uppercase tracking-wider text-ink-soft">Shelf</h2>
        <nav class="flex flex-col gap-1">
          <button
            type="button"
            class="rounded px-2 py-1 text-left text-sm"
            :class="tab === 'active' ? 'bg-paper-shade font-semibold text-ink' : 'text-ink-soft'"
            @click="tab = 'active'"
          >
            Active ({{ active.length }})
          </button>
          <button
            type="button"
            class="rounded px-2 py-1 text-left text-sm"
            :class="tab === 'archived' ? 'bg-paper-shade font-semibold text-ink' : 'text-ink-soft'"
            @click="tab = 'archived'"
          >
            Archived ({{ archived.length }})
          </button>
        </nav>
      </div>

      <div v-if="tab === 'active' && schoolYears.length > 1">
        <h2 class="mb-2 text-xs font-semibold uppercase tracking-wider text-ink-soft">School year</h2>
        <nav class="flex flex-col gap-1">
          <button
            type="button"
            class="rounded px-2 py-1 text-left text-sm"
            :class="schoolYearFilter === null ? 'bg-paper-shade font-semibold text-ink' : 'text-ink-soft'"
            @click="schoolYearFilter = null"
          >
            All years
          </button>
          <button
            v-for="year in schoolYears"
            :key="year"
            type="button"
            class="rounded px-2 py-1 text-left text-sm"
            :class="schoolYearFilter === year ? 'bg-paper-shade font-semibold text-ink' : 'text-ink-soft'"
            @click="schoolYearFilter = year"
          >
            {{ formatSchoolYear(year) }}
          </button>
        </nav>
      </div>

      <RouterLink to="/profile" class="text-sm text-ink-soft underline">Profile &amp; address</RouterLink>
    </aside>

    <section class="min-w-0 flex-1">
      <header class="mb-6 flex items-start justify-between gap-4">
        <div>
          <h1 class="font-display text-3xl font-bold text-ink">
            Magandang araw{{ auth.user ? `, ${auth.user.first_name}` : '' }}!
          </h1>
          <p class="text-sm text-ink-soft">{{ formatSchoolYear(schoolYears[0] ?? '') || 'Your notebooks' }}</p>
        </div>

        <div class="flex items-center gap-3">
          <div class="relative">
            <button
              type="button"
              class="relative flex h-10 w-10 items-center justify-center rounded-full text-xl text-ink hover:bg-paper-shade"
              aria-label="Notifications"
              @click="openBell"
            >
              🔔
              <span
                v-if="unread > 0"
                class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-margin px-1 text-[10px] font-bold text-paper"
              >
                {{ unread > 9 ? '9+' : unread }}
              </span>
            </button>

            <div
              v-if="bellOpen"
              class="absolute right-0 z-40 mt-1 w-80 overflow-hidden rounded-lg border border-paper-shade bg-paper shadow-xl"
            >
              <NotificationList
                :notifications="notifications"
                :loading="notificationsLoading"
                :has-more="moreNotifications"
                @open="onNotification"
                @mark-all="markAllRead"
                @load-more="loadMoreNotifications"
              />
            </div>
          </div>

          <button
            type="button"
            class="rounded bg-margin px-4 py-2 font-medium text-paper"
            @click="dialogOpen = true"
          >
            New notebook
          </button>
        </div>
      </header>

      <FormErrors :message="errors.message" :errors="errors.fields" class="mb-4" />

      <p v-if="loading" class="text-sm text-ink-faint">Loading your shelf…</p>

      <template v-else>
        <NotebookGrid
          v-if="tab === 'active'"
          :notebooks="active"
          :types="types"
          :cover-urls="coverUrls"
          empty-message="No notebooks yet — create your first one."
          @open="open"
          @menu="onMenu"
        />
        <ArchiveShelf v-else :notebooks="archived" :types="types" @open="open" @menu="onMenu" />
      </template>
    </section>

    <NewNotebookDialog
      :open="dialogOpen"
      :types="types"
      :saving="saving"
      :error-message="errors.message"
      :error-fields="errors.fields"
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
