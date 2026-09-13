// The adapter seam that keeps every shell cheap (D-025): views NEVER call a
// native/desktop API raw — they ask this module. Adding Electron or iOS is a
// new branch here + a storage adapter, never a rewrite.

type CapacitorGlobal = { isNativePlatform?: () => boolean; getPlatform?: () => string }

function capacitor(): CapacitorGlobal | undefined {
  return (globalThis as { Capacitor?: CapacitorGlobal }).Capacitor
}

export type Platform = 'web' | 'android' | 'ios' | 'electron'

export function currentPlatform(): Platform {
  const cap = capacitor()
  if (cap?.isNativePlatform?.()) {
    return cap.getPlatform?.() === 'ios' ? 'ios' : 'android'
  }
  if (typeof navigator !== 'undefined' && navigator.userAgent.includes('Electron')) {
    return 'electron'
  }
  return 'web'
}

/** True where an on-device SQLite database backs the RW offline tables (D-016). */
export function hasNativeStorage(): boolean {
  const platform = currentPlatform()
  return platform === 'android' || platform === 'ios'
}
