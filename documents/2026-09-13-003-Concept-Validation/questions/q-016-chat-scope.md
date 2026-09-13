# Q-016 — Messenger scope for MVP

The brief wants a full messenger: private 1-on-1 (any role pairing), group chats, and file attachments. MIIT ships only course-room chat (one room per class). A general messenger is a product of its own — and minor-to-minor private DMs with attachments carry real moderation/safeguarding exposure.

**Option A — Course-room chat first (recommended).** One chat room per course (MIIT-proven pattern, ports directly to Reverb); teacher is always present in the room. Private/group DMs deferred to a later milestone with a moderation plan (reporting, blocking).
- ✅ Covers the actual classroom communication need; teacher presence is a natural safeguard; smallest realtime surface.
- ❌ No student-to-student DMs at launch.

**Option B — Full messenger as briefed.**
- ✅ Feature-complete vs the brief.
- ❌ Weeks of extra MVP work (threads, presence, receipts, attachment storage); unsupervised minor-to-minor DMs need reporting/blocking/retention policies before launch, not after.

**Option C — Defer all chat.** Notifications only in MVP.
- ✅ Smallest MVP.
- ❌ Teachers expect at least a class channel; weakens the classroom layer's pull.

**Decision:** Option A — course-room chat first (one room per course, teacher always present, on Pusher per D-007); private/group DMs deferred until a moderation plan exists. Locked 2026-09-13 as **D-021** (Lead Developer via option prompt).
