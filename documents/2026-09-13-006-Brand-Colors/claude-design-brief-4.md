# Claude Design brief 4 — Notebook TEACHER mobile app (Komposisyon brand)

_Task 2026-09-13-006 design home · pass 4 (D-033, feeds M2 Phase-201+). Paste/upload the prompt below into the Claude Design app, together with the student mobile canvas (`Notebook-Mobile-22-screens.html`) if the app lets you continue on it — the teacher app is the same product seen from the other side of the classroom, not a second design. Self-contained otherwise._

---

## THE PROMPT (copy everything below this line)

Design the **teacher mobile app** for **Notebook** — the Philippine student app where typed notes live on faithful digital reproductions of real paper notebooks. The student mobile app (22 screens) is already designed; this pass designs the **teacher's side**: running a class on top of the notebooks students already live in — courses, lessons, quizzes, scores, invitations, and a class chat. The teacher persona is a working Filipino teacher managing 2–4 courses from their phone between classes; heavy authoring happens on desktop (a later pass), so mobile optimizes for **monitoring, quick edits, grading on the go, and communication**.

### Brand — locked, identical to all prior passes

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
| Soft accent | Soft Red | `#E0928C` |
| On navy/red | Warm White | `#F6EFDF` |

Typography carries over (Fraunces display + Figtree UI). **Real Filipino school content everywhere** — the teacher is **Gng. Liza Manalo**, teaching **Math 7 (Diamond)** and **Math 7 (Emerald)**, SY 2026–2027; students include Ana Reyes, Miguel Santos, Bea Villanueva. Never lorem ipsum. Light theme only; calm states (offline, pending, empty) read as factual, never alarming.

### Format & mobile conventions (carried over from the student passes)

- Portrait phone artboards, same size as the student mobile canvas; the design is **permanently portrait**.
- Bottom nav for the teacher app: **Home · Courses · Notifications · Profile**.
- Teachers author lessons as typed block documents on the same paper surfaces students see — when lesson content appears, the paper rule is sacred (ruled lines, red margin, text sitting on the lines).

### Screens (12 artboards)

1. **Teacher home** — greeting ("Magandang umaga, Gng. Manalo"), today at a glance: courses with activity counts (3 new quiz submissions, 1 invitation answered), quick actions. The red badge on submissions is the screen's accent.
2. **Course list** — cards per course: title, section, SY, student count, timeline chip (Active · until Mar 2027); one **ended course shows the "Expired" label** (quiet, factual). "+ New course" action.
3. **Create course** — title, description, school year, start/end dates, cover choice — AND the **"Clone a previous course"** entry point (pick an ended course → lessons and quizzes copy over, students don't).
4. **Course home** — the hub: course header with timeline, then segmented sections (Lessons · Students · Quizzes · Scores · Chat) with the Lessons list showing availability windows and draft/published state.
5. **Lesson editor (mobile quick-edit)** — title, the typed block content on the ruled paper surface, availability window (from/until dates), video link field (YouTube/web link), Save draft / Publish. Compact — mobile edits, desktop authors.
6. **Course calendar** — month view where lesson availability windows and quiz windows paint the days; tapping a day lists what opens/closes. Uses Rule Blue fills, red only on "today".
7. **Quiz settings** — for a quiz gated by a lesson ("Activates when: Aralin 3 completed"): time limit (60 min default), attempts, passing score, shuffle questions/options toggles, and the **"Release answers" state — OFF until the teacher triggers it** (make this a deliberate, labeled action, not a casual toggle).
8. **Question editor** — one multiple-choice question being edited (rich text, options with the correct one marked, points) + the **"Reuse from question bank"** sheet (search past questions; note "a copy is added — editing it won't change the original").
9. **Invitations** — invite by student email (input + send), then the status list: pending / accepted / **declined with the student's reason shown** / revoked / expired, with resend & revoke actions. Include the empty state ("No invitations yet — invite your first student").
10. **Students roster** — enrolled list with per-student glance (lessons completed 5/8, last quiz score), remove action behind a confirm.
11. **Grading & scores** — a quiz's submissions list (auto-scored MC/TF shown, one short-answer awaiting manual grade), and the manual grading view: the student's answer, points stepper, saved state. Score figures use tabular numerals.
12. **Course chat (teacher view)** — the one room per course: messages with sender names, an image attachment, the teacher's composer. Include the notifications screen as an inset or split on this board: quiz_submitted and invite_answered items with unread dots.

### Rules (carried over)

- Margin Red once per screen. No prices anywhere. No company/contractor branding — the product is just "Notebook".
- Body text ≥ 16px; check contrast on Cream Paper.
- Quiz taking, chat, and grading are **online-only** — no offline affordances needed on those screens; a quiet "online" assumption is fine.

---

_End of prompt. Export the finished canvas into this folder (suggested name: `Notebook-Teacher-Mobile-12-screens.html`), write `design-pass-4-complete.md`, and update the pass table in [README.md](README.md). Pass 5 (teacher browser) follows._
