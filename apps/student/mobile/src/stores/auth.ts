import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import {
  authService,
  firebaseAuth,
  setBearerToken,
  setUnauthorizedHandler,
  useMock,
} from '@notebook/services'
import type { SignUpProfile } from '@notebook/services'
import type { User } from '@notebook/types'

// The ONE global store (CLAUDE.md §3: Pinia stays auth-only; per-view state
// lives in composables). Firebase owns the credential, this store owns the
// Sanctum bearer it was exchanged for (D-015, Phase 004).

const TOKEN_KEY = 'notebook.token'

/** localStorage throws in some privacy modes — never let that break boot. */
function readStoredToken(): string | null {
  try {
    return localStorage.getItem(TOKEN_KEY)
  } catch {
    return null
  }
}

function writeStoredToken(token: string | null): void {
  try {
    if (token) localStorage.setItem(TOKEN_KEY, token)
    else localStorage.removeItem(TOKEN_KEY)
  } catch {
    // Non-fatal: the session simply won't survive a reload.
  }
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(null)
  const user = ref<User | null>(null)
  const ready = ref(false)

  const isAuthenticated = computed(() => token.value !== null && user.value !== null)

  function setSession(bearer: string, account: User): void {
    token.value = bearer
    user.value = account
    setBearerToken(bearer)
    writeStoredToken(bearer)
  }

  function clearSession(): void {
    token.value = null
    user.value = null
    setBearerToken(null)
    writeStoredToken(null)
  }

  async function signUp(email: string, password: string, profile: SignUpProfile): Promise<void> {
    const idToken = useMock ? 'mock' : await firebaseAuth.signUpWithEmail(email, password)
    const { data } = await authService.exchangeFirebaseToken(idToken, profile)
    if (data) setSession(data.token, data.user)
  }

  async function signIn(email: string, password: string): Promise<void> {
    const idToken = useMock ? 'mock' : await firebaseAuth.signInWithEmail(email, password)
    const { data } = await authService.exchangeFirebaseToken(idToken)
    if (data) setSession(data.token, data.user)
  }

  async function signInWithGoogle(profile?: SignUpProfile): Promise<void> {
    const idToken = useMock ? 'mock' : await firebaseAuth.signInWithGoogle()
    const { data } = await authService.exchangeFirebaseToken(idToken, profile)
    if (data) setSession(data.token, data.user)
  }

  async function signOut(): Promise<void> {
    try {
      await authService.logout()
    } finally {
      // Local state clears even if the revoke call failed — the user asked to
      // leave, and a stale server token expires on its own.
      await firebaseAuth.signOutFirebase().catch(() => {})
      clearSession()
    }
  }

  /**
   * Boot: restore a stored bearer and confirm it still works. A 401 here is
   * handled by the interceptor below (silent re-exchange); if that fails too,
   * we land signed-out rather than in a broken half-session.
   */
  async function restore(): Promise<void> {
    const stored = readStoredToken()

    if (stored) {
      setBearerToken(stored)
      token.value = stored

      try {
        const { data } = await authService.me()
        if (data) user.value = data
        else clearSession()
      } catch {
        clearSession()
      }
    }

    ready.value = true
  }

  // The 401 path (D-015): ask Firebase for a FRESH id token, exchange it for a
  // new bearer, and let http.ts retry the failed request exactly once.
  setUnauthorizedHandler(async () => {
    try {
      const idToken = await firebaseAuth.getIdToken(true)
      if (!idToken) {
        clearSession()
        return null
      }

      const { data } = await authService.exchangeFirebaseToken(idToken)
      if (!data) {
        clearSession()
        return null
      }

      setSession(data.token, data.user)
      return data.token
    } catch {
      clearSession()
      return null
    }
  })

  return {
    token,
    user,
    ready,
    isAuthenticated,
    setSession,
    clearSession,
    signUp,
    signIn,
    signInWithGoogle,
    signOut,
    restore,
  }
})
