<script setup lang="ts">
import type { AppNotification } from '@notebook/types'

// Shared list body — the browser hangs it in a dropdown, mobile renders it as a
// full screen. Neither reimplements the row (D-027).
defineProps<{
  notifications: AppNotification[]
  loading?: boolean
  hasMore?: boolean
}>()

defineEmits<{
  open: [notification: AppNotification]
  markAll: []
  loadMore: []
}>()

function whenLabel(iso: string): string {
  const diff = Date.now() - new Date(iso).getTime()
  const mins = Math.round(diff / 60000)

  if (mins < 1) return 'just now'
  if (mins < 60) return `${mins}m ago`
  if (mins < 1440) return `${Math.round(mins / 60)}h ago`
  return new Date(iso).toLocaleDateString()
}
</script>

<template>
  <div class="flex flex-col">
    <header class="flex items-center justify-between px-4 py-2">
      <span class="text-xs font-semibold uppercase tracking-wider text-ink-soft">Notifications</span>
      <button
        v-if="notifications.some((n) => !n.read_at)"
        type="button"
        class="text-xs text-ink underline"
        @click="$emit('markAll')"
      >
        Mark all read
      </button>
    </header>

    <p v-if="loading && notifications.length === 0" class="px-4 py-6 text-sm text-ink-faint">
      Loading…
    </p>
    <p v-else-if="notifications.length === 0" class="px-4 py-6 text-sm text-ink-soft">
      Nothing here yet.
    </p>

    <ul v-else class="flex flex-col">
      <li v-for="notification in notifications" :key="notification.id">
        <button
          type="button"
          class="flex w-full gap-3 border-t border-paper-shade px-4 py-3 text-left hover:bg-paper-shade/40"
          @click="$emit('open', notification)"
        >
          <span
            class="mt-1.5 h-2 w-2 flex-none rounded-full"
            :class="notification.read_at ? 'bg-transparent' : 'bg-margin'"
            aria-hidden="true"
          />
          <span class="min-w-0 flex-1">
            <span class="block text-sm font-medium text-ink-text">{{ notification.title }}</span>
            <span v-if="notification.body" class="mt-0.5 block text-xs text-ink-soft">
              {{ notification.body }}
            </span>
            <span class="mt-1 block text-[11px] text-ink-faint">
              {{ whenLabel(notification.created_at) }}
            </span>
          </span>
        </button>
      </li>
    </ul>

    <button
      v-if="hasMore"
      type="button"
      class="border-t border-paper-shade px-4 py-2 text-xs text-ink underline"
      @click="$emit('loadMore')"
    >
      Load older
    </button>
  </div>
</template>
