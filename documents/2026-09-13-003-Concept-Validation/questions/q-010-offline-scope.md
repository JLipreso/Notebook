# Q-010 — What lives in the on-device SQLite copy?

"SQLite on tablet/mobile as a local copy of data" is locked in direction — but the sync engine's size depends entirely on WHICH data is offline-capable. Every offline-writable table needs conflict handling; every offline-readable table only needs cursor pulls.

**Option A — Notebooks read-write; course content read-only (recommended).** Offline-writable: notebooks, pages, page attachments (queued upload). Offline-readable cache: enrolled courses, lessons, own grades/scores, profile. Online-only: quiz/exam taking, chat, payments, sharing changes, invitations.
- ✅ The student's own notebook — the core promise — works on a jeepney with no signal; write-conflict surface is tiny (single author per notebook in MVP); quizzes stay online, which the time-limit + anti-cheat rules want anyway.
- ❌ "I answered the quiz offline" is not supported — must be communicated in UX.

**Option B — Notebooks read-write only; nothing else cached.**
- ✅ Smallest possible sync engine.
- ❌ Opening the app offline shows empty courses/lessons — feels broken for a student commuting.

**Option C — Everything offline including quiz attempts.**
- ✅ Maximal resilience.
- ❌ Offline quiz attempts + time limits + answer-reveal rules = clock-tampering and integrity problems; conflict handling explodes; slowest to ship.

**Decision:** Option A — notebooks/pages/attachments read-write offline; courses/lessons/scores/profile read-only cache; quiz taking, chat, payments, sharing online-only. Locked 2026-09-13 as **D-016** (Lead Developer via option prompt).
