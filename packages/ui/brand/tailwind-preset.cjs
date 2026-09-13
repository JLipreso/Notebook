// The ONE shared Tailwind preset (CLAUDE.md §4: brand tokens live here, not per app).
// Placeholder palette named after the product's paper metaphor — swap values when
// the brand identity lands; the token NAMES are what views are allowed to use.
module.exports = {
  theme: {
    extend: {
      colors: {
        // notebook paper
        paper: {
          DEFAULT: '#fdfcf7',
          shade: '#f5f2e9',
        },
        // writing ink — primary actions, headings
        ink: {
          DEFAULT: '#1e3a5f',
          soft: '#3d5a80',
          faint: '#98a8bd',
        },
        // the red margin line — accents, destructive, badges
        margin: {
          DEFAULT: '#d9534f',
          soft: '#f2b8b5',
        },
        // ruled lines
        rule: {
          blue: '#a8c6e8',
          red: '#e8a8a8',
        },
      },
      fontFamily: {
        // real families chosen with the brand; keep the token names
        display: ['ui-serif', 'Georgia', 'serif'],
        body: ['ui-sans-serif', 'system-ui', 'sans-serif'],
      },
    },
  },
}
