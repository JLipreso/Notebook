# MIIT ("Elea") Monorepo Scan — Findings

_Task 2026-09-13-003. Source: `D:\Software-Dev-Projects\Jazer-Tech\Monorepo-MIIT`, scanned 2026-09-13._
_Best pre-existing doc inside MIIT itself: `documents/2026-07-25-000-Claude/analysis.md` (note: its "Laravel 11" and "Pinia/Tailwind" claims are wrong — verified below)._

## 1. What the product is

**"Elea"** — a multi-tenant e-learning/LMS SaaS sold B2B to Philippine schools (tenants: MIIT, PAP, ACT, one ended contract). Each school gets thin `student-web` / `teacher-web` / `admin-web` wrappers over one shared Vue library. Three user classes with separate DB tables and Sanctum guards: **students**, **staff** (teachers, `is_security` flag), **admins**. The same monorepo also hosts a B2B sales side (`apps/business/`): marketing site + internal CRM (leads, inquiries, leads map).

## 2. Domain model (64 MySQL tables; dump at `database-copy/2026-08-02-08-46.sql`)

- **Tenancy/academic spine:** `schools` → `school_id` added to nearly every table; `academic_years` → `academic_terms` → `sem_terms`; `department`, `student_standing`.
- **Courses:** `subjects` (code, title, thumbnail, description, objectives, requirements, weight columns) → `subject_offerings` (per term) → `subject_instructors`. `syllabi` → `syllabus_meetings`.
- **Lessons:** `subjects_lessons` — title/description/topic/activity/outcome all **mediumText Quill HTML**, `video` + `video_type ENUM('upload','youtube','web_link')`, `sort_order`, `active`.
- **Enrollment:** `students_subjects` (unique student+subject). **No invitation flow** — teachers add students directly; credentials emailed.
- **Progress:** `students_lessons` — status enum, `progress_percent`, `time_spent_seconds`, plus embedded assignment columns (`activity_submitted_date`, `activity_score`, `activity_score_over`, …).
- **Quizzes** (lesson-scoped): `quizzes` (time_limit_minutes, passing_score, max_attempts, shuffle flags, show_correct_answers) → `quiz_questions` (`ENUM('multiple_choice','true_false','short_answer')`, points, sort_order, explanation) → `quiz_options` (is_correct); `quiz_attempts` (snapshot totals, passed, status, unique student+quiz+attempt) → `quiz_answers` (unique attempt+question, `points_earned`, `graded_by/graded_at` so auto- and manual-grading coexist).
- **Exams** (subject-scoped near-clone): `exams` → `exam_sections` (`quiz_based`/`essay`, points_per_item) → `exam_questions` (adds `essay`, and **`source_question_id` FK back to `quiz_questions`** — exams assembled from the quiz bank while keeping an immutable copy) → `exam_question_options`; attempts/answers mirror quizzes. `exam_lessons` maps coverage.
- **Assignments: dead schema.** `assignments`, `assignment_types`, `assignment_submissions`, `project_groups`, `peer_evaluations` etc. exist with **no controllers/routes** — superseded by the lesson `activity_*` columns.
- **Attendance:** `subject_attendance` — binary `present`/`absent` only.
- **Grades — two competing models:** `student_grades` (rich per-component score/weighted columns, grade_scale, grading components) is **largely unused**; the live path stores `subjects.weight_quizzes/weight_exams/weight_activities/weight_attendance/weight_lessons` (defaults 30/30/20/10/10, must sum 100) and `Student/DashboardController::computeOverallGrade()` **recomputes the whole class on every request** (loads every enrolled student's aggregates to derive one student's rank); logic duplicated in `Teacher/SubjectStatisticsController`. Untaken quizzes count as 0.
- **Notifications:** `staff_notifications` + `students_notifications` — identical shape, duplicated per role; dispatch/read pipeline incomplete.
- **Chat:** `chat_rooms` (one per school+subject) + `chat_messages` (`sender_type ENUM('student','staff')`, denormalized sender_name). Pusher + Echo; channel auth derived from enrollment in `routes/channels.php`.
- **Forum:** `forum_posts` (subject-scoped, counters, pinned/closed) + replies/reactions/attachments.
- **No payments, no subscriptions, no address/region tables** anywhere (`students.address` is free text).

## 3. Backend shape

**Laravel 10** (PHP ^8.1), `laravel/sanctum ^3.2`, `kreait/laravel-firebase`, `pusher/pusher-php-server`, `anthropic-ai/sdk`, `league/flysystem-ftp`, `carlos-meneses/laravel-mpdf`.

