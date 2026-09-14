<script setup lang="ts">
import type { PageAttachmentWithFile } from '@notebook/types'

// Non-image attachments as chips under the page. Images live INSIDE the
// document as editor nodes; PDFs cannot, so they get a list (phase file §2).
defineProps<{ attachments: PageAttachmentWithFile[]; uploading?: boolean }>()

defineEmits<{ open: [attachment: PageAttachmentWithFile]; remove: [id: string] }>()

function sizeLabel(bytes: number | undefined): string {
  if (!bytes) return ''
  const mb = bytes / 1024 / 1024
  return mb >= 1 ? `${mb.toFixed(1)} MB` : `${Math.max(1, Math.round(bytes / 1024))} KB`
}
</script>

<template>
  <div v-if="uploading || attachments.length" class="flex flex-wrap items-center gap-2">
    <span v-if="uploading" class="text-xs text-ink-soft">Uploading…</span>

    <div
      v-for="attachment in attachments"
      :key="attachment.id"
      class="flex items-center gap-2 rounded-full border border-paper-shade bg-paper-shade/40 px-3 py-1"
    >
      <button
        type="button"
        class="flex items-center gap-1.5 text-xs text-ink"
        @click="$emit('open', attachment)"
      >
        <span aria-hidden="true">📄</span>
        <span class="max-w-[160px] truncate">
          {{ attachment.file_upload?.original_name ?? 'Attachment' }}
        </span>
        <span class="text-ink-soft">{{ sizeLabel(attachment.file_upload?.size_bytes) }}</span>
      </button>

      <button
        type="button"
        class="text-ink-soft hover:text-margin"
        :aria-label="`Remove ${attachment.file_upload?.original_name ?? 'attachment'}`"
        @click="$emit('remove', attachment.id)"
      >
        ×
      </button>
    </div>
  </div>
</template>
