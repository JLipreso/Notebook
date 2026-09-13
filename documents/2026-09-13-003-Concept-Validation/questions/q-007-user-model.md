# Q-007 — User model: one table or three?

MIIT uses separate `students` / `staff` / `admins` tables with three Sanctum guards. Notebook's brief has students, teachers, admins — and chat/notifications/sharing that cross those roles (MIIT's per-role duplication of notification tables is a direct consequence of the three-table choice).

**Option A — Single `users` table + `role` column (recommended).** One Sanctum guard; role-specific profile data in `student_profiles` / `teacher_profiles` satellite tables if needed.
- ✅ One FK for every polymorphic feature (chat sender, notification recipient, notebook sharee); one auth path; simplest queries; a person can hold both roles later (a teacher who studies).
- ❌ Role checks are middleware/policy discipline rather than guard-enforced separation.

**Option B — Separate tables per role (MIIT style).**
- ✅ Hard separation; MIIT-familiar.
- ❌ Every cross-role feature needs `sender_type/sender_id` pairs or duplicated tables; three auth flows; proven painful in MIIT (duplicated notifications, denormalized sender names in chat).

**Decision:** Option A — one `users` table + `role` column, profile satellites. Locked 2026-09-13 as **D-014** (Lead Developer via option prompt).
