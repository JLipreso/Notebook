import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

// Aliases are declared in BOTH this file and tsconfig.app.json (CLAUDE.md §4) —
// change both or neither.
export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
      '@notebook/types': path.resolve(__dirname, '../../../packages/types'),
      '@notebook/services': path.resolve(__dirname, '../../../packages/services'),
      '@notebook/utility': path.resolve(__dirname, '../../../packages/utility'),
      '@notebook/ui': path.resolve(__dirname, '../../../packages/ui'),
      '@notebook/sync': path.resolve(__dirname, '../../../packages/sync'),
    },
  },
  server: {
    host: true,
    port: 5171,
  },
})
