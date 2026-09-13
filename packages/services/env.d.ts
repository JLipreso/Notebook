// Vite env surface this package reads. Declared locally so the package
// typechecks standalone (tsc --noEmit) without depending on vite/client.
interface ImportMetaEnv {
  readonly VITE_API_URL?: string
  readonly VITE_USE_MOCK?: string
  readonly VITE_DEMO_MODE?: string
  // Firebase web app config (D-015) — public client identifiers, not secrets.
  readonly VITE_FIREBASE_API_KEY?: string
  readonly VITE_FIREBASE_AUTH_DOMAIN?: string
  readonly VITE_FIREBASE_PROJECT_ID?: string
  readonly VITE_FIREBASE_APP_ID?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
