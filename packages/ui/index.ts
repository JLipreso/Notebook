// @notebook/ui — the product's visual core, shared by Student (write) and
// Teacher (lesson authoring + viewing shared pages) so it is never forked:
//   auth/   — shared form field, error bag and Google button (Phase 004)
//   address/— AddressSelect.vue, the four-level PSGC chain (Phase 005)
//   notebook/— the shelf: cards, grid, create dialog, archive (Phase 006)
//   editor/ — Tiptap wrapper, toolbar and usePages autosave (Phase 007)
//   paper/  — PaperPage.vue rendering notebook_types.page_template rulings (Phase 007)
//   brand/  — tailwind-preset.cjs, consumed by every app's tailwind.config

export const UI_PACKAGE_NAME = '@notebook/ui'

export { default as FormField } from './auth/FormField.vue'
export { default as FormErrors } from './auth/FormErrors.vue'
export { default as GoogleButton } from './auth/GoogleButton.vue'
export { useAuthForm, extractErrors, type AuthFormErrors } from './auth/useAuthForm'

export { default as AddressSelect } from './address/AddressSelect.vue'
export { default as SelectField } from './address/SelectField.vue'
export { useProfile } from './address/useProfile'

export { default as NotebookCard } from './notebook/NotebookCard.vue'
export { default as NotebookCover } from './notebook/NotebookCover.vue'
export { default as NotebookGrid } from './notebook/NotebookGrid.vue'
export { default as NewNotebookDialog } from './notebook/NewNotebookDialog.vue'
export { default as PaperPreview } from './notebook/PaperPreview.vue'
export { default as ArchiveShelf } from './notebook/ArchiveShelf.vue'
export { useNotebooks } from './notebook/useNotebooks'

export { default as PaperPage } from './paper/PaperPage.vue'
export { default as NotebookEditor } from './editor/NotebookEditor.vue'
export { default as EditorToolbar } from './editor/EditorToolbar.vue'
export { usePages, type SaveState } from './editor/usePages'
export { default as AttachmentBar } from './editor/AttachmentBar.vue'
export { useAttachments } from './editor/useAttachments'
