// Brand tokens come from the ONE shared preset (CLAUDE.md §4) — never extend
// colors here; extend the preset in packages/ui/brand.
module.exports = {
  presets: [require('../../../packages/ui/brand/tailwind-preset.cjs')],
  content: [
    './index.html',
    './src/**/*.{vue,ts,tsx}',
    // ui package source dirs listed explicitly — a bare packages/ui/** would
    // scan its node_modules and tank build performance
    '../../../packages/ui/index.ts',
    '../../../packages/ui/{brand,editor,paper}/**/*.{vue,ts}',
  ],
}
