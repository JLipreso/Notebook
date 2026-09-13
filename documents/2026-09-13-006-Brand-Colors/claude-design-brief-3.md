# Claude Design brief 3 — Notebook browser app, desktop web (Komposisyon brand)

_Task 2026-09-13-006, design pass 3. Paste/upload the prompt below into the Claude Design app, together with the pass-2 canvas (`Notebook-Mobile-22-screens.html`) if the app lets you continue on it — the browser app must feel like the same product, translated to a big screen, not a second design. Self-contained otherwise._

---

## THE PROMPT (copy everything below this line)

Design the **desktop web app** for **Notebook** — the Philippine student app where typed notes live on faithful digital reproductions of real paper notebooks, archived per school year. The mobile app (22 screens) is already designed; this pass translates it to the **laptop/desktop browser**. Same product, same soul, bigger canvas: this is where students type long notes comfortably and where people who receive share links land.

### Brand — locked, identical to the mobile passes

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

Typography carries over from mobile (warm serif display + humanist sans UI). Real Filipino school content everywhere (Ana Reyes, Grade 7, SY 2026–2027, Math 7 / Science 7 / Araling Panlipunan) — never lorem ipsum. Light theme only. The "calm states" principle holds: offline, syncing, and limit moments read as factual, never alarming.

### Format & desktop conventions

- Artboards at **1440 × 900**; content should also survive 1280 wide (note anything that reflows).
- Navigation: a **left sidebar** (Library, Search, Shared, Archive, Profile) with the wordmark on top — replacing mobile's bottom nav. A slim top bar carries the current context + sync status + notifications bell.
- Desktop affordances matter: visible hover states, right-click-free design (everything reachable by click), dialogs instead of bottom sheets, and a hint of keyboard support where natural (⌘/Ctrl+K for search, ⌘/Ctrl+B in the editor toolbar tooltips).
- The paper rule is sacred at every size: page previews and the editor keep faithful rulings and the red margin line; typed text sits on the lines.

### Screens (11 artboards)

1. **Desktop foundations** — the sidebar, top bar, dialog frame, buttons with hover/focus states, notebook card at desktop size, input fields, the search command bar (⌘K) — everything reused from mobile restated for desktop.
2. **Sign in / Sign up** — one artboard, both moments: centered card on Cream Paper, wordmark above; sign-up shows the full profile form comfortably in two columns (names/birthday/mobile left, email/password/role right).
3. **Library** — sidebar + a generous notebook grid; filters as a quiet row (school year, type, active/archived toggle); the red "+ New notebook" button as the screen's accent; hover state on one card showing quick actions (open, share, settings).
4. **New notebook dialog** — modal over the dimmed library: type picker with mini paper previews in a grid, title, school year, cover choice. Show it as the open-dialog state of screen 3.
5. **Editor — Composition** — the desktop editor: page-list sidebar (thumbnails with page numbers, "+ Add page"), the ruled paper page centered at a comfortable reading width, formatting toolbar docked above the page, sync status + "Page 12 of 24" in the top bar. Paper fidelity at full desktop scale is this pass's hero moment.
6. **Editor — Writing (penmanship)** — same chrome, penmanship guide lines with the "Date:" header and signature footer, large practice text.
7. **Attachments in the editor** — an image placed on the ruled page (max-width, caption), the PDF chip + the PDF opened in a side-by-side split (paper left, PDF right) — the layout luxury desktop affords.
8. **Search** — the ⌘K command-bar overlay mid-typing ("frac…") AND the full results page grouped by notebook with highlighted snippets (the highlight is this screen's single red accent).
9. **Shared management + share dialog** — the Shared sidebar destination (links list: name, created, expiry, revoke) with the share dialog open over it (scope, expiry, copy link).
10. **Public share viewer** — THE signed-out page a link recipient lands on: no sidebar, no app chrome — just a slim header (wordmark + "Shared notebook · read-only"), the paper pages rendered beautifully centered, and a quiet "Make your own notebooks — free for 14 days" footer as the only conversion nudge. Include the expired/revoked variant ("This link is no longer available") — friendly, not an error page.
11. **Profile & states** — profile page with the Region → Province → City/Municipality → Barangay dropdowns laid out in a row, storage meter ("128 MB of 500 MB"), the quiet trial chip ("Free trial · 11 days left"), and the notifications dropdown open from the bell (unread red dots).

### Rules (carried over)

- Margin Red once per screen. No geolocation UI. No prices. No company/contractor branding — the product is just "Notebook".
- Body text ≥ 16px; line length in the editor ~65–75 characters; check contrast on Cream Paper.
- Empty states for Library and Shared can be small inset variants on their boards — don't skip them.

---

_End of prompt. Export the finished canvas back into this folder (suggested name: `Notebook-Browser-11-screens.html`) and update the pass table in [README.md](README.md). With this pass the full M1 design surface (browser + mobile, per D-029) is covered; tablet/teacher/admin stay parked by decision._
