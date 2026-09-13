<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { FormErrors, FormField, GoogleButton, useAuthForm } from '@notebook/ui'
import type { SignUpProfile } from '@notebook/services'
import { useAuthStore } from '@/stores/auth'

// Portrait sign-up. Address is collected later in the profile screen (Phase 005).
const auth = useAuthStore()
const router = useRouter()
const { loading, errors, submit } = useAuthForm()

const email = ref('')
const password = ref('')

const profile = reactive<SignUpProfile>({
  first_name: '',
  last_name: '',
  middle_name: '',
  birthday: '',
  mobile_number: '',
  role: 'student',
})

function payload(): SignUpProfile {
  return { ...profile, middle_name: profile.middle_name || null }
}

async function onSubmit(): Promise<void> {
  const ok = await submit(() => auth.signUp(email.value, password.value, payload()))
  if (ok) await router.replace('/')
}

async function onGoogle(): Promise<void> {
  const ok = await submit(() => auth.signInWithGoogle(payload()))
  if (ok) await router.replace('/')
}
</script>

<template>
  <main class="flex min-h-screen flex-col bg-paper px-6 py-10">
    <h1 class="font-display text-3xl font-bold text-ink">Create your account</h1>
    <p class="mt-1 text-sm text-ink-soft">Your notebooks are yours forever.</p>

    <form class="mt-6 flex flex-col gap-4" @submit.prevent="onSubmit">
      <FormErrors :message="errors.message" :errors="errors.fields" />

      <FormField v-model="profile.first_name" label="First name" required autocomplete="given-name" />
      <FormField v-model="profile.last_name" label="Last name" required autocomplete="family-name" />
      <FormField v-model="profile.middle_name as string" label="Middle name" autocomplete="additional-name" />
      <FormField v-model="profile.birthday" label="Birthday" type="date" required autocomplete="bday" />
      <FormField
        v-model="profile.mobile_number"
        label="Mobile number"
        type="tel"
        placeholder="09XX XXX XXXX"
        required
        autocomplete="tel"
      />

      <div class="flex flex-col gap-1">
        <span class="text-sm font-medium text-ink">I am a</span>
        <div class="flex gap-4">
          <label class="flex items-center gap-2 text-ink">
            <input v-model="profile.role" type="radio" value="student" /> Student
          </label>
          <label class="flex items-center gap-2 text-ink">
            <input v-model="profile.role" type="radio" value="teacher" /> Teacher
          </label>
        </div>
      </div>

      <FormField v-model="email" label="Email" type="email" required autocomplete="email" />
      <FormField
        v-model="password"
        label="Password"
        type="password"
        required
        autocomplete="new-password"
        placeholder="At least 6 characters"
      />

      <button
        type="submit"
        :disabled="loading"
        class="mt-2 rounded bg-ink px-4 py-3 font-medium text-paper disabled:opacity-50"
      >
        {{ loading ? 'Creating account…' : 'Create account' }}
      </button>
    </form>

    <div class="my-5 flex items-center gap-3 text-xs text-ink-faint">
      <span class="h-px flex-1 bg-paper-shade" />or<span class="h-px flex-1 bg-paper-shade" />
    </div>

    <GoogleButton label="Sign up with Google" :disabled="loading" @click="onGoogle" />

    <p class="mt-8 text-center text-sm text-ink-soft">
      Already have an account?
      <RouterLink to="/sign-in" class="font-medium text-ink underline">Sign in</RouterLink>
    </p>
  </main>
</template>
