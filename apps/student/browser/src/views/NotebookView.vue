<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  AttachmentBar,
  EditorToolbar,
  FormErrors,
  NotebookEditor,
  PaperPage,
  useAttachments,
  usePages,
} from '@notebook/ui'
import type { PageAttachmentWithFile, PageContent } from '@notebook/types'

// Browser layout: page rail on the left, one paper page centred. The mobile app
// composes the SAME PaperPage + NotebookEditor into a single-page portrait flow
// (D-027 — apps hold layout only).
const route = useRoute()
const router = useRouter()
const notebookId = route.params.id as string

const {
  notebook,
  type,
  pages,
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
const fileInput = ref<HTMLInputElement | null>(null)

const { attachments, uploading, errors: attachErrors, load: loadAttachments, attachFile, detach, kindFor } =
  useAttachments()

// Attachments belong to the PAGE, so reload them whenever the page changes.
watch(
  () => current.value?.id,
  (id) => {
    attachments.value = []
    if (id) void loadAttachments(id)
  },
  { immediate: true },
)

function pickFile(): void {
  fileInput.value?.click()
}

async function onFilePicked(event: Event): Promise<void> {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  // Reset immediately so picking the same file twice still fires a change.
  input.value = ''

  const page = current.value
  if (!file || !page) return

  const kind = kindFor(file)
  if (!kind) {
    window.alert('Only images and PDFs can be attached.')
    return
  }

  const url = await attachFile(page.id, file, kind)

  // An image goes INTO the document; a PDF stays a page-level chip.
  if (url && kind === 'image') {
    editorRef.value?.editor.chain().focus().setImage({ src: url }).run()
  }
}

function openAttachment(attachment: PageAttachmentWithFile): void {
  const url = attachment.file_upload?.url
  if (url) window.open(url, '_blank', 'noopener')
}

onMounted(load)

// Never lose an edit to navigation: the debounce may not have fired yet.
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
  <main class="flex h-screen flex-col bg-paper-shade/40">
    <header class="flex flex-none items-center gap-4 border-b border-paper-shade bg-paper px-6 py-3">
      <button
        type="button"
        class="text-xl leading-none text-ink"
        aria-label="Back to library"
        @click="router.push('/')"
      >
        ‹
      </button>

      <div class="min-w-0 flex-1">
        <h1 class="truncate font-display text-lg font-semibold text-ink">
          {{ notebook?.title ?? 'Notebook' }}
          <span v-if="type" class="font-body text-sm font-normal text-ink-soft">· {{ type.name }}</span>
        </h1>
        <p class="text-xs text-ink-soft">{{ saveLabel }}</p>
      </div>

      <EditorToolbar
        :editor="editorRef?.editor ?? null"
        can-attach
        :uploading="uploading"
        @attach="pickFile"
      />
    </header>

    <FormErrors :message="errors.message ?? attachErrors.message" :errors="errors.fields ?? attachErrors.fields" class="mx-6 mt-3" />

    <div v-if="loading" class="p-8 text-sm text-ink-faint">Opening notebook…</div>

    <div v-else class="flex min-h-0 flex-1">
      <!-- Page rail -->
      <aside class="hidden w-40 flex-none overflow-y-auto border-r border-paper-shade p-3 md:block">
        <button
          v-for="(page, index) in pages"
          :key="page.id"
          type="button"
          class="mb-2 w-full rounded border p-1 text-left transition"
          :class="index === currentIndex ? 'border-ink' : 'border-paper-shade hover:border-ink-faint'"
          @click="goTo(index)"
        >
          <div class="h-20 overflow-hidden rounded-sm">
            <PaperPage v-if="type" :template="type.page_template" :line-height="10" compact>
              <p class="truncate text-[6px] leading-[10px] text-ink-text">{{ page.search_text }}</p>
            </PaperPage>
          </div>
          <span class="mt-1 block text-center text-xs text-ink-soft">{{ index + 1 }}</span>
        </button>

        <button
          type="button"
          class="w-full rounded border border-dashed border-ink-faint py-2 text-sm text-ink-soft hover:border-ink"
          @click="addPage"
        >
          + Add page
        </button>
      </aside>

      <!-- The paper -->
      <section class="flex min-w-0 flex-1 flex-col items-center overflow-y-auto p-6">
        <div class="w-full max-w-3xl flex-1 shadow-lg" style="min-height: 700px">
          <PaperPage v-if="type && current" :key="current.id" :template="type.page_template">
            <NotebookEditor
              ref="editorRef"
              :content="current.content"
              @update:content="onUpdate"
            />
          </PaperPage>
        </div>

        <AttachmentBar
          :attachments="attachments"
          :uploading="uploading"
          class="mt-3 w-full max-w-3xl"
          @open="openAttachment"
          @remove="detach"
        />

        <footer class="mt-4 flex w-full max-w-3xl items-center justify-between text-sm text-ink-soft">
          <button type="button" class="underline disabled:opacity-40" :disabled="currentIndex === 0" @click="goTo(currentIndex - 1)">
            ‹ Previous
          </button>

          <span>Page {{ currentIndex + 1 }} of {{ pageCount }}</span>

          <div class="flex gap-4">
            <button type="button" class="text-margin underline" @click="onDeletePage">Delete page</button>
            <button
              type="button"
              class="underline disabled:opacity-40"
              :disabled="currentIndex >= pageCount - 1"
              @click="goTo(currentIndex + 1)"
            >
              Next ›
            </button>
          </div>
        </footer>
      </section>
    </div>

    <!-- Hidden picker: a plain file input works inside the Capacitor WebView,
         so the mobile shell needs no Camera plugin for MVP (phase file §2). -->
    <input
      ref="fileInput"
      type="file"
      accept="image/jpeg,image/png,image/webp,application/pdf"
      class="hidden"
      @change="onFilePicked"
    />
  </main>
</template>
