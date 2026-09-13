// @notebook/ui — the product's visual core, shared by Student (write) and
// Teacher (lesson authoring + viewing shared pages) so it is never forked:
//   auth/   — shared form field, error bag and Google button (Phase 004)
//   editor/ — Tiptap 2 wrapper + custom extensions + node whitelist (Phase 007)
//   paper/  — PaperPage.vue rendering notebook_types.page_template rulings (Phase 007)
//   brand/  — tailwind-preset.cjs, consumed by every app's tailwind.config

export const UI_PACKAGE_NAME = '@notebook/ui'

export { default as FormField } from './auth/FormField.vue'
export { default as FormErrors } from './auth/FormErrors.vue'
export { default as GoogleButton } from './auth/GoogleButton.vue'
export { useAuthForm, extractErrors, type AuthFormErrors } from './auth/useAuthForm'
