import type { CapacitorConfig } from '@capacitor/cli'

// appId is the permanent store identity (Play Store package name — effectively
// unchangeable after the first upload). Locked as D-030: com.notebook.student
// here; com.notebook.teacher is reserved for the teacher app.
const config: CapacitorConfig = {
  appId: 'com.notebook.student',
  appName: 'Notebook',
  webDir: 'www',
}

export default config
