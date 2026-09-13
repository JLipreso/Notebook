import { ref } from 'vue'
import type { SignUpProfile } from '@notebook/services'

/**
 * The shared auth-form state machine (Phase 004). Both form factors drive their
 * own layout but neither re-implements submit handling, error extraction or the
 * loading flag — that logic is identical and belongs here (D-027 guardrail).
 */
export interface AuthFormErrors {
  message: string | null
  fields: Record<string, string[]> | null
}

export function useAuthForm() {
  const loading = ref(false)
  const errors = ref<AuthFormErrors>({ message: null, fields: null })

  function reset(): void {
    errors.value = { message: null, fields: null }
  }

  /** Run an auth action, mapping any failure into the shared error shape. */
  async function submit(action: () => Promise<void>): Promise<boolean> {
    loading.value = true
    reset()

    try {
      await action()
      return true
    } catch (error) {
      errors.value = extractErrors(error)
      return false
    } finally {
      loading.value = false
    }
  }

  return { loading, errors, reset, submit }
}

/**
 * Pull a message out of whatever failed: a Laravel envelope (422 with an
 * `errors` bag), a Firebase SDK error code, or anything else.
 */
export function extractErrors(error: unknown): AuthFormErrors {
  const response = (error as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } })
    ?.response

  if (response?.data) {
    return {
      message: response.data.message ?? 'Something went wrong.',
      fields: response.data.errors ?? null,
    }
  }

  const code = (error as { code?: string })?.code

  if (typeof code === 'string' && code.startsWith('auth/')) {
    return { message: firebaseMessage(code), fields: null }
  }

  return {
    message: error instanceof Error ? error.message : 'Something went wrong.',
    fields: null,
  }
}

/** Firebase error codes are not user-facing copy — translate the common ones. */
function firebaseMessage(code: string): string {
  switch (code) {
    case 'auth/invalid-email':
      return 'That email address looks incorrect.'
    case 'auth/invalid-credential':
    case 'auth/wrong-password':
    case 'auth/user-not-found':
      return 'Email or password is incorrect.'
    case 'auth/email-already-in-use':
      return 'An account already exists for that email. Try signing in.'
    case 'auth/weak-password':
      return 'Choose a password of at least 6 characters.'
    case 'auth/popup-closed-by-user':
      return 'The Google sign-in window was closed.'
    case 'auth/network-request-failed':
      return 'No connection. Check your internet and try again.'
    case 'auth/too-many-requests':
      return 'Too many attempts. Please wait a moment and try again.'
    default:
      return 'Sign-in failed. Please try again.'
  }
}

export type { SignUpProfile }
