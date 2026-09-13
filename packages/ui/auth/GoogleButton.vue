<script setup lang="ts">
defineProps<{ label?: string; disabled?: boolean }>()
defineEmits<{ click: [] }>()
</script>

<template>
  <button
    type="button"
    :disabled="disabled"
    class="flex w-full items-center justify-center gap-2 rounded border border-paper-shade bg-paper px-4 py-2 font-medium text-ink disabled:opacity-50"
    @click="$emit('click')"
  >
    <svg class="h-4 w-4" viewBox="0 0 24 24" aria-hidden="true">
      <path fill="#4285F4" d="M22 12.26c0-.74-.07-1.45-.19-2.13H12v4.03h5.62a4.8 4.8 0 0 1-2.08 3.15v2.62h3.36C20.87 18.1 22 15.42 22 12.26z" />
      <path fill="#34A853" d="M12 23c2.82 0 5.18-.94 6.9-2.54l-3.36-2.62c-.94.63-2.14 1-3.54 1-2.72 0-5.02-1.84-5.85-4.31H2.68v2.7A10.43 10.43 0 0 0 12 23z" />
      <path fill="#FBBC05" d="M6.15 14.53a6.3 6.3 0 0 1 0-4.02V7.8H2.68a10.43 10.43 0 0 0 0 9.4l3.47-2.67z" />
      <path fill="#EA4335" d="M12 5.5c1.54 0 2.91.53 3.99 1.56l2.99-2.99C17.18 2.38 14.82 1.4 12 1.4A10.43 10.43 0 0 0 2.68 7.8l3.47 2.7C6.98 8.04 9.28 5.5 12 5.5z" />
    </svg>
    {{ label ?? 'Continue with Google' }}
  </button>
</template>
