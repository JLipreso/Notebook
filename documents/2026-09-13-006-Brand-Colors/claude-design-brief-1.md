# Claude Design brief — Notebook mobile app (Komposisyon brand)

_Task 2026-09-13-006. This file is the prompt to paste/upload into the Claude Design app. It is deliberately self-contained — Claude Design has no access to this repo. Source of truth for the palette: D-032; for the screens: the M1 plan (2026-09-13-005)._

---

## THE PROMPT (copy everything below this line)

Design the mobile app screens for **Notebook** — a Philippine-market app that replaces the stack of 8–12 paper notebooks a student buys every school year with digital notebooks they own forever. Students from preschool through college write typed notes on faithful digital reproductions of real Philippine paper notebooks; their notebooks work fully offline and archive per school year. Design the student app only.

### Brand — locked, do not deviate

The brand is called **"Komposisyon"** — the classic Philippine composition notebook itself: classic, trustworthy, school-official. It must feel like a beloved school supply, not a tech product. Parents should trust it on sight; it must not look childish for college students.

**Palette (exact hex values, the complete set — introduce no new hues):**

| Role | Name | Hex |
|---|---|---|
| Primary / brand — headers, primary buttons, notebook covers, active states | Ink Navy | `#1E3A5F` |
| THE single accent — FAB, highlights, unread badges, destructive actions | Margin Red | `#C9463D` |
| App background / ground | Cream Paper | `#FBF8F0` |
| Cards-on-ground, dividers, subtle fills | Paper Shade | `#F2ECDD` |
| Body text | Ink Text | `#22344C` |
| Secondary text | Soft Ink | `#7A89A0` |
| Muted/disabled text | Faint Ink | `#A9B6C8` |
| Notebook ruling lines | Rule Blue | `#B9CFE8` |
| Soft accent fills / penmanship red guide line | Soft Red | `#E0928C` |
| Text/icons on navy or red | Warm White | `#F6EFDF` |

Accent discipline: Margin Red appears in ONE place per screen (the FAB, or the badge, or the highlight — never several at once). Everything else is navy, ink, and paper. White (#FFFFFF) is allowed only as the paper-page surface inside the editor; the app ground is always Cream Paper.

**Typography:** a warm serif for display (screen titles, notebook titles, the wordmark) paired with a clean humanist sans for UI and body. Suggested pairing: Fraunces (display) + Figtree (UI); an equivalent pairing is fine if it keeps the "trusted schoolbook" character. Generous line-height; UI labels in the sans, never the serif.

### Format

- Phone, portrait, 390 × 844 artboards (the app is permanently portrait on phones).
- One artboard per screen below, plus one tokens/foundations artboard (palette swatches, type scale, button set, card set) as the first board.
- Use realistic Filipino school content everywhere — never lorem ipsum. Student: **Ana Reyes, Grade 7**. School year: **SY 2026–2027**. Subjects: Math 7, Science 7, Araling Panlipunan, English. Greeting: "Magandang umaga, Ana!". Currency: ₱.

### The paper rule (the soul of the product — get this right)

Notebook pages inside the editor must faithfully reproduce real paper:
- **Composition type:** white page, horizontal Rule Blue lines, one vertical Margin Red line on the left. Typed text sits ON the ruled lines (text baseline aligned to the ruling).
- **Writing type (preschool penmanship):** alternating blue/blue/red guide lines, a "Date: ____" header line, and "Teacher's Signature" / "Parent's Signature" lines in the footer.
- The paper is content, not decoration — chrome (toolbars, nav) stays on Cream Paper around it, and the page reads instantly as "my school notebook".

### Screens (12 artboards)

1. **Foundations** — palette, type scale, buttons (primary navy / outline / text), notebook card, input field, chip, FAB, bottom nav.
2. **Welcome** — wordmark/logo (a simple notebook glyph is fine), tagline "All your notebooks, every school year, forever.", Create account (primary), Sign in (outline), Continue with Google, "Free for 14 days · no card needed".
3. **Sign up** — first/middle/last name, birthday, email, mobile number, role choice (Student / Teacher) as two selectable cards, password via Firebase — keep it one scrollable form with clear grouping.
4. **Profile & address** — the Philippine address dropdown chain: Region → Province → City/Municipality → Barangay (four cascading selects), plus avatar, name fields. Show the state where Region is chosen and Province is open.
5. **Library (home)** — greeting + SY 2026–2027 chip, search, "Active notebooks" grid of notebook cards (cover = navy or red spine with the subject), one archived-shelf entry point, red FAB (+), bottom nav: Library / Search / Shared / Profile.
6. **New notebook** — notebook type picker where each type (Composition, Writing, Drawing, Diary, Scrapbook, Log Book, Timesheet) shows a mini paper preview of its ruling; title field; school-year field prefilled "2026–2027"; cover color/photo choice.
7. **Page editor — Composition** — top bar (back, "Math 7 · Composition", sync status "✓ synced"), the ruled paper page with real study notes about fractions, floating formatting toolbar (bold, italic, underline, lists, table, image), page indicator "Page 12 of 24".
8. **Page editor — Writing (penmanship)** — same chrome, penmanship guide lines, "Date:" header, signature footer lines, large typed practice letters.
9. **Offline state** — the editor while offline: a quiet "Working offline — changes will sync" pill, everything still fully usable; nothing alarming or red (offline is normal, not an error).
10. **Share** — share sheet for a notebook: "Anyone with the link can view", scope choice (whole notebook / this page), optional expiry, copy-link button, list of active links with revoke.
11. **Notifications** — list with unread state (Margin Red dot), items like "Your share link was opened", "Welcome to Notebook!", mark-all-read.
12. **Archive shelf** — past school years as labeled shelves ("SY 2025–2026"), read-only badge, reassuring note that archives are kept forever.

### Rules

- Touch targets ≥ 44px; body text ≥ 16px equivalent; check contrast on Cream Paper (Soft Ink is for secondary text only, never long copy).
- Bottom navigation, not hamburger. States matter: show pressed/active/disabled in the foundations board.
- No geolocation UI anywhere. No prices on these screens (billing is a later milestone). No company/contractor branding anywhere — the product is just "Notebook".
- Light theme only for this pass.

---

_End of prompt. Design decisions made inside Claude Design (final typefaces, spacing scale, component shapes) should be exported back into this task folder when the canvas is settled, and the type choices folded into `packages/ui/brand/tailwind-preset.cjs` (`fontFamily` tokens) with a completion note._
