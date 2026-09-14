<?php

namespace App\Http\Controllers\Api;

use App\Models\FileUpload;
use App\Models\Notebook;
use App\Models\NotebookPage;
use App\Models\PageAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Page attachments (2026-09-13-005 Phase 008, D-016 / schema §2.5).
 *
 * The ROW is offline-writable; the FILE uploads when online. That split is why
 * `file_upload_id` is nullable and `upload_status` exists — a device creates
 * the row with status 'pending' and a local_ref, then patches in the id once
 * the bytes land (Phase 009). This controller serves the ONLINE path, where
 * both happen in one sitting.
 *
 * Deleting an attachment soft-deletes the ROW and never the file: another row
 * may reference the same upload, and the tombstone is what propagates.
 */
class PageAttachmentController extends ApiController
{
    private const COLUMNS = [
        'id', 'page_id', 'file_upload_id', 'kind', 'local_ref', 'upload_status',
        'client_updated_at', 'created_at', 'updated_at', 'deleted_at',
    ];

    /**
     * GET /api/pages/{page}/attachments
     */
    public function index(Request $request, string $pageId): JsonResponse
    {
        if ($this->ownedPage($request, $pageId) === null) {
            return $this->notFound('Page not found.');
        }

        $attachments = PageAttachment::query()
            ->select(self::COLUMNS)
            ->where('page_id', $pageId)
            ->orderBy('created_at')
            ->get()
            ->map(fn (PageAttachment $a) => $this->present($a));

        return $this->success($attachments);
    }

    /**
     * POST /api/pages/{page}/attachments?id&kind&file_upload_id&local_ref
     */
    public function store(Request $request, string $pageId): JsonResponse
    {
        if ($this->ownedPage($request, $pageId) === null) {
            return $this->notFound('Page not found.');
        }

        $data = $request->validate([
            'id' => ['required', 'uuid', Rule::unique('page_attachments', 'id')->withoutTrashed()],
            'kind' => ['required', Rule::in(['image', 'pdf', 'document'])],
            'file_upload_id' => ['nullable', 'uuid', 'exists:file_uploads,id'],
            'local_ref' => ['nullable', 'string', 'max:255'],
        ]);

        // An attachment may only point at the caller's OWN upload — otherwise a
        // guessed id would expose someone else's file through this page.
        if (isset($data['file_upload_id']) && ! $this->ownsUpload($request, $data['file_upload_id'])) {
            return $this->validationError(['file_upload_id' => ['That file does not exist.']]);
        }

        $attachment = new PageAttachment();

        $attachment->forceFill([
            ...$data,
            'page_id' => $pageId,
            'upload_status' => isset($data['file_upload_id']) ? 'uploaded' : 'pending',
            'client_updated_at' => now(),
        ])->save();

        return $this->created($this->present($attachment->fresh()), 'Attachment added');
    }

    /**
     * DELETE /api/attachments/{id}
     *
     * SOFT delete of the row. The file stays — see the class docblock.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $attachment = PageAttachment::find($id);

        if ($attachment === null || $this->ownedPage($request, $attachment->page_id) === null) {
            return $this->notFound('Attachment not found.');
        }

        $attachment->client_updated_at = now();
        $attachment->save();
        $attachment->delete();

        return $this->success(null, 'Attachment removed');
    }

    /** A page whose notebook the caller owns, or null. */
    private function ownedPage(Request $request, string $pageId): ?NotebookPage
    {
        $page = NotebookPage::find($pageId);

        if ($page === null) {
            return null;
        }

        return Notebook::owned($request->user())->find($page->notebook_id) === null ? null : $page;
    }

    private function ownsUpload(Request $request, string $uploadId): bool
    {
        return FileUpload::where('user_id', $request->user()->id)->whereKey($uploadId)->exists();
    }

    /** Attachment row + the file's served URL, for rendering. */
    private function present(PageAttachment $attachment): array
    {
        $row = $attachment->only(self::COLUMNS);

        $upload = $attachment->file_upload_id === null
            ? null
            : FileUpload::find($attachment->file_upload_id);

        $row['file_upload'] = $upload === null ? null : [
            ...$upload->only([
                'id', 'user_id', 'disk', 'path', 'original_name', 'mime_type',
                'size_bytes', 'sha256', 'created_at', 'updated_at',
            ]),
            'url' => Storage::disk($upload->disk)->url($upload->path),
        ];

        return $row;
    }
}
