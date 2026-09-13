import type { CapacitorConfig } from '@capacitor/cli'

// appId is the permanent store identity (Play Store package name — effectively
// unchangeable after the first upload). Placeholder pending O-4 confirmation;
// do NOT run `cap add android` until the Lead Developer confirms it.
const config: CapacitorConfig = {
  appId: 'com.wlabs.notebook',
  appName: 'Notebook',
  webDir: 'www',
}

export default config
