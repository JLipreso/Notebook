# Notebook — Finalized Concept

_Finalized 2026-09-13 from the Lead Developer's brief ([about.md](about.md) — the raw brief, never edited) + the concept-validation task ([2026-09-13-003](../2026-09-13-003-Concept-Validation/README.md)). Every statement here traces to a locked decision (D-007…D-023 in [.claude/memory/decisions.md](../../.claude/memory/decisions.md)). "Notebook" is the working MVP name; final brand TBD._

## Identity

**Notebook** is a Philippine-market, mobile-first app that replaces the stack of 8–12 paper notebooks a student buys every school year with digital notebooks they own forever — searchable, shareable, archived by school year — plus a lightweight classroom layer that lets a teacher run a class (lessons, quizzes, scores) on top of the notebooks students already live in. It serves preschool through college; students and teachers subscribe individually (₱ tiers, GCash), and the student's own notebooks work fully offline on mobile and tablet.

## The product spine (locked)

- **Notebook types are faithful paper templates** (D-010): Composition = single-ruled + red margin (HS/college); Writing = blue/red penmanship guide lines with `Date:` header and Teacher's/Parent's Signature footers (preschool); plus Diary, Drawing Book, Scrapbook, Log Book, Timesheet — the type list is extensible.
- **Pages are typed block documents** — Tiptap 2 / ProseMirror JSON rendered on the paper templates; the schema reserves a future drawing/ink block (Drawing Book and preschool handwriting input unlock then). Writing type is visual-style-only in MVP (D-012, D-018).
- **Offline-first where it counts** (D-016): notebooks/pages/attachments are read-write offline (SQLite on device via Capacitor, D-017); enrolled courses/lessons/scores are a read-only offline cache; quiz taking, chat, payments, sharing stay online-only. UUIDv7 client-generated PKs everywhere (D-013).
- **Scores, not grades** (D-008): the MVP records raw quiz/activity scores; final-grade computation differs per level and school, and ships later as free per-level "Grade Calculator" add-on tools.
- **Lessons print** (D-009): every lesson downloads as PDF and prints directly — for students without devices.
- **Classroom communication = course-room chat** (D-021, on paid Pusher per D-007); DMs/groups deferred behind a moderation plan.
- **No geolocation** in MVP (D-020); the PSGC Region→Province→City/Municipality→Barangay tables provide address data.

## Users & model (locked)

- **Roles:** student / teacher / admin on a single `users` table (D-014); single-tenant B2C — individuals subscribe, no per-school tenancy (Q-003).
- **Auth:** Firebase identity (email/password, Google, phone OTP) exchanged once for a Sanctum bearer token (D-015).
- **Growth loop is teacher-led** (D-022): a paying teacher's students get course access regardless of the student's own tier; students pay for notebook-side premium (more notebooks, storage, fonts, AI add-on).
- **Free is a permanent floor** (D-023): after the 14-day trial, Free degrades to a limited tier (e.g. 2 active notebooks, read-only archives) — user data is never deleted or fully locked away.
- **Tiers & prices** (Admin-editable, per the brief): Student Basic ₱69 / Premium ₱129 · Teacher Basic ₱89 / Premium ₱169 per month; ₱ + USD; AI add-ons priced separately. Payment MVP = GCash QR + reference number with staff verification (unverified → in_progress → follow_up → received / fail_payment); PayMongo/Maya later.

## Delivery plan (D-011 — notebook-first)

1. **M1 — Notebook core:** auth + profile/PSGC address, notebook library (types, covers, per-school-year, archive), the block editor on paper templates, offline sync, read-only sharing links, images/PDF viewing, notification plumbing.
2. **M2 — Classroom layer:** minimal teacher portal (courses, Tiptap lessons, quiz builder + question bank, email invitations with accept/decline+reason, course timeline/reuse), student course join + lesson viewing + quiz taking, lesson PDF/print, score collection, course-room chat.
3. **M3 — Money + admin:** entitlements module (dynamic tier→feature mapping), GCash verification workflow, admin panel (dashboard counts, student/teacher/payment management).
4. **Deferred:** exams, assignments, attendance, Grade Calculators, AI add-ons, Google Drive/Calendar, doc/3D viewing-editing, full messenger, PayMongo/Maya, Open API (design endpoints with it in mind).

## Where the detail lives

- Raw brief: [about.md](about.md) · Validation + stack table: [2026-09-13-003 README](../2026-09-13-003-Concept-Validation/README.md) · Per-question rationale: [questions/](../2026-09-13-003-Concept-Validation/questions/) · MIIT lessons: [findings/miit-scan.md](../2026-09-13-003-Concept-Validation/findings/miit-scan.md)
