// The ONE shared Tailwind preset (CLAUDE.md §4: brand tokens live here, not per app).
// Palette LOCKED as D-032 — Option A "Komposisyon", chosen by boss + client 2026-09-13
// (documents/2026-09-13-006-Brand-Colors/). Token NAMES are the API views consume;
// changing brand = changing this file only, never a view.
module.exports = {
  theme: {
    extend: {
      colors: {
        // notebook paper — the app ground
        paper: {
          DEFAULT: '#fbf8f0', // Cream Paper
          shade: '#f2ecdd',
        },
        // writing ink — brand navy for chrome/headings, text/soft/faint for copy
        ink: {
          DEFAULT: '#1e3a5f', // Ink Navy (brand)
          text: '#22344c',    // Ink Text (body copy on paper)
          soft: '#7a89a0',
          faint: '#a9b6c8',
        },
        // the red margin line — THE single accent (FAB, highlights, destructive)
        margin: {
          DEFAULT: '#c9463d', // Margin Red
          soft: '#e0928c',
        },
        // ruled lines on paper templates
        rule: {
          blue: '#b9cfe8',
          red: '#e0928c',
        },
      },
      fontFamily: {
        // real families chosen in the Claude Design pass; keep the token names
        display: ['ui-serif', 'Georgia', 'serif'],
        body: ['ui-sans-serif', 'system-ui', 'sans-serif'],
      },
    },
  },
}
