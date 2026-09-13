import axios, { AxiosError } from 'axios'

// The ONE axios instance every service uses. Base URL from the app's env;
// Sanctum bearer attached once a session exists (D-015: Firebase → Sanctum exchange).
export const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://127.0.0.1:8000/api',
  headers: { Accept: 'application/json' },
})

let bearerToken: string | null = null

export function setBearerToken(token: string | null): void {
  bearerToken = token
}

export function getBearerToken(): string | null {
  return bearerToken
}

http.interceptors.request.use((config) => {
  if (bearerToken) config.headers.Authorization = `Bearer ${bearerToken}`
  return config
})

/**
 * Called on a 401 so the auth store can re-exchange the Firebase ID token
 * (D-015) and retry once. Registered by the app's auth store in Phase 004 —
 * this module stays ignorant of Firebase.
 */
type ReauthHandler = () => Promise<string | null>

let onUnauthorized: ReauthHandler | null = null

export function setUnauthorizedHandler(handler: ReauthHandler | null): void {
  onUnauthorized = handler
}

http.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const config = error.config as (typeof error.config & { _retried?: boolean }) | undefined

    // One silent re-exchange, then give up — never a retry loop.
    if (error.response?.status === 401 && onUnauthorized && config && !config._retried) {
      config._retried = true
      const token = await onUnauthorized()

      if (token) {
        setBearerToken(token)
        config.headers.Authorization = `Bearer ${token}`
        return http.request(config)
      }
    }

    return Promise.reject(error)
  },
)
