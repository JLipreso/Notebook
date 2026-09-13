# Phase 010 — Sharing links & notifications

**Goal:** a student shares a notebook (or single page) as a **read-only link** (M1 scope — read_write lands post-M1), can revoke it, and gets in-app notifications with the plumbing every later feature reuses. Sharing is ONLINE-ONLY (D-016).

**Prereq:** Phase-007. **Decisions:** D-016, D-023; schema §5 (`notebook_shares`, `notifications`).

## 1. Backend

### `ShareController` — banner `// ==== SHARES (2026-09-13-005 Phase 010) ====`
| Endpoint | Behavior |
|---|---|
| `POST /api/shares` (auth) | body: `notebook_id`, optional `page_id` (that page only), optional `expires_at`. Owner-checked. Generates `share_token` = 64 random hex (`bin2hex(random_bytes(32))`), `access='read'` hard-coded in M1 (validate `access` absent or `read`). Returns the share with the token |
| `GET /api/shares?notebook_id=` (auth) | own active shares (revoked/expired flagged) |
| `POST /api/shares/{id}/revoke` (auth) | sets `revoked_at` |
| `GET /api/shared/{token}` (**public**, `throttle:public`) | resolve: valid + not revoked + not expired ⇒ notebook meta + pages (`content` included, read-only shape; single page if page-scoped). Invalid ⇒ 404 envelope. `{token}` `->where('token','[0-9a-f]{64}')` |

Register the literal `/api/shared/{token}` BEFORE any resource wildcards (§5 rule). If `shared_with_user_id` (direct share) is requested by the Lead Developer later, it's a schema-ready extension — M1 ships token links only.

### `NotificationController`
`GET /api/notifications?page=` (paginated envelope) · `POST /api/notifications/{id}/read` · `POST /api/notifications/read-all`. **Plus the one internal helper every future feature calls:** `App\Support\Notify::send($userId, $type, $title, $body?, $data?)` inserting a row — chat/invitations/payments all reuse this in M2/M3. Wire it now: sharing to a known user (future) and… nothing else fires in M1? Correct — seed one `welcome` notification at registration (Phase-004's controller, small backfill commit here) so the UI has real data.

## 2. Frontend

- Share dialog (shared component `packages/ui/share/ShareDialog.vue`): scope picker (whole notebook / this page), optional expiry, create → copy-link UI, list existing links with revoke. Entry points: notebook card menu + page view toolbar, both apps.
- **Public share viewer**: route `/shared/:token` in the **browser app only** (mobile users receiving a link open it in their browser — no auth): renders `PaperPage` read-only with the shared content; friendly expired/revoked state. No editor mount, no auth store dependency — this route must work signed-out (adjust the Phase-004 route guard to allowlist it).
- Notifications UI: bell + unread badge (browser header) / notifications tab (mobile); list, mark-read, mark-all. `AppNotification.data` carries a deep-link the UI follows when tapped (define `{ route, params }` shape now).

## 3. Verification

- Feature tests: token resolve happy path, revoked ⇒ 404, expired ⇒ 404, page-scoped returns only that page, owner-only creation/revocation; notifications pagination + read flows.
- Manual: create link in mobile app → open in a signed-out desktop browser → paper renders read-only; revoke → refreshed link 404s.

## Acceptance checklist

- [ ] Read-only link round-trip works signed-out; revoke and expiry enforced
- [ ] Page-scoped share shows exactly one page; whole-notebook shows all
- [ ] Notifications list/read works in both apps; `Notify::send` helper in place with the welcome seed
- [ ] Tests green · typecheck + build:all green · refresh-docs ran · PR (`M1 Phase 010 — sharing & notifications`) · completion note
