// Vite env surface this package reads. Declared locally so the package
// typechecks standalone (tsc --noEmit) without depending on vite/client.
interface ImportMetaEnv {
  readonly VITE_API_URL?: string
  readonly VITE_USE_MOCK?: string
  readonly VITE_DEMO_MODE?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
