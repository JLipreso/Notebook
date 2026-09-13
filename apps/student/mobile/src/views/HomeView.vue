<script setup lang="ts">
import { deriveSchoolYear, formatSchoolYear } from '@notebook/utility'
import { useMock } from '@notebook/services'
import { currentPlatform } from '@notebook/sync'

const schoolYear = formatSchoolYear(deriveSchoolYear())
const platform = currentPlatform()
</script>

<template>
  <main class="mx-auto flex min-h-screen max-w-sm flex-col items-center justify-center gap-3 p-6 text-center">
    <h1 class="font-display text-4xl font-bold">Notebook</h1>
    <p class="text-ink-soft">Your notebooks for {{ schoolYear }}, in your pocket.</p>
    <p class="text-sm text-ink-faint">
      Student · mobile (portrait) · running on <span class="font-semibold">{{ platform }}</span>
      · datasource: <span class="font-semibold">{{ useMock ? 'mock' : 'live API' }}</span>
    </p>
    <div class="mt-2 h-1 w-16 rounded bg-margin" aria-hidden="true"></div>
  </main>
</template>
