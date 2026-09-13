<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { EditorToolbar, FormErrors, NotebookEditor, PaperPage, usePages } from '@notebook/ui'
import type { PageContent } from '@notebook/types'

// Mobile layout: one page, portrait, arrows between pages — per the approved
// canvas (2026-09-13-006, board 07). Same shared PaperPage + NotebookEditor as
// the browser app; only the composition differs (D-027).
const route = useRoute()
const router = useRouter()
const notebookId = route.params.id as string

const {
  notebook,
  type,
  current,
  currentIndex,
  pageCount,
  loading,
  saveLabel,
  errors,
  load,
  onContentChange,
  flush,
  addPage,
  removePage,
  goTo,
} = usePages(notebookId)

const editorRef = ref<InstanceType<typeof NotebookEditor> | null>(null)

onMounted(load)
onBeforeUnmount(() => void flush())

function onUpdate(content: PageContent): void {
  onContentChange(content)
}

async function onDeletePage(): Promise<void> {
  const page = current.value
  if (!page) return
  if (!window.confirm(`Delete page ${currentIndex.value + 1}?`)) return
  await removePage(page.id)
}
</script>

<template>
  <main class="flex h-screen flex-col bg-paper">
    <header class="flex flex-none items-center gap-2 px-3 py-2">
      <button
        type="button"
        class="flex h-11 w-11 flex-none items-center justify-center text-2xl leading-none text-ink"
        aria-label="Back to library"
        @click="router.push('/')"
      >
        ‹
      </button>

      <div class="min-w-0 flex-1">
        <p class="truncate font-display text-base font-semibold text-ink">
          {{ notebook?.title ?? 'Notebook' }}
          <span v-if="type" class="font-body text-xs font-normal text-ink-soft">· {{ type.name }}</span>
        </p>
        <p class="text-xs text-ink-soft">{{ saveLabel }}</p>
      </div>

      <button
        type="button"
        class="flex h-11 w-11 flex-none items-center justify-center text-xl text-ink"
        aria-label="Add page"
        @click="addPage"
      >
        +
      </button>
    </header>

    <div class="flex-none overflow-x-auto border-y border-paper-shade px-3 py-1.5">
      <EditorToolbar :editor="editorRef?.editor ?? null" />
    </div>

    <FormErrors :message="errors.message" :errors="errors.fields" class="mx-3 mt-2" />

    <div v-if="loading" class="p-6 text-sm text-ink-faint">Opening notebook…</div>

    <section v-else class="flex min-h-0 flex-1 flex-col px-3 pb-2 pt-3">
      <div class="min-h-0 flex-1 overflow-hidden rounded-t shadow-lg">
        <PaperPage v-if="type && current" :key="current.id" :template="type.page_template">
          <NotebookEditor ref="editorRef" :content="current.content" @update:content="onUpdate" />
        </PaperPage>
      </div>

      <footer class="flex flex-none items-center justify-between py-2 text-sm text-ink-soft">
        <button
          type="button"
          class="px-2 disabled:opacity-40"
          :disabled="currentIndex === 0"
          aria-label="Previous page"
          @click="goTo(currentIndex - 1)"
        >
          ‹
        </button>

        <div class="flex items-center gap-4">
          <span class="text-xs font-semibold tracking-wide text-ink-faint">
            Page {{ currentIndex + 1 }} of {{ pageCount }}
          </span>
          <button type="button" class="text-xs text-margin underline" @click="onDeletePage">
            Delete
          </button>
        </div>

        <button
          type="button"
          class="px-2 disabled:opacity-40"
          :disabled="currentIndex >= pageCount - 1"
          aria-label="Next page"
          @click="goTo(currentIndex + 1)"
        >
          ›
        </button>
      </footer>
    </section>
  </main>
</template>
