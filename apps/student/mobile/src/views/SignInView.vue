<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { FormErrors, FormField, GoogleButton, useAuthForm } from '@notebook/ui'
import { useAuthStore } from '@/stores/auth'

// Mobile layout: full-screen portrait flow. Same shared pieces and same store
// as the browser app — only the composition differs (D-027).
const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const { loading, errors, submit } = useAuthForm()

const email = ref('')
const password = ref('')

const demoMode = import.meta.env.VITE_DEMO_MODE === 'true'

function fillDemo(): void {
  email.value = 'maria.santos@example.com'
  password.value = 'password'
}

async function onSubmit(): Promise<void> {
  const ok = await submit(() => auth.signIn(email.value, password.value))
  if (ok) await router.replace((route.query.redirect as string) ?? '/')
}

async function onGoogle(): Promise<void> {
  const ok = await submit(() => auth.signInWithGoogle())
  if (ok) await router.replace((route.query.redirect as string) ?? '/')
}
</script>

<template>
  <main class="flex min-h-screen flex-col justify-center bg-paper px-6 py-10">
    <h1 class="font-display text-4xl font-bold text-ink">Welcome back</h1>
    <p class="mt-1 text-sm text-ink-soft">Sign in to your notebooks.</p>

    <form class="mt-8 flex flex-col gap-4" @submit.prevent="onSubmit">
      <FormErrors :message="errors.message" :errors="errors.fields" />

      <FormField v-model="email" label="Email" type="email" autocomplete="email" required />
      <FormField
        v-model="password"
        label="Password"
        type="password"
        autocomplete="current-password"
        required
      />

      <button
        type="submit"
        :disabled="loading"
        class="mt-2 rounded bg-ink px-4 py-3 font-medium text-paper disabled:opacity-50"
      >
        {{ loading ? 'Signing in…' : 'Sign in' }}
      </button>
    </form>

    <div class="my-5 flex items-center gap-3 text-xs text-ink-faint">
      <span class="h-px flex-1 bg-paper-shade" />or<span class="h-px flex-1 bg-paper-shade" />
    </div>

    <GoogleButton :disabled="loading" @click="onGoogle" />

    <p class="mt-8 text-center text-sm text-ink-soft">
      New here?
      <RouterLink to="/sign-up" class="font-medium text-ink underline">Create an account</RouterLink>
    </p>

    <button
      v-if="demoMode"
      type="button"
      class="mt-4 w-full text-xs text-ink-faint underline"
      @click="fillDemo"
    >
      Fill demo credentials
    </button>
  </main>
</template>
