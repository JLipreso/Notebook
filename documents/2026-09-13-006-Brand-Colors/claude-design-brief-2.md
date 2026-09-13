# Claude Design brief 2 — Notebook mobile flows & states (Komposisyon brand)

_Task 2026-09-13-006, design pass 2. Paste/upload the prompt below into the Claude Design app **together with the pass-1 canvas** (`Notebook-Mobile-12-screens.html`) if the app lets you continue on it — these screens must reuse pass 1's foundations (buttons, cards, nav, type) exactly. Self-contained otherwise._

---

## THE PROMPT (copy everything below this line)

Continue the mobile screen design for **Notebook** — the Philippine student app where typed notes live on faithful digital reproductions of real paper notebooks, fully offline-capable, archived per school year. Pass 1 designed the core 12 screens; this pass designs the **remaining flows and states** so the app has no undesigned moment. Reuse the existing foundations (buttons, cards, bottom nav, type pairing) exactly — do not restyle them.

### Brand — locked, identical to pass 1

**"Komposisyon"** — the classic Philippine composition notebook: classic, trustworthy, school-official.

| Role | Name | Hex |
|---|---|---|
| Primary / brand | Ink Navy | `#1E3A5F` |
| THE single accent (one place per screen) | Margin Red | `#C9463D` |
| App ground | Cream Paper | `#FBF8F0` |
| Cards / subtle fills | Paper Shade | `#F2ECDD` |
| Body text | Ink Text | `#22344C` |
| Secondary text | Soft Ink | `#7A89A0` |
| Muted/disabled | Faint Ink | `#A9B6C8` |
| Ruling lines | Rule Blue | `#B9CFE8` |
| Soft accent / penmanship red line | Soft Red | `#E0928C` |
| On navy/red | Warm White | `#F6EFDF` |

Typography, format, and content conventions carry over: warm serif display + humanist sans UI; phone portrait 390 × 844; real Filipino school content (Ana Reyes, Grade 7, SY 2026–2027, Math 7 / Science 7 / Araling Panlipunan); never lorem ipsum. Light theme only. Touch targets ≥ 44px.

### Design principle for this pass: calm states

Most of these screens are *states* — offline, syncing, empty, at-a-limit. The product's promise is that nothing is ever lost or locked away, so states must read as **calm and factual, never alarming**: Margin Red is reserved for genuine destructive confirmation and unread badges; offline/pending/limit states use navy and ink, with plain-language copy that says what's happening and what (if anything) to do.

### Screens (10 artboards)

1. **Sign in** — email + password, "Continue with Google", "Forgot password?" link, link back to Create account. Sibling of pass 1's Welcome — same warmth, quicker.
2. **Forgot password** — email entry → confirmation state ("We emailed a reset link to a•••@gmail.com"). Both moments on one artboard is fine.
3. **Page overview** — tapping "Page 12 of 24" in the editor opens a thumbnail grid of the notebook's pages (tiny faithful paper previews with page numbers), reorder handle, "+ Add page" tile at the end, tap to jump.
4. **Notebook settings** — sheet or screen from the editor/library card: rename, change cover (color set + photo option), font choice (curated list, preview line in each font), archive action, and Delete with a confirmation dialog — deletion copy must reassure: "Moves to trash — your writing is never gone forever." (Red lives on the confirm button only.)
5. **Attachment flows** — one artboard, three moments: (a) the editor's attach sheet (Photo / Camera / PDF / Document), (b) an image placed inside a ruled Composition page (sane max-width, caption line), (c) a PDF chip under the page that opens a full-screen viewer with page controls.
6. **Pending upload state** — the same page while offline: the image shows a small "will upload when online" overlay chip; the notebook header shows the quiet offline pill from pass 1. Include the failed-upload variant (a retry chip — Soft Red fill, not alarming).
7. **Search results** — the bottom-nav Search tab: query "fractions", results grouped by notebook, each hit showing the page thumbnail + a text snippet with the match highlighted (Margin Red highlight — this screen's single accent), recent-searches empty variant.
8. **Shared tab** — the bottom-nav Shared destination: "Links I've shared" list (notebook/page name, created date, expiry if set, view count optional, revoke button per row), with an empty state ("Nothing shared yet — share a notebook from its cover menu").
9. **Sync & quota states** — one artboard, three cards/moments: (a) sync-in-progress (subtle spinner in the header, "Syncing 3 changes…"), (b) a conflict-resolved notice ("This page was updated on your other device — showing the latest version", with a calm dismiss), (c) the storage meter in Profile ("128 MB of 500 MB used") with the near-limit variant and a quiet "More space comes with Premium — soon" line (no prices, no purchase flow).
10. **Empty & first-run states** — one artboard, three variants: empty Library (friendly nudge + red FAB as the accent), empty Archive ("Your finished school years will live here"), trial status line in Profile ("Free trial · 11 days left" as a quiet chip, plus the after-trial variant "Free plan" — never a countdown alarm).

### Rules (carried over)

- Bottom navigation, not hamburger. Show pressed/disabled states where a new control appears.
- No geolocation UI. No prices anywhere. No company/contractor branding — the product is just "Notebook".
- The paper is sacred: any page preview, however tiny, keeps its ruling and margin line.

---

_End of prompt. Export the finished canvas back into this folder (suggested name: `Notebook-Mobile-flows-states.html`) and update the pass table in [README.md](README.md); pass 3 (M1 browser layouts) gets its brief after this one lands._
