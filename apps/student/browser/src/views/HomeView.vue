<script setup lang="ts">
import { deriveSchoolYear, formatSchoolYear } from '@notebook/utility'
import { useMock } from '@notebook/services'

const schoolYear = formatSchoolYear(deriveSchoolYear())
</script>

<template>
  <main class="mx-auto flex min-h-screen max-w-2xl flex-col items-center justify-center gap-3 p-8 text-center">
    <h1 class="font-display text-5xl font-bold">Notebook</h1>
    <p class="text-ink-soft">Your notebooks for {{ schoolYear }}, on every screen you own.</p>
    <p class="text-sm text-ink-faint">
      Student · browser (laptop/desktop) · datasource:
      <span class="font-semibold">{{ useMock ? 'mock' : 'live API' }}</span>
    </p>
    <div class="mt-2 h-1 w-24 rounded bg-margin" aria-hidden="true"></div>
  </main>
</template>
