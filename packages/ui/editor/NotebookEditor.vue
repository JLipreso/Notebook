<script setup lang="ts">
import { onBeforeUnmount, watch } from 'vue'
import { Editor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Image from '@tiptap/extension-image'
import Placeholder from '@tiptap/extension-placeholder'
import { TableKit } from '@tiptap/extension-table'
import type { PageContent } from '@notebook/types'

/**
 * The page editor (Phase 007, D-012/D-018).
 *
 * JSON in, JSON out — there is NO HTML anywhere in this component's data path.
 * `content` is a Tiptap document and stays one all the way to the database.
 *
 * ======================== THE WHITELIST IS TWO-SIDED ========================
 * The enabled nodes/marks must match ALLOWED_NODES / ALLOWED_MARKS in
 * @notebook/types AND config/notebook.php on the backend. StarterKit ships
 * MORE than we allow — blockquote, code, codeBlock and link are switched off
 * below on purpose. Leaving one on would let the editor produce a node the
 * server silently strips on save, which reads to a student as "my work
 * vanished".
 *
 * The drawing/ink node is deliberately absent — reserved for post-M1 (D-012).
 * ===========================================================================
 */
const props = withDefaults(
  defineProps<{
    content: PageContent | null
    editable?: boolean
    placeholder?: string
  }>(),
  { editable: true, placeholder: 'Start writing…' },
)

const emit = defineEmits<{ 'update:content': [content: PageContent] }>()

const editor = new Editor({
  content: props.content ?? { type: 'doc', content: [{ type: 'paragraph' }] },
  editable: props.editable,
  extensions: [
    StarterKit.configure({
      // Allowed, with headings capped at the two levels the paper's type scale
      // supports (the sanitizer enforces the same cap).
      heading: { levels: [1, 2] },
      // NOT on the whitelist — off deliberately. See the block comment above.
      blockquote: false,
      code: false,
      codeBlock: false,
      link: false,
    }),
    TableKit.configure({ table: { resizable: false } }),
    // The node is enabled now so a page written by a newer client round-trips;
    // the toolbar affordance to INSERT one lands in Phase 008 (attachments).
    Image.configure({ inline: false }),
    Placeholder.configure({ placeholder: () => props.placeholder }),
  ],
  onUpdate: ({ editor: instance }) => {
    emit('update:content', instance.getJSON() as PageContent)
  },
})

// Re-hydrate when the page changes underneath us (switching pages, or a sync
// pull landing a newer copy). `emitUpdate: false` stops that write echoing
// straight back out as a user edit.
watch(
  () => props.content,
  (next) => {
    if (!next) return
    if (JSON.stringify(next) === JSON.stringify(editor.getJSON())) return
    editor.commands.setContent(next, { emitUpdate: false })
  },
)

watch(
  () => props.editable,
  (value) => editor.setEditable(value),
)

onBeforeUnmount(() => editor.destroy())

defineExpose({ editor })
</script>

<template>
  <EditorContent :editor="editor" class="h-full" />
</template>
