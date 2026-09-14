# Phase 010 complete — Sharing links & notifications

_2026-09-14 · branch `Workstation-Laptop` · task 2026-09-13-005._

A student shares a notebook or a single page as a read-only link that works signed-out, can revoke it, and has the notification plumbing every M2/M3 feature will reuse.

## What shipped

**Backend**

| Endpoint | Notes |
|---|---|
| `POST /api/shares` | owner-checked; 64-hex token; `access` forced to `read` |
| `GET /api/shares?notebook_id` | own shares with `is_active` / `is_revoked` / `is_expired` flags |
| `POST /api/shares/{id}/revoke` | idempotent |
| `GET /api/shared/{token}` | **PUBLIC** — trimmed read-only shape |
| `GET /api/notifications` · `/unread-count` · `POST /{id}/read` · `/read-all` | paginated, own-only |

`App\Support\Notify` — the one way a notification is created. `Notify::welcome()` is wired into `AuthController::register()` **inside the transaction**, so a user never exists without their welcome.

**Frontend** — `ShareDialog.vue` + `useShares`, `NotificationList.vue` + `useNotifications`, `ReadOnlyContent.vue`, and the browser-only `/shared/:token` viewer. Share entry points in both notebook views; a bell with an unread badge in the browser library, a dedicated notifications screen on mobile.

## The public endpoint

This is the only unauthenticated route in the app that returns user content, so three rules hold it:

1. **The token is 256 bits** from `random_bytes(32)` — not guessable, not derived from any id.
2. **Every failure is the same 404.** Missing, revoked, expired and deleted-notebook all answer `"This link is no longer available."` The endpoint cannot confirm a token ever existed.
3. **The response is a trimmed shape, not the models.** No `user_id`, no `search_text`, no `client_updated_at`. A link leaks exactly what it renders — asserted in a test, not just intended.

## `ReadOnlyContent.vue` — not the editor with a flag

The viewer renders Tiptap JSON by walking it and emitting plain elements, rather than mounting Tiptap with `editable: false`.

Two reasons. Mounting the editor would ship the entire ProseMirror runtime to someone who is only reading, and it would leave **one boolean** between a signed-out visitor and an edit surface. Walking the JSON means there is nothing to toggle.

It renders the same whitelist the editor enables (imported from `@notebook/types`), so shared pages look identical to owned ones, and anything off-whitelist is skipped rather than passed through.

## Decisions worth knowing

1. **`read_write` is rejected at validation.** The schema has the column and M1 does not honour it, so accepting it would mint a link the viewer cannot keep its promise about.

2. **A page-scoped share must name a page in that notebook.** Without the check, a crafted `page_id` would publish a page from a different notebook through a link you control.

3. **Status flags are computed server-side** (`is_active` etc.) rather than re-derived from `expires_at` in three UIs that would each drift.

4. **The mobile app has no viewer route.** Per the phase file, a mobile user who receives a link opens it in their browser. Duplicating the viewer would mean two read-only renderers to keep in sync.

5. **Notifications are optimistic on mark-read** — the badge drops on tap and rolls back if the request fails. A badge that lags a tap feels broken.

## Verified

- **140 backend tests pass** (400 assertions); 25 new — token format and uniqueness, owner-only creation, foreign-notebook 404, page-must-belong validation, `read_write` rejected, signed-out resolve, **the public shape omitting owner and internal fields**, page-scoped showing exactly one page, revoked/expired/unknown/malformed all 404, deleted notebook killing the link, own-only listing with flags, foreign revoke 404, idempotent revoke, `Notify::send`/`sendMany` deduping, pagination, unread-first ordering, mark-read idempotence, cross-user isolation on read and read-all.
- **Live, signed-out:** created a link → resolved it with **no Authorization header** → got the notebook, 2 pages and the paper template, with `user_id` and `search_text` absent → revoked → the same link returned 404.
- **The welcome seed fires** on the real registration path with the `{route: 'library'}` deep-link shape.
- Root `pnpm typecheck` (7 projects) + `pnpm build:all` green; `refresh-docs` run (43 routes).

## Not done

**The manual pass:** create a link on the phone, open it on a signed-out desktop browser, confirm the paper renders and the revoked state reads well. Every layer is verified programmatically — including the signed-out resolve — but *"it looks right to a stranger"* is a visual judgement.

Carry-overs unchanged: the `⋯` card menu is still a `prompt()`, and the Phase 007/008 visual passes remain open. Sharing is reachable from the **notebook view**, not the card menu, because the card menu is that prompt.

## Notes for Phase 011

Android packaging, and the first phase where a real device is required. Nothing here needs native work: sharing is online-only (D-016), and the viewer is browser-only by design.

What Phase 011 owes this phase is small — confirm the share dialog's **clipboard** call works inside the Capacitor WebView. `useShares.copy()` already falls back to a `prompt()` showing the raw link when `navigator.clipboard` throws, which some WebViews do.
