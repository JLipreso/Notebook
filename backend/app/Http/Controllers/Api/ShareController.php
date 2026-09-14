<?php

namespace App\Http\Controllers\Api;

use App\Models\Notebook;
use App\Models\NotebookPage;
use App\Models\NotebookShare;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Read-only share links (2026-09-13-005 Phase 010, D-016).
 *
 * ONLINE-ONLY: shared editing never merges offline, so M1 ships token links
 * with `access = 'read'` and nothing else. `shared_with_user_id` (direct share)
 * and `read_write` are schema-ready but deliberately unreachable until a
 * decision says otherwise.
 *
 * The resolve endpoint is PUBLIC — the only unauthenticated route in the app
 * that returns user content. Three rules hold it safe:
 *
 *   1. The token is 64 hex chars from random_bytes(32) — 256 bits, not
 *      guessable and not derived from any id.
 *   2. Every failure (missing, revoked, expired) answers with the SAME 404, so
 *      the endpoint cannot confirm that a token ever existed.
 *   3. The response is a trimmed shape, not the models — no user_id, no
 *      timestamps, no search_text. A link leaks exactly what it renders.
 */
class ShareController extends ApiController
{
    private const COLUMNS = [
        'id', 'notebook_id', 'page_id', 'shared_by', 'shared_with_user_id',
        'share_token', 'access', 'expires_at', 'revoked_at', 'created_at', 'updated_at',
    ];

    /**
     * POST /api/shares?notebook_id&page_id&expires_at
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'notebook_id' => ['required', 'uuid'],
            'page_id' => ['nullable', 'uuid'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            // M1 is read-only. Accepting 'read_write' here would mint a link
            // the viewer cannot honour.
            'access' => ['nullable', Rule::in(['read'])],
        ]);

        $notebook = Notebook::owned($request->user())->find($data['notebook_id']);

        if ($notebook === null) {
            return $this->notFound('Notebook not found.');
        }

        // A page-scoped share must name a page IN that notebook — otherwise a
        // link could expose a page from somewhere else entirely.
        if (! empty($data['page_id'])) {
            $belongs = NotebookPage::where('id', $data['page_id'])
                ->where('notebook_id', $notebook->id)
                ->exists();

            if (! $belongs) {
                return $this->validationError(['page_id' => ['That page is not in this notebook.']]);
            }
        }

        $share = new NotebookShare();

        $share->forceFill([
            'notebook_id' => $notebook->id,
            'page_id' => $data['page_id'] ?? null,
            'shared_by' => $request->user()->id,
            'shared_with_user_id' => null,
            // 256 bits of randomness. NOT derived from the notebook id.
            'share_token' => bin2hex(random_bytes(32)),
            'access' => 'read',
            'expires_at' => $data['expires_at'] ?? null,
        ])->save();

        return $this->created($this->reload($share->id), 'Share link created');
    }

    /**
     * GET /api/shares?notebook_id
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'notebook_id' => ['nullable', 'uuid'],
        ]);

        $ownNotebooks = Notebook::owned($request->user())->select('id');

        $shares = NotebookShare::query()
            ->select(self::COLUMNS)
            ->whereIn('notebook_id', $ownNotebooks)
            ->when(isset($data['notebook_id']), fn ($q) => $q->where('notebook_id', $data['notebook_id']))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (NotebookShare $share) => $this->present($share));

        return $this->success($shares);
    }

    /**
     * POST /api/shares/{id}/revoke
     */
    public function revoke(Request $request, string $id): JsonResponse
    {
        $share = $this->ownedShare($request, $id);

        if ($share === null) {
            return $this->notFound('Share not found.');
        }

        // Idempotent: revoking twice is not an error, the link is just as dead.
        if ($share->revoked_at === null) {
            $share->forceFill(['revoked_at' => now()])->save();
        }

        return $this->success($this->reload($share->id), 'Share link revoked');
    }

    /**
     * GET /api/shared/{token}
     *
     * PUBLIC. See the class docblock for why every failure is the same 404.
     */
    public function resolve(string $token): JsonResponse
    {
        $share = NotebookShare::where('share_token', $token)->first();

        if ($share === null
            || $share->revoked_at !== null
            || ($share->expires_at !== null && $share->expires_at->isPast())) {
            return $this->notFound('This link is no longer available.');
        }

        $notebook = Notebook::with('notebookType')->find($share->notebook_id);

        // The notebook was deleted after the link was made — the link dies too.
        if ($notebook === null) {
            return $this->notFound('This link is no longer available.');
        }

        $pages = NotebookPage::query()
            ->select('id', 'position', 'title', 'content')
            ->where('notebook_id', $notebook->id)
            // A page-scoped share shows EXACTLY that page.
            ->when($share->page_id !== null, fn ($q) => $q->where('id', $share->page_id))
            ->orderBy('position')
            ->get();

        return $this->success([
            'notebook' => [
                'id' => $notebook->id,
                'title' => $notebook->title,
                'school_year' => $notebook->school_year,
                'notebook_type' => [
                    'key' => $notebook->notebookType?->key,
                    'name' => $notebook->notebookType?->name,
                    'page_template' => $notebook->notebookType?->page_template,
                ],
            ],
            'pages' => $pages,
            'access' => $share->access,
            'expires_at' => $share->expires_at?->toISOString(),
        ]);
    }

    private function ownedShare(Request $request, string $id): ?NotebookShare
    {
        return NotebookShare::query()
            ->whereIn('notebook_id', Notebook::owned($request->user())->select('id'))
            ->find($id);
    }

    private function reload(string $id): array
    {
        return $this->present(NotebookShare::query()->select(self::COLUMNS)->findOrFail($id));
    }

    /** Adds the derived flags the UI needs rather than re-deriving them client-side. */
    private function present(NotebookShare $share): array
    {
        $expired = $share->expires_at !== null && $share->expires_at->isPast();

        return [
            ...$share->only(self::COLUMNS),
            'is_revoked' => $share->revoked_at !== null,
            'is_expired' => $expired,
            'is_active' => $share->revoked_at === null && ! $expired,
        ];
    }
}
