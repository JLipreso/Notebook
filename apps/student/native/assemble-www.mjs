// Assembles www/ — the webDir Capacitor ships — from the form-factor builds (D-028).
// While the tablet design is deferred (D-029) this is a plain copy of the mobile
// build; when tablet joins, this script writes both builds into www/mobile/ and
// www/tablet/ plus a bootstrap index.html that picks one by screen size at launch.
import { cpSync, rmSync, existsSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import path from 'node:path'

const here = path.dirname(fileURLToPath(import.meta.url))
const mobileDist = path.join(here, '..', 'mobile', 'dist')
const www = path.join(here, 'www')

if (!existsSync(mobileDist)) {
  console.error('mobile/dist not found — run `pnpm build:student:mobile` from the repo root first.')
  process.exit(1)
}

rmSync(www, { recursive: true, force: true })
cpSync(mobileDist, www, { recursive: true })
console.log('www/ assembled from mobile/dist')
