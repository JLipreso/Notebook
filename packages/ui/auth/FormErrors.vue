<script setup lang="ts">
// Renders the envelope's `errors` bag (ApiResponse.errors) plus a top-level
// message, so every auth screen reports validation the same way.
defineProps<{
  message?: string | null
  errors?: Record<string, string[]> | null
}>()
</script>

<template>
  <div
    v-if="message || (errors && Object.keys(errors).length)"
    role="alert"
    class="rounded border border-margin-soft bg-margin-soft/20 px-3 py-2 text-sm text-margin"
  >
    <p v-if="message">{{ message }}</p>
    <ul v-if="errors" class="list-inside list-disc">
      <li v-for="(messages, field) in errors" :key="field">{{ messages[0] }}</li>
    </ul>
  </div>
</template>
