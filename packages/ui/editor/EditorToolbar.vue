<script setup lang="ts">
import type { Editor } from '@tiptap/vue-3'

// Formatting controls for the whitelist — and ONLY the whitelist. A button here
// for a node the server strips would be a lie to the student.
const props = withDefaults(
  defineProps<{
    editor: Editor | null
    /** Phase 008: show the attach button. Hidden where uploads make no sense. */
    canAttach?: boolean
    uploading?: boolean
  }>(),
  { canAttach: false, uploading: false },
)

const emit = defineEmits<{ attach: [] }>()

type Action = { key: string; label: string; title: string; run: () => void; active: () => boolean }

const actions: Action[] = [
  {
    key: 'bold',
    label: 'B',
    title: 'Bold',
    run: () => props.editor?.chain().focus().toggleBold().run(),
    active: () => props.editor?.isActive('bold') ?? false,
  },
  {
    key: 'italic',
    label: 'I',
    title: 'Italic',
    run: () => props.editor?.chain().focus().toggleItalic().run(),
    active: () => props.editor?.isActive('italic') ?? false,
  },
  {
    key: 'underline',
    label: 'U',
    title: 'Underline',
    run: () => props.editor?.chain().focus().toggleUnderline().run(),
    active: () => props.editor?.isActive('underline') ?? false,
  },
  {
    key: 'strike',
    label: 'S',
    title: 'Strikethrough',
    run: () => props.editor?.chain().focus().toggleStrike().run(),
    active: () => props.editor?.isActive('strike') ?? false,
  },
  {
    key: 'h1',
    label: 'H1',
    title: 'Heading 1',
    run: () => props.editor?.chain().focus().toggleHeading({ level: 1 }).run(),
    active: () => props.editor?.isActive('heading', { level: 1 }) ?? false,
  },
  {
    key: 'h2',
    label: 'H2',
    title: 'Heading 2',
    run: () => props.editor?.chain().focus().toggleHeading({ level: 2 }).run(),
    active: () => props.editor?.isActive('heading', { level: 2 }) ?? false,
  },
  {
    key: 'bullet',
    label: '•',
    title: 'Bullet list',
    run: () => props.editor?.chain().focus().toggleBulletList().run(),
    active: () => props.editor?.isActive('bulletList') ?? false,
  },
  {
    key: 'ordered',
    label: '1.',
    title: 'Numbered list',
    run: () => props.editor?.chain().focus().toggleOrderedList().run(),
    active: () => props.editor?.isActive('orderedList') ?? false,
  },
  {
    key: 'table',
    label: '⊞',
    title: 'Insert table',
    run: () =>
      props.editor?.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(),
    active: () => props.editor?.isActive('table') ?? false,
  },
  {
    key: 'rule',
    label: '—',
    title: 'Horizontal rule',
    run: () => props.editor?.chain().focus().setHorizontalRule().run(),
    active: () => false,
  },
]
</script>

<template>
  <div class="flex flex-wrap items-center gap-1" role="toolbar" aria-label="Formatting">
    <button
      v-for="action in actions"
      :key="action.key"
      type="button"
      :title="action.title"
      :aria-label="action.title"
      :aria-pressed="action.active()"
      :disabled="!editor"
      class="min-w-[32px] rounded px-2 py-1 text-sm font-semibold transition disabled:opacity-40"
      :class="action.active() ? 'bg-ink text-paper' : 'text-ink hover:bg-paper-shade'"
      @click="action.run()"
    >
      {{ action.label }}
    </button>

    <button
      v-if="canAttach"
      type="button"
      title="Attach image or PDF"
      aria-label="Attach image or PDF"
      :disabled="!editor || uploading"
      class="min-w-[32px] rounded px-2 py-1 text-sm font-semibold text-ink transition hover:bg-paper-shade disabled:opacity-40"
      @click="emit('attach')"
    >
      {{ uploading ? '…' : '📎' }}
    </button>
  </div>
</template>
