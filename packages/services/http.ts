import axios from 'axios'

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

http.interceptors.request.use((config) => {
  if (bearerToken) config.headers.Authorization = `Bearer ${bearerToken}`
  return config
})

// 401 handling (drop the token; the auth store re-exchanges the Firebase ID
// token per D-015) is wired when the auth service lands in M1 implementation.
