# 2026-09-13-007 — M2 Classroom Plan (milestone level)

The **Milestone 2 (classroom layer)** plan, written while M1 is being implemented — deliberately at **milestone/phase level**, not file level. Boss-approved workflow: the junior developer implements M1 first; M2 planning runs in parallel on the Lead Developer's branch; M2 phase files get the Phase-001-style file-level treatment only when M1 stabilizes (trigger below). Writing step-by-step detail against code that doesn't exist yet would rot with every M1 deviation.

**Scope (concept-final.md delivery plan, D-011):** minimal teacher portal (courses, Tiptap lessons, quiz builder + question bank, email invitations with accept/decline + reason, course timeline/reuse), student course join + lesson viewing + quiz taking, lesson PDF/print (D-009), score collection (D-008 — scores, not grades), course-room chat (D-021). M3 (payments/admin) is NOT here; exams/assignments/attendance stay deferred.

## Ground rules

1. **Detail-fill trigger:** when M1 **Phase-007 (editor)** and **Phase-009 (offline sync)** have merged to `staging`, each M2 phase below gets its own `Phase-2NN.md` file written against the real M1 code (services, components, composables by their actual names). Until then this README is the whole plan — don't implement from it.
2. **Numbering:** M2 phases are `Phase-201…Phase-212` (the `2` = milestone), so M1's `Phase-001…012` sequence stays untouched if either milestone gains a phase.
3. Everything the M1 plan's ["rules that outrank everything"](../2026-09-13-005-Implementation-Plan/README.md) says applies here unchanged: golden data-flow rule, D-027 guardrail (domain components in `@notebook/ui` — the teacher apps are thin shells too), types-are-the-contract, backend shape, same-commit conventions, one PR per phase to `staging`.
4. **The schema is already designed.** Tables 14–25 in [database-schema.md §6](../2026-09-13-004-M1-Foundation/plan/database-schema.md) carry the full classroom + assessment + chat domain with the brief's rules (invitation decline reason, answers-release trigger, question-bank immutable copy, score snapshots, one chat room per course). M2 planning does not redesign them; deviations found during implementation go through `decisions.md`.
5. Open questions live in [questions/](questions/README.md) — each ends with a `**Decision:**` line only the Lead Developer fills in (CLAUDE.md §7). **Close them before the detail-fill, not during it** — same trick that made the M1 plan self-sufficient.

## Phase index (milestone level)

| Phase | Title | Builds | Depends on |
|---|---|---|---|
| Phase-201 | Teacher workspace scaffold | `apps/teacher/{browser,mobile}` thin shells (D-025/D-027, mirrors student structure), teacher auth reusing M1's Firebase→Sanctum flow, teacher role gate | M1 Phase-004 |
| Phase-202 | Classroom database | migrations/models/seeders for tables 14–25 (courses, invitations, enrollment, lessons, progress, assessment tree, chat) | M1 Phase-002 |
| Phase-203 | Contract & services extension | course/lesson/assessment/chat domains in `@notebook/types`, services + mock fixtures per the datasource pattern | 202 · M1 Phase-003 |
| Phase-204 | Courses: teacher portal core | course CRUD, covers, school-year + start/end timeline ("expired" label), clone-for-next-term (`cloned_from_course_id`), course calendar view | 201, 203 |
| Phase-205 | Lesson authoring & delivery | Tiptap lessons (reuses M1's editor from `@notebook/ui`), availability windows, publish flow, video links (youtube/web_link), **lesson PDF/print pipeline (D-009)** | 204 · M1 Phase-007 |
| Phase-206 | Quiz builder & question bank | assessment CRUD (`kind='quiz'`), question types (MC/TF/short answer), options, shuffle/time-limit/attempts settings, bank reuse via `source_question_id` immutable copy | 205 |
| Phase-207 | Enrollment: invitations | teacher invites by student email, tokened invitation lifecycle (pending/accepted/declined+reason/revoked/expired), both-direction notifications (M1 Phase-010 plumbing), `course_students` creation | 204 · M1 Phase-010 |
| Phase-208 | Student course experience | course list + join, lesson viewing on both student form factors, `lesson_progress` completion (⇒ quiz activation), **RO offline cache of courses/lessons (D-016)** on the M1 sync engine | 205, 207 · M1 Phase-009 |
| Phase-209 | Quiz taking & scores | attempts (online-only, D-016), auto-grade MC/TF + manual-grade short answer, teacher-triggered answers release, score views for both roles, submit notification | 206, 208 |
| Phase-210 | Course-room chat | one Pusher room per course (D-021/D-007), membership auth from `course_students`/`teacher_id`, text + attachment messages | 207 |
| Phase-211 | Packaging update | `apps/teacher/native` Capacitor project (**appId `com.notebook.teacher`, reserved by D-030**), student native rebuild with M2 features, portrait lock parity | 201–210 · M1 Phase-011 |
| Phase-212 | M2 acceptance & handoff | full acceptance run against the brief's classroom claims, docs reality-check (CLAUDE.md §2/§10), tracker + status close-out | all |

Phases 205/206 (authoring) and 207 (enrollment) can interleave once 204 is in; 209 and 210 are independent of each other.

## Pre-implementation work that is NOT a phase

- **Teacher design passes** (parked by decision at M1 design close): Claude Design briefs for teacher browser + mobile on the Komposisyon palette (D-032), same pass structure as [2026-09-13-006](../2026-09-13-006-Brand-Colors/README.md). Should complete before Phase-201 starts — see Q-M2-1.
- **Tracker extension:** add `phase-201…212` rows to the live M1 Build Tracker artifact (or a second board on the same page) when the detail-fill happens.

## Decisions this plan implements

D-007 (paid Pusher) · D-008 (scores, not grades — attempts table IS the grade story) · D-009 (lesson PDF/print) · D-011 (M2 = classroom) · D-016 (courses RO offline; quiz/chat online-only) · D-018 (Tiptap lessons) · D-021 (course-room chat only) · D-022 (teacher-led growth — **schema hooks only**; enforcement is M3's entitlements module) · D-024 (schema §6) · D-025/D-027 (teacher platforms + thin shells) · D-030 (`com.notebook.teacher`).
