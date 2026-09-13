# Phase 008 — Attachments: images & PDF

**Goal:** attach images to pages, set notebook covers, view attached PDFs. Storage is Laravel disks (never FTP — validation lesson), MIME-allowlisted, quota-aware.

**Prereq:** Phase-007. **Decisions:** D-016 (attachments RW-offline as rows, files upload-when-online), D-022/D-023 (storage quota is a premium lever — enforce a constant now, plans wire in M3).

## 1. Backend — `FileUploadController`

Banner `// ==== FILES (2026-09-13-005 Phase 008) ====`, `auth:sanctum`:

| Endpoint | Behavior |
|---|---|
| `POST /api/files` (`throttle:public-write`) | multipart `file` + `kind` (`image`/`pdf`/`document`/`cover`). Validate: MIME allowlist per kind (config `notebook.upload_mimes`: image ⇒ jpeg/png/webp, pdf ⇒ application/pdf…), max size per kind (image 10MB, pdf 25MB — config), **quota check**: `SUM(size_bytes) where user_id` + incoming ≤ `config('notebook.quota_mb', 500)` MB ⇒ else 422 envelope. Store on the `public` disk under `uploads/{user_id}/{uuid}.{ext}`; create `file_uploads` row (path, mime, size, sha256); return the contract `FileUpload` |
| `GET /api/files/{id}` | own file only — stream/redirect to the public URL (`Storage::url`) |

`disk` column stays `public` for now; the `s3` value is a config swap later (schema §5).

## 2. Wire-ups

- **Page attachments:** `attach(pageId, …)` in `attachment.service` creates the `page_attachments` row (client-minted id, `kind`, `file_upload_id` after upload; `upload_status` = `uploaded` on this online path — the `pending` path is Phase-009's). `DELETE` soft-deletes the row, never the file (other rows may reference the same upload later).
- **Editor image node** (reserved in Phase-007): toolbar "attach image" → file pick → `upload()` → insert image node with the served URL + the attachment row. PDFs attach as a page-level attachment list item (chip under the page), opening in a viewer: browser = new tab / `<embed>`; mobile = in-app viewer route (`<embed>`/pdf.js — simplest that works; note the choice in the completion file).
- **Notebook covers:** `NewNotebookDialog`/notebook settings gain cover pick → upload `kind=cover` → `PUT /api/notebooks/{id}` with `cover_upload_id`; `NotebookCard` renders it.
- Mobile file pick: plain `<input type="file" accept=…>` works inside the Capacitor WebView for MVP — the Camera plugin is Phase-011-optional, don't block on it.

## 3. Verification

- Feature tests: MIME rejection (rename a .exe to .jpg — content-based MIME must catch it: validate via `File::mimeType`, not extension), size cap, quota 422 at the boundary, ownership on `GET /api/files/{id}`.
- Manual: image into a Composition page (renders on paper at sane max-width), PDF attach + open, cover on the shelf card — both apps.

## Acceptance checklist

- [ ] Image attach renders in the editor and survives reload; PDF opens; cover shows on the card
- [ ] MIME allowlist is content-based; quota enforced with a clean envelope error
- [ ] No file ever hard-deleted; tests green
- [ ] typecheck + build:all green · refresh-docs ran · PR (`M1 Phase 008 — attachments`) · completion note
