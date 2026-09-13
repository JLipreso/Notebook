import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { setBearerToken } from '@notebook/services'

// The ONE global store (CLAUDE.md §3: Pinia stays auth-only; per-view state
// lives in composables). Holds the Sanctum bearer obtained from the Firebase
// exchange (D-015); the sign-in flow itself lands with M1 implementation.
export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(null)

  const isAuthenticated = computed(() => token.value !== null)

  function setSession(bearer: string): void {
    token.value = bearer
    setBearerToken(bearer)
  }

  function clearSession(): void {
    token.value = null
    setBearerToken(null)
  }

  return { token, isAuthenticated, setSession, clearSession }
})
