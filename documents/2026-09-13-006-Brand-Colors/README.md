# 2026-09-13-006 — Brand Colors

Client-facing color exploration and the locked selection (**D-032**). Requested by the boss; three directions were pitched as an artifact showing the same three mobile screens (Welcome, Library, Page editor) with identical content, so color was the only variable.

**Pitch artifact (kept live for reference):** https://claude.ai/code/artifact/81763d78-b4a8-4e57-9bc7-444a827fcb96

## The three options pitched

| Option | Name | Feel | Core colors |
|---|---|---|---|
| A ✅ | **Komposisyon** | Classic & trustworthy — the composition notebook itself | Ink Navy · Margin Red · Cream Paper |
| B | Silid-Aralan | Warm & nostalgic — chalkboard green + manila paper | Chalkboard Green · Pencil Gold · Manila |
| C | Kislap | Modern & energetic — contemporary study app | Deep Teal · Coral · Warm White |

## Selected: Option A — "Komposisyon" (D-032, boss + client, 2026-09-13)

| Token role | Name | Hex | Used for |
|---|---|---|---|
| `ink` (DEFAULT) | Ink Navy | `#1E3A5F` | primary brand color — headers, primary buttons, covers, links |
| `margin` (DEFAULT) | Margin Red | `#C9463D` | THE single accent — FAB, highlights, destructive, badges |
| `paper` (DEFAULT) | Cream Paper | `#FBF8F0` | app ground / page background |
| `paper.shade` | Paper Shade | `#F2ECDD` | cards-on-paper, dividers, subtle fills |
| `rule.blue` | Rule Blue | `#B9CFE8` | notebook ruling lines |
| `ink.text` | Ink Text | `#22344C` | body text on paper (brand navy stays for chrome/headings) |
| `ink.soft` | Soft Ink | `#7A89A0` | secondary text |
| `margin.soft` | Soft Red | `#E0928C` | `rule.red` / soft accent fills |

## Where it lives (single source of truth)

- **Code:** [packages/ui/brand/tailwind-preset.cjs](../../packages/ui/brand/tailwind-preset.cjs) — the ONE shared Tailwind preset (CLAUDE.md §4). Views consume token NAMES (`bg-paper`, `text-ink`, `bg-margin`, `rule-blue`); changing brand = changing this file only.
- **Decision:** D-032 in [.claude/memory/decisions.md](../../.claude/memory/decisions.md) — final, do not re-litigate per option debates.

## Design passes

Screen design happens in **Claude Design** on this palette; briefs and exports live in this folder. Options B and C are archived above for the record — they are NOT fallbacks; a palette change now requires a new decision.

**The M1 design surface is COMPLETE (browser + mobile, per D-029). Canonical references — UI implementation matches against these two files:**
- **Mobile (portrait):** [Notebook-Mobile-22-screens.html](Notebook-Mobile-22-screens.html)
- **Browser (desktop):** [Notebook-Browser-12-screens.html](Notebook-Browser-12-screens.html)

| Pass | Brief | Deliverable | Status |
|---|---|---|---|
| 1 — M1 mobile core (12 screens) | [claude-design-brief-1.md](claude-design-brief-1.md) | [Notebook-Mobile-12-screens.html](Notebook-Mobile-12-screens.html) (superseded — history only) | **done** — [note](design-pass-1-complete.md) |
| 2 — M1 mobile flows & states (+10) | [claude-design-brief-2.md](claude-design-brief-2.md) | [Notebook-Mobile-22-screens.html](Notebook-Mobile-22-screens.html) | **done** — [note](design-pass-2-complete.md) |
| 3 — M1 browser, desktop web (12 screens) | [claude-design-brief-3.md](claude-design-brief-3.md) | [Notebook-Browser-12-screens.html](Notebook-Browser-12-screens.html) | **done** — [note](design-pass-3-complete.md) |

Still pending on this thread: the **typeface fold-back** into `packages/ui/brand/tailwind-preset.cjs` (`fontFamily` tokens) once the final families are confirmed from the canvases. Tablet / teacher / admin passes are parked by decision (D-029, M2, M3).
