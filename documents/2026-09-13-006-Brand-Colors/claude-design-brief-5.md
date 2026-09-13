# Claude Design brief 5 — Notebook TEACHER browser app, desktop web (Komposisyon brand)

_Task 2026-09-13-006 design home · pass 5 (D-033, feeds M2 Phase-201+). Run AFTER pass 4; paste/upload the prompt below together with the pass-4 canvas (`Notebook-Teacher-Mobile-12-screens.html`) and, if the app allows a second reference, the student browser canvas (`Notebook-Browser-12-screens.html`) — same product, same desktop chrome, teacher's side. Self-contained otherwise._

---

## THE PROMPT (copy everything below this line)

Design the **teacher desktop web app** for **Notebook** — the Philippine student app where typed notes live on faithful digital reproductions of real paper notebooks. The teacher mobile app is designed (monitoring + quick edits); **desktop is where teachers do the heavy work**: writing lessons, building quizzes, grading a whole class, managing enrollment. The student desktop app is also designed — reuse its chrome (left sidebar + slim top bar) so the two feel like one product.

### Brand — locked, identical to all prior passes

**"Komposisyon"** — classic, trustworthy, school-official.

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
| Soft accent | Soft Red | `#E0928C` |
| On navy/red | Warm White | `#F6EFDF` |

Typography: Fraunces display + Figtree UI. Real Filipino school content (Gng. Liza Manalo, Math 7 Diamond/Emerald, SY 2026–2027; students Ana Reyes, Miguel Santos, Bea Villanueva). Light theme only; calm states.

### Format & desktop conventions (carried over from the student browser pass)

- Artboards at **1440 × 900**, surviving 1280 wide.
- **Left sidebar** (Dashboard, Courses, Calendar, Notifications, Profile) with the wordmark on top; slim top bar carrying current context + notifications bell. Hover/focus states visible; dialogs, not bottom sheets.
- Lesson content lives on the faithful paper surface — ruled lines, red margin, text on the lines — at comfortable desktop reading width.

### Screens (12 artboards)

1. **Teacher dashboard** — courses at a glance (cards with activity counts), pending work strip (3 to grade, 2 invitations pending), recent activity feed. Red badge on "to grade" is the accent.
2. **Course list + create dialog** — the grid with timeline chips (one "Expired"), and the create-course dialog open over it: title/section/SY/dates/cover + the **"Clone a previous course"** option with its explainer (content copies, enrollment doesn't).
3. **Course home** — header with timeline, tabbed sections (Lessons · Quizzes · Students · Scores · Chat), Lessons tab active: rows with availability windows, draft/published state, drag-handle sort.
4. **Lesson editor — the hero screen** — full authoring: title, the typed block editor on the ruled paper page centered at reading width, formatting toolbar docked above, right rail with availability window, video link (YouTube/web), status (Draft/Published), and a **"Download PDF / Print" affordance** (every lesson prints — for students without devices).
5. **Course calendar** — month grid where lesson and quiz windows paint spans across days; a side panel lists the selected day. Rule Blue fills; red only on today.
6. **Quiz builder — settings + questions** — two-pane: left = quiz settings (gating lesson, 60-min default time limit, attempts, passing score, shuffle toggles, the deliberate **"Release answers" action, OFF by default**); right = the question list with points and types.
7. **Question editor + bank** — a multiple-choice question open (rich text, options, correct mark, points, explanation) with the **question bank panel** alongside: searchable past questions, "Add a copy" (note: copies are independent of the original).
8. **Invitations manager** — invite-by-email input row, then the full-width status table: pending / accepted / **declined with reason shown** / revoked / expired, resend & revoke actions, and the invite-sent confirmation toast.
9. **Students roster** — table with per-student progress (lessons 5/8, avg score, last active), row hover actions, remove behind a confirm dialog.
10. **Grading table — the second hero** — a quiz's submissions as a table (student, submitted time, auto score, status), with one short-answer submission open in a **side panel for manual grading**: the student's answer, points input, prev/next student navigation for grading a class in one sitting. Tabular numerals everywhere.
11. **Scores overview** — per-course score matrix (students × quizzes) with quiet heat (Paper Shade steps, never traffic lights), export/print affordance, and the answers-released indicator per quiz.
12. **Course chat (teacher view)** — desktop layout: room with messages and attachments left, member list right (teacher pinned on top), composer with attach.

### Rules (carried over)

- Margin Red once per screen. No prices. No company/contractor branding — the product is just "Notebook".
- Body ≥ 16px; ~65–75 character line length on paper; contrast-check on Cream Paper.
- Include empty states for Invitations and the grading queue as small insets — don't skip them.

---

_End of prompt. Export the finished canvas into this folder (suggested name: `Notebook-Teacher-Browser-12-screens.html`), write `design-pass-5-complete.md`, and update the pass table in [README.md](README.md). With passes 4+5 done, the M2 teacher design surface is complete; the admin pass stays parked for M3 pre-work._