- **Auth:** three Sanctum guards (`web`, `staff`, `student`); tokens carry abilities, 7-day expiry, `elea_` prefix. **Firebase used only for Google Sign-In** — `staffGoogleLogin` verifies the Firebase ID token via Kreait, then issues a Sanctum token. ⚠ `firebase-service-account.json` is **committed to the repo**.
- **Tenancy:** `ResolveSchool` middleware binds `current_school_id` from the token's user; global scopes on models.
- **Envelope:** `Api/ApiController` — `success()/error()/paginated()` (+ `created()/noContent()/unauthorized()/forbidden()/notFound()`). Same shape as our §3/§5 conventions. Worth copying verbatim.
- **Routes:** `routes/api.php` (~21KB) + 8 feature includes. Controllers by audience: `Api/Admin` (10), `Api/Teacher` (20), `Api/Student` (8), `Api/Common` (3). Validation inline; `Common/QuizController` and `Teacher/ExamController` are enormous (multi-thousand-line).
- **Uploads:** `FTPManagerController` → flysystem **FTP disk**, MIME allowlist per category, metadata in `file_uploads`.
- **AI:** `POST /api/ai/chat` on the Anthropic PHP SDK — AI lesson/syllabus generation for teachers.

## 4. Frontend shape

pnpm workspace, **Vue 3.5 + Vite 7 + TS 5.9 + vue-router 4**. **No Pinia, no Tailwind** — Bootstrap 5.3 + bootstrap-icons, CSS-variable theming (`--elea-primary`) in `core/base-website/styles/elea-theme.css`. State is component-local + services + localStorage.

`core/` (`@miit/core`) is the real product; 13 app workspaces are ~50-line shells:
- `core/base-website/{student,teacher,admin}/` with `create-app.ts` factories + `SchoolConfig` provide/inject (name, logo, colors, API URL). A tenant app calls `createStudentApp(config)` and overrides theme CSS + `.env`.
- `core/services/` ~35 domain services; `core/types/` ~31 interface files; `core/utility/` (`api.ts` with `setApiHost()` + school-prefixed `storageKey()`, `firebase.ts`, localStorage helpers, sweetalert).
- 20 `core/export-*.ts` entry points in `package.json#exports` for tree-shaking.
- Libraries: `@vueup/vue-quill` (teacher only), FullCalendar, chart.js, swiper, sweetalert2, laravel-echo + pusher-js, firebase.

## 5. Feature inventory (from router files)

- **Student:** login, dashboard, courses → lessons → lesson (+quiz), per-subject forum, quiz list, exam + take-exam, class-standing (rank), grades, calendar, settings; lesson chat + class group chat.
- **Teacher (19 routes):** dashboard, my-subjects (+create), per-subject lessons/edit, students, attendance, statistics, quizzes, exams, forum, grades, settings; global students, academic management, quiz/exam builders, syllabus builder, analytics, calendar.
- **Admin (9 routes):** dashboard, teachers, students, subjects, syllabus, academic, security.
- **Business admin-panel:** Clients, Leads, LeadsMap, Inquiry, Marketing, PricingServices.

## 6. Patterns to copy / avoid

**Copy:**
1. `ApiController` envelope + `paginated()`.
2. **Normalized question/option/attempt/answer tables** (not JSON blobs) with snapshot totals and `graded_by` for mixed auto/manual grading.
3. `exam_questions.source_question_id` — assessment built from a question bank, stored as an immutable copy.
4. Core-library + thin-app factory pattern with config injection + CSS variables (white-labeling).
5. Broadcast channel auth derived from enrollment.

**Avoid / redesign:**
1. Two competing grade models; whole-class recompute per page view; duplicated grade logic.
2. Duplicated per-role notification tables; duplicated quiz-vs-exam tree (unify with a `kind` discriminator, keep the bank idea).
3. Mixed `latin1`/`utf8mb4` collations; mixed PK signedness.
4. Raw Quill HTML in mediumText, no sanitization, no structured block model.
5. FTP-disk uploads; committed service-account secret; hard deletes everywhere; zero tests; multi-thousand-line controllers.
6. Dead schema shipped to production (assignments/project-groups/peer-evaluations family).
7. No invitation flow (our brief requires accept/decline with reason — must be net-new).

## 7. Offline / mobile / SQLite

**None.** No SQLite, IndexedDB, or sync anywhere. Mobile = legacy Ionic + Capacitor apps (`apps/client/*/student-mobile`) over `core/base-mobile/student/`. Only persistence trick: `storage.ts` hydrates localStorage from Capacitor Preferences at startup and monkey-patches `setItem/removeItem` to mirror the four school-scoped **auth keys only**. Offline-first is net-new engineering for Notebook and must be designed into the schema from migration one (IDs, cursors, tombstones — see Q-009/Q-010).
