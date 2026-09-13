# Design pass 2 complete — M1 mobile flows & states

_2026-09-13. Produced in the Claude Design app from [claude-design-brief-2.md](claude-design-brief-2.md); Komposisyon palette (D-032)._

- **Deliverable:** [Notebook-Mobile-22-screens.html](Notebook-Mobile-22-screens.html) — the cumulative canvas: pass 1's 12 boards PLUS the 10 flow/state boards (sign-in, forgot-password, page overview, notebook settings, attachment flows incl. pending/failed upload, search results, Shared tab, sync & quota states, empty/first-run + trial states).
- **This 22-screen file is now the canonical M1 mobile design reference** — it supersedes [Notebook-Mobile-12-screens.html](Notebook-Mobile-12-screens.html), which stays only as pass-1 history. The junior developer's UI phases (Phase-004…010) design-match against the 22-screen canvas.
- With this pass, **the M1 mobile app has no undesigned moment.**

## Remaining design work

1. **Design pass 3 — M1 browser (desktop web)**: brief to be written next; completes the M1 design surface (D-029: M1 = browser + mobile). Includes the public share viewer (browser-only per Phase-010).
2. **Typeface fold-back (pending since pass 1):** confirm the final display/UI families from the canvas and set them in `packages/ui/brand/tailwind-preset.cjs` `fontFamily` tokens (+ Google Fonts wiring in the apps).
3. Parked by decision: tablet landscape (post-M1, D-029), teacher portal (M2), admin (M3).
