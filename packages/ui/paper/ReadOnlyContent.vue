<script setup lang="ts">
import { computed, h, type VNode } from 'vue'
import { ALLOWED_MARKS, ALLOWED_NODES, type PageContent, type PageNode } from '@notebook/types'

/**
 * Render a Tiptap document READ-ONLY, without mounting an editor (Phase 010).
 *
 * ===================== WHY NOT JUST MOUNT THE EDITOR? =====================
 * The public share viewer is signed-out and must never be editable. Mounting
 * Tiptap with `editable: false` would still ship the whole ProseMirror runtime
 * to a reader and leave one boolean between a visitor and an edit surface.
 * This walks the JSON and emits plain elements instead — nothing to toggle.
 *
 * It renders the SAME whitelist the editor enables (@notebook/types), so shared
 * pages look identical to owned ones. Anything off-whitelist is skipped, which
 * means a document that somehow carried an unexpected node degrades to missing
 * content rather than to raw output.
 * ==========================================================================
 */
const props = defineProps<{ content: PageContent | null }>()

const MARK_TAG: Record<string, string> = {
  bold: 'strong',
  italic: 'em',
  underline: 'u',
  strike: 's',
}

const NODE_TAG: Record<string, string> = {
  paragraph: 'p',
  bulletList: 'ul',
  orderedList: 'ol',
  listItem: 'li',
  table: 'table',
  tableRow: 'tr',
  tableHeader: 'th',
  tableCell: 'td',
  horizontalRule: 'hr',
  hardBreak: 'br',
}

function renderText(node: PageNode): VNode | string {
  const text = node.text ?? ''
  const marks = (node.marks ?? []).filter((mark) =>
    (ALLOWED_MARKS as readonly string[]).includes(mark.type),
  )

  // Nest the mark tags around the text, innermost first.
  return marks.reduce<VNode | string>(
    (child, mark) => h(MARK_TAG[mark.type] ?? 'span', {}, [child]),
    text,
  )
}

function renderNode(node: PageNode, key: number): VNode | string | null {
  if (!(ALLOWED_NODES as readonly string[]).includes(node.type)) return null

  if (node.type === 'text') return renderText(node)

  if (node.type === 'image') {
    const src = typeof node.attrs?.src === 'string' ? node.attrs.src : null
    if (!src) return null
    return h('img', { key, src, alt: (node.attrs?.alt as string) ?? '' })
  }

  if (node.type === 'heading') {
    const level = Number(node.attrs?.level ?? 1)
    return h(level === 2 ? 'h2' : 'h1', { key }, renderChildren(node))
  }

  const tag = NODE_TAG[node.type]
  if (!tag) return null

  if (tag === 'hr' || tag === 'br') return h(tag, { key })

  return h(tag, { key }, renderChildren(node))
}

function renderChildren(node: PageNode): (VNode | string)[] {
  return (node.content ?? [])
    .map((child, index) => renderNode(child, index))
    .filter((child): child is VNode | string => child !== null)
}

/** The whole document as one VNode tree — rendered via <component :is>. */
const tree = computed(() =>
  h(
    'div',
    {},
    (props.content?.content ?? [])
      .map((node, index) => renderNode(node, index))
      .filter((node): node is VNode | string => node !== null),
  ),
)
</script>

<template>
  <component :is="tree" />
</template>
