<?php

namespace App\Http\Controllers\Api;

use App\Models\Notebook;
use App\Models\NotebookPage;
use App\Support\TiptapSanitizer;
use App\Support\TiptapText;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Notebook pages (2026-09-13-005 Phase 007, D-012/D-013/D-018).
 *
 * Ownership is transitive: a page belongs to whoever owns its notebook, so
 * every route resolves the notebook through Notebook::owned() first. A page in
 * someone else's notebook is a 404, never a 403.
 *
 * EVERY content write is sanitized and re-flattens search_text. That is not
 * belt-and-braces — Phase 009's sync push writes content too, and both paths
 * must strip the same way.
 */
class NotebookPageController extends ApiController
{
    /** Contract columns (D-005) — `content` included, it is the page. */
    private const COLUMNS = [
        'id', 'notebook_id', 'position', 'title', 'content', 'search_text',
        'client_updated_at', 'created_at', 'updated_at', 'deleted_at',
    ];

    /**
     * GET /api/notebooks/{notebook}/pages
     */
    public function index(Request $request, string $notebookId): JsonResponse
    {
        if ($this->ownedNotebook($request, $notebookId) === null) {
            return $this->notFound('Notebook not found.');
        }

        $pages = NotebookPage::query()
            ->select(self::COLUMNS)
            ->where('notebook_id', $notebookId)
            ->orderBy('position')
            ->orderBy('created_at')
            ->get();

        return $this->success($pages);
    }

    /**
     * POST /api/notebooks/{notebook}/pages?id&position&title&content
     *
     * The id is minted on the client (D-013) and stored verbatim.
     */
    public function store(Request $request, string $notebookId): JsonResponse
    {
        if ($this->ownedNotebook($request, $notebookId) === null) {
            return $this->notFound('Notebook not found.');
        }

        $data = $request->validate([
            'id' => ['required', 'uuid', Rule::unique('notebook_pages', 'id')->withoutTrashed()],
            'position' => ['nullable', 'integer', 'min:0'],
            'title' => ['nullable', 'string', 'max:150'],
            'content' => ['nullable', 'array'],
        ]);

        try {
            $content = TiptapSanitizer::clean($data['content'] ?? null);
        } catch (RuntimeException $e) {
            return $this->validationError(['content' => [$e->getMessage()]]);
        }

        $page = new NotebookPage();

        $page->forceFill([
            'id' => $data['id'],
            'notebook_id' => $notebookId,
            'position' => $data['position'] ?? NotebookPage::where('notebook_id', $notebookId)->count(),
            'title' => $data['title'] ?? null,
            'content' => $content,
            'search_text' => TiptapText::flatten($content),
            'client_updated_at' => now(),
        ])->save();

        return $this->created($this->reload($page->id), 'Page created');
    }

    /**
     * GET /api/pages/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $page = $this->ownedPage($request, $id);

        return $page === null
            ? $this->notFound('Page not found.')
            : $this->success($this->reload($id));
    }

    /**
     * PUT /api/pages/{id}?title&position&content
     *
     * The autosave target. Phase 009 reroutes this exact call through the
     * outbox, so the shape must not drift.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $page = $this->ownedPage($request, $id);

        if ($page === null) {
            return $this->notFound('Page not found.');
        }

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:150'],
            'position' => ['sometimes', 'integer', 'min:0'],
            'content' => ['sometimes', 'nullable', 'array'],
        ]);

        if (array_key_exists('content', $data)) {
            try {
                $content = TiptapSanitizer::clean($data['content']);
            } catch (RuntimeException $e) {
                return $this->validationError(['content' => [$e->getMessage()]]);
            }

            $data['content'] = $content;
            // search_text is derived, never accepted from the client.
            $data['search_text'] = TiptapText::flatten($content);
        }

        $page->forceFill([...$data, 'client_updated_at' => now()])->save();

        return $this->success($this->reload($id), 'Page saved');
    }

    /**
     * DELETE /api/pages/{id}
     *
     * SOFT delete — the tombstone is what propagates the delete (schema §2.4).
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $page = $this->ownedPage($request, $id);

        if ($page === null) {
            return $this->notFound('Page not found.');
        }

        $page->client_updated_at = now();
        $page->save();
        $page->delete();

        return $this->success(null, 'Page deleted');
    }

    /** The caller's notebook, or null — the single ownership gate. */
    private function ownedNotebook(Request $request, string $notebookId): ?Notebook
    {
        return Notebook::owned($request->user())->find($notebookId);
    }

    /** A page whose notebook the caller owns, or null. */
    private function ownedPage(Request $request, string $id): ?NotebookPage
    {
        $page = NotebookPage::find($id);

        if ($page === null) {
            return null;
        }

        return $this->ownedNotebook($request, $page->notebook_id) === null ? null : $page;
    }

    private function reload(string $id): NotebookPage
    {
        return NotebookPage::query()->select(self::COLUMNS)->findOrFail($id);
    }
}
