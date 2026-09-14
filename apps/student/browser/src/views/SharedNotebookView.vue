<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { PaperPage, ReadOnlyContent } from '@notebook/ui'
import { shareService } from '@notebook/services'
import type { SharedNotebookView } from '@notebook/types'

/**
 * The public share viewer (Phase 010) — browser app ONLY.
 *
 * ========================= THIS ROUTE IS SIGNED-OUT =========================
 * It must work with no session at all: someone receives a link and opens it.
 * So there is NO auth store here, NO editor mount, and the route is allowlisted
 * in the guard with meta.public.
 *
 * Mobile users who receive a link open it in their phone's browser — the phase
 * file is explicit that the viewer is not duplicated into the mobile app.
 * ===========================================================================
 */
const route = useRoute()
const token = route.params.token as string

const view = ref<SharedNotebookView | null>(null)
const loading = ref(true)
const gone = ref(false)

onMounted(async () => {
  try {
    const { data } = await shareService.resolve(token)
    view.value = data ?? null
    if (!data) gone.value = true
  } catch {
    // Every failure — revoked, expired, never existed — looks identical here,
    // exactly as the server intends.
    gone.value = true
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <main class="min-h-screen bg-paper-shade/40">
    <div v-if="loading" class="p-10 text-center text-sm text-ink-faint">Opening…</div>

    <div v-else-if="gone || !view" class="mx-auto max-w-md p-10 text-center">
      <h1 class="font-display text-2xl font-bold text-ink">This link is no longer available</h1>
      <p class="mt-2 text-sm text-ink-soft">
        It may have been revoked by its owner, or it may have expired. Ask them for a new link.
      </p>
    </div>

    <template v-else>
      <header class="border-b border-paper-shade bg-paper px-6 py-4">
        <div class="mx-auto max-w-3xl">
          <h1 class="font-display text-xl font-semibold text-ink">{{ view.notebook.title }}</h1>
          <p class="text-sm text-ink-soft">
            {{ view.notebook.notebook_type.name }} · {{ view.notebook.school_year }} ·
            <span class="font-medium">read-only</span>
          </p>
        </div>
      </header>

      <div class="mx-auto flex max-w-3xl flex-col gap-8 p-6">
        <article v-for="page in view.pages" :key="page.id" class="shadow-lg" style="min-height: 700px">
          <PaperPage :template="view.notebook.notebook_type.page_template">
            <!-- Rendered, never editable: no editor is mounted on this route. -->
            <ReadOnlyContent :content="page.content" />
          </PaperPage>
        </article>

        <p class="pb-8 text-center text-xs text-ink-faint">
          Shared from Notebook · {{ view.pages.length }}
          {{ view.pages.length === 1 ? 'page' : 'pages' }}
        </p>
      </div>
    </template>
  </main>
</template>
