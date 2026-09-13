# Design pass 3 complete — M1 browser (desktop web), 12 screens

_2026-09-13. Produced in the Claude Design app from [claude-design-brief-3.md](claude-design-brief-3.md); Komposisyon palette (D-032)._

- **Deliverable:** [Notebook-Browser-12-screens.html](Notebook-Browser-12-screens.html) — the desktop canvas (the 11 briefed boards — desktop foundations with sidebar nav, sign-in/sign-up, library + new-notebook dialog, Composition and Writing editors with the page-thumbnail pane, attachments incl. the paper/PDF split, ⌘K search + results, shared management + share dialog, the signed-out public share viewer with expired variant, profile & states — plus one additional board from the design session).
- **This file is the canonical M1 BROWSER design reference**; [Notebook-Mobile-22-screens.html](Notebook-Mobile-22-screens.html) stays the canonical MOBILE reference. Together they cover the entire M1 design surface (D-029: M1 = browser + mobile).

## 🎉 M1 design surface COMPLETE

Every screen the junior developer builds in the [implementation plan](../2026-09-13-005-Implementation-Plan/README.md) now has a design to match on both form factors:
- **Mobile phases** (portrait) → match [Notebook-Mobile-22-screens.html](Notebook-Mobile-22-screens.html)
- **Browser phases** (desktop) → match [Notebook-Browser-12-screens.html](Notebook-Browser-12-screens.html)

## Remaining on the design thread

1. **Typeface fold-back (still pending since pass 1):** confirm the final display/UI families from the canvases → set `packages/ui/brand/tailwind-preset.cjs` `fontFamily` tokens + Google Fonts wiring in the apps.
2. Parked by decision: tablet landscape (post-M1, D-029), teacher portal (M2), admin (M3) — each gets its own brief in this format when its milestone starts.
