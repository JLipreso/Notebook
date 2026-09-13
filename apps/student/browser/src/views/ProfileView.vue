<script setup lang="ts">
import { onMounted } from 'vue'
import { AddressSelect, FormErrors, FormField, useProfile } from '@notebook/ui'
import { useAuthStore } from '@/stores/auth'

// Browser layout: a settings page. The mobile app composes the SAME shared
// pieces into a portrait stack screen (D-027 — apps hold layout only).
const auth = useAuthStore()
const { form, chain, loading, saving, saved, errors, load, save, formatChain } = useProfile()

onMounted(load)
</script>

<template>
  <main class="mx-auto max-w-2xl bg-paper p-8">
    <header class="mb-6">
      <h1 class="font-display text-3xl font-bold text-ink">Profile</h1>
      <p class="mt-1 text-sm text-ink-soft">
        Signed in as <span class="font-medium text-ink">{{ auth.user?.email }}</span>
        · {{ auth.user?.role }}
      </p>
    </header>

    <p v-if="loading" class="text-sm text-ink-faint">Loading profile…</p>

    <form v-else class="flex flex-col gap-5" @submit.prevent="save">
      <FormErrors :message="errors.message" :errors="errors.fields" />

      <p v-if="saved" role="status" class="rounded border border-paper-shade bg-paper-shade/40 px-3 py-2 text-sm text-ink">
        Profile saved.
      </p>

      <section class="flex flex-col gap-4">
        <h2 class="font-display text-xl font-semibold text-ink">Your details</h2>

        <div class="grid gap-4 sm:grid-cols-2">
          <FormField v-model="form.first_name as string" label="First name" required />
          <FormField v-model="form.last_name as string" label="Last name" required />
        </div>

        <FormField v-model="form.middle_name as string" label="Middle name" />

        <div class="grid gap-4 sm:grid-cols-2">
          <FormField v-model="form.birthday as string" label="Birthday" type="date" required />
          <FormField v-model="form.mobile_number as string" label="Mobile number" type="tel" required />
        </div>
      </section>

      <section class="flex flex-col gap-4">
        <h2 class="font-display text-xl font-semibold text-ink">Address</h2>
        <p v-if="chain" class="text-sm text-ink-soft">{{ formatChain() }}</p>

        <AddressSelect v-model="form.barangay_code" @update:chain="chain = $event" />

        <FormField
          v-model="form.address_line as string"
          label="House number and street"
          placeholder="12 Mabini Street"
        />
      </section>

      <div class="flex items-center gap-3">
        <button
          type="submit"
          :disabled="saving"
          class="rounded bg-ink px-5 py-2 font-medium text-paper disabled:opacity-50"
        >
          {{ saving ? 'Saving…' : 'Save changes' }}
        </button>
        <RouterLink to="/" class="text-sm text-ink-soft underline">Back to notebooks</RouterLink>
      </div>
    </form>
  </main>
</template>
