<script setup lang="ts">
import { onMounted } from 'vue'
import { AddressSelect, FormErrors, FormField, useProfile } from '@notebook/ui'
import { useAuthStore } from '@/stores/auth'

// Mobile layout: a portrait stack screen. Same shared pieces and same
// composable as the browser app — only the composition differs (D-027).
const auth = useAuthStore()
const { form, chain, loading, saving, saved, errors, load, save, formatChain } = useProfile()

onMounted(load)

async function onSignOut(): Promise<void> {
  await auth.signOut()
}
</script>

<template>
  <main class="flex min-h-screen flex-col bg-paper px-6 py-8">
    <header class="mb-6">
      <h1 class="font-display text-3xl font-bold text-ink">Profile</h1>
      <p class="mt-1 text-sm text-ink-soft">{{ auth.user?.email }}</p>
    </header>

    <p v-if="loading" class="text-sm text-ink-faint">Loading profile…</p>

    <form v-else class="flex flex-col gap-5" @submit.prevent="save">
      <FormErrors :message="errors.message" :errors="errors.fields" />

      <p v-if="saved" role="status" class="rounded border border-paper-shade bg-paper-shade/40 px-3 py-2 text-sm text-ink">
        Profile saved.
      </p>

      <FormField v-model="form.first_name as string" label="First name" required />
      <FormField v-model="form.last_name as string" label="Last name" required />
      <FormField v-model="form.middle_name as string" label="Middle name" />
      <FormField v-model="form.birthday as string" label="Birthday" type="date" required />
      <FormField v-model="form.mobile_number as string" label="Mobile number" type="tel" required />

      <h2 class="mt-2 font-display text-xl font-semibold text-ink">Address</h2>
      <p v-if="chain" class="text-sm text-ink-soft">{{ formatChain() }}</p>

      <AddressSelect v-model="form.barangay_code" @update:chain="chain = $event" />

      <FormField
        v-model="form.address_line as string"
        label="House number and street"
        placeholder="12 Mabini Street"
      />

      <button
        type="submit"
        :disabled="saving"
        class="mt-2 rounded bg-ink px-4 py-3 font-medium text-paper disabled:opacity-50"
      >
        {{ saving ? 'Saving…' : 'Save changes' }}
      </button>

      <RouterLink to="/" class="mt-2 text-center text-sm text-ink-soft underline">
        Back to notebooks
      </RouterLink>

      <button type="button" class="mt-6 text-sm text-margin underline" @click="onSignOut">
        Sign out
      </button>
    </form>
  </main>
</template>
