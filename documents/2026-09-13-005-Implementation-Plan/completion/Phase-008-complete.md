# Phase 008 complete — Attachments: images & PDF

_2026-09-14 · branch `Workstation-Laptop` · task 2026-09-13-005._

Images attach into the page, PDFs attach as chips beneath it, and notebooks take cover photos. Storage is Laravel disks, MIME-allowlisted by content, quota-aware.

## What shipped

**Backend**

| Endpoint | Notes |
|---|---|
| `POST /api/files` | multipart `file` + `kind`; content-based MIME check, per-kind size cap, quota check |
| `GET /api/files/usage` | own total vs quota — literal route, registered before `{id}` |
| `GET /api/files/{id}` | own file only, with its served URL |
| `GET/POST /api/pages/{page}/attachments` | attachment rows, transitively ownership-checked |
| `DELETE /api/attachments/{id}` | soft-deletes the ROW; the file is never touched |

`config/notebook.php` gained `upload_mimes`, `upload_max_bytes` (image 10MB, cover 5MB, pdf 25MB) and `quota_mb` (500).

**Frontend** — `useAttachments` + `AttachmentBar` in `packages/ui`, an attach button on the toolbar, image styling on the paper, cover support on `NotebookCover`/`NotebookCard`/`NotebookGrid`, and `setCover` in `useNotebooks`. Both apps wire a hidden `<input type="file">` — which works inside the Capacitor WebView, so Phase 011 needs no Camera plugin for MVP.

## 🔴 The test that was passing for the wrong reason

The phase file asks for a specific check: *rename a `.exe` to `.jpg` — content-based MIME must catch it*. I wrote it with `UploadedFile::fake()` and **it failed: the disguised file was accepted with a 201.**

The controller was correct all along. The problem is that **`Illuminate\Http\Testing\File` overrides `getMimeType()` to return `MimeType::from($this->name)`** — the type guessed from the *filename*. Under a fake, a disguised executable reports `image/jpeg` and sails through, regardless of what the code does.

That cuts both ways and is the dangerous part: had I written the assertion the other way round, or trusted a green result, **the test would have "proved" content sniffing that was never exercised.** A passing suite here would have meant nothing.

Fixed by constructing a real `UploadedFile` over real bytes on disk. Confirmed live against the running API: the disguised executable is rejected as **`application/x-dosexec`**, and a genuine PNG named `.txt` is accepted as `image/png` — content wins over extension in both directions. Logged in [001-Learnings.md](../../0000-00-00-000-Memory/001-Learnings.md).

## Decisions worth knowing

1. **A new `upload` rate limiter, keyed by USER not IP.** The phase file says `throttle:public-write`, which is 5/min by IP. Uploads are authenticated, and an IP key would make one school's shared connection throttle every student at once. 30/min per user instead. The existing limiters are untouched.

2. **Upload and attach are two calls, deliberately.** The row carries `file_upload_id`, so bytes must land first on the online path. Phase 009 inverts this — row first with `upload_status: 'pending'` and a `local_ref`, bytes when a connection returns — and that split is exactly why they are separate endpoints rather than one.

3. **An attachment may only reference the caller's own upload.** Without that check, a guessed `file_upload_id` would expose someone else's file through a page you control. Tested.

4. **PDFs open via `window.open` in both apps** — the phase file allows `<embed>`/pdf.js on mobile, but the WebView delegates to the device's PDF viewer, which is better than shipping a viewer we would have to maintain. Worth revisiting in Phase 011 if the Android experience disappoints.

5. **Images get a whole-line block margin** (`margin: var(--paper-line-height) 0`) so text after an image lands back **on** the ruling. An arbitrary margin would break the Phase-007 fidelity everywhere an image appears.

## Verified

- **91 backend tests pass** (276 assertions); 17 new — content-vs-extension both directions, PDF rejected as image and accepted as pdf, unknown kind, per-kind size caps, **quota against the running total** and per-user isolation, usage reporting, own-file-only `show`, attachment linking, the `pending` path, foreign-upload rejection, transitive 404s, and soft delete keeping the file.
- **Live against the running API:** real PNG → 201 with a working served URL and a sha256; disguised executable → 422 naming `application/x-dosexec`; the same PDF → 422 as `image`, 201 as `pdf`; quota usage reported correctly. Test uploads cleaned up afterwards.
- `storage:link` created and confirmed gitignored, as is `storage/app/public/**` — **no uploaded file can be committed.**
- Root `pnpm typecheck` (7 projects) + `pnpm build:all` green; `refresh-docs` run (33 routes).

## Not done — needs a human with a browser

**The manual pass:** an image into a Composition page rendering at a sane width on the ruling, a PDF chip opening, a cover appearing on the shelf card — in both apps. I verified every layer beneath that (upload, storage, serving, the editor's `setImage` call, the cover field round trip), but *"the photo looks right on the paper"* is a visual judgement.

Two carry-overs, unchanged: the `⋯` card menu is still a `prompt()` (it now offers **C** for cover), and Phase-007's fidelity eyeball is still open.

## Notes for Phase 009

Offline sync — the phase this one was built to feed. `page_attachments` rows already carry `upload_status` and `local_ref`, and the `pending` path is tested. What Phase 009 adds is the device side: create the row offline with a `local_ref`, then `attachment-uploader.ts` walks pending rows when a connection returns, uploads via this phase's `POST /api/files`, and patches in the `file_upload_id`.
