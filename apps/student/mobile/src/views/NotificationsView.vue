<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { NotificationList, useNotifications } from '@notebook/ui'
import type { AppNotification } from '@notebook/types'

// Mobile: notifications are their own screen rather than a dropdown — the same
// shared NotificationList the browser hangs in a popover (D-027).
const router = useRouter()

const {
  notifications,
  loading,
  hasMore,
  load,
  loadMore,
  markRead,
  markAllRead,
  destinationFor,
} = useNotifications()

onMounted(() => load(1))

async function onNotification(notification: AppNotification): Promise<void> {
  await markRead(notification.id)

  const destination = destinationFor(notification)
  if (!destination) return

  // 'library' is the home route in this app.
  const name = destination.name === 'library' ? 'home' : destination.name
  router.push({ name, params: destination.params as never }).catch(() => {})
}
</script>

<template>
  <main class="flex min-h-screen flex-col bg-paper">
    <header class="flex flex-none items-center gap-2 px-3 py-2">
      <button
        type="button"
        class="flex h-11 w-11 items-center justify-center text-2xl leading-none text-ink"
        aria-label="Back"
        @click="router.push('/')"
      >
        ‹
      </button>
      <h1 class="font-display text-lg font-semibold text-ink">Notifications</h1>
    </header>

    <NotificationList
      :notifications="notifications"
      :loading="loading"
      :has-more="hasMore"
      @open="onNotification"
      @mark-all="markAllRead"
      @load-more="loadMore"
    />
  </main>
</template>
