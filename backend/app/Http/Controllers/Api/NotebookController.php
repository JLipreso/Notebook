<?php

namespace App\Http\Controllers\Api;

use App\Models\Notebook;
use App\Models\NotebookType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The student's notebook shelf (2026-09-13-005 Phase 006, D-010/D-011/D-013).
 *
 * Ownership is enforced through Notebook::owned() on EVERY query — including
 * Phase 009's sync push. A row belonging to someone else is reported as 404,
 * never 403: a 403 would confirm the id exists.
 */
class NotebookController extends ApiController
{
    /** The contract columns (D-005) — never `select *`. */
    private const COLUMNS = [
        'id', 'user_id', 'notebook_type_id', 'title', 'school_year',
        'cover_upload_id', 'font_family', 'status', 'archived_at', 'position',
        'client_updated_at', 'created_at', 'updated_at', 'deleted_at',
    ];

    /**
     * GET /api/notebook-types
     *
     * Active types with their page_template. Public shape, but auth-gated like
     * the rest of the shelf — an unauthenticated caller has no use for it.
     */
    public function types(): JsonResponse
    {
        $types = NotebookType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return $this->success($types);
    }

    /**
     * GET /api/notebooks?status=&school_year=
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['sometimes', Rule::in(['active', 'archived'])],
            'school_year' => ['sometimes', 'string', 'regex:/^\d{4}-\d{4}$/'],
        ]);

        $notebooks = Notebook::owned($request->user())
            ->select(self::COLUMNS)
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['school_year']), fn ($q) => $q->where('school_year', $filters['school_year']))
            ->orderBy('position')
            ->orderBy('created_at')
            ->get();

        return $this->success($notebooks);
    }

    /**
     * POST /api/notebooks?id&notebook_type_id&title&school_year&font_family&position
     *
     * The id is MINTED ON THE CLIENT (D-013) and inserted as-is, so the same
     * code path works offline in Phase 009. Uniqueness is checked against
     * trashed rows too — a recycled id would resurrect a tombstone.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => ['required', 'uuid', Rule::unique('notebooks', 'id')->withoutTrashed()],
            'notebook_type_id' => ['required', 'uuid', 'exists:notebook_types,id'],
            'title' => ['required', 'string', 'max:150'],
            'school_year' => ['required', 'string', 'regex:/^\d{4}-\d{4}$/'],
            'font_family' => ['nullable', 'string', 'max:64'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        $notebook = new Notebook();

        $notebook->forceFill([
            ...$data,
            // NEVER from the request: the owner is always the caller.
            'user_id' => $request->user()->id,
            'status' => 'active',
            'position' => $data['position'] ?? Notebook::owned($request->user())->count(),
            'client_updated_at' => now(),
        ])->save();

        return $this->created($this->reload($notebook), 'Notebook created');
    }

    /**
     * GET /api/notebooks/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $notebook = Notebook::owned($request->user())->select(self::COLUMNS)->find($id);

        return $notebook === null
            ? $this->notFound('Notebook not found.')
            : $this->success($notebook);
    }

    /**
     * PUT /api/notebooks/{id}?title&font_family&position&notebook_type_id&cover_upload_id
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $notebook = Notebook::owned($request->user())->find($id);

        if ($notebook === null) {
            return $this->notFound('Notebook not found.');
        }

        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:150'],
            'font_family' => ['nullable', 'string', 'max:64'],
            'position' => ['sometimes', 'integer', 'min:0'],
            'notebook_type_id' => ['sometimes', 'uuid', 'exists:notebook_types,id'],
            // Phase 008 sets this once uploads exist.
            'cover_upload_id' => ['nullable', 'uuid', 'exists:file_uploads,id'],
        ]);

        $notebook->fill([...$data, 'client_updated_at' => now()])->save();

        return $this->success($this->reload($notebook), 'Notebook updated');
    }

    /**
     * POST /api/notebooks/{id}/archive
     *
     * Archiving is the product promise (D-023) — the notebook stays readable
     * forever, it just leaves the active shelf.
     */
    public function archive(Request $request, string $id): JsonResponse
    {
        return $this->setArchived($request, $id, true);
    }

    /**
     * POST /api/notebooks/{id}/unarchive
     */
    public function unarchive(Request $request, string $id): JsonResponse
    {
        return $this->setArchived($request, $id, false);
    }

    /** Re-read a row through the contract column list (fresh() takes relations). */
    private function reload(Notebook $notebook): Notebook
    {
        return Notebook::query()->select(self::COLUMNS)->findOrFail($notebook->id);
    }

    private function setArchived(Request $request, string $id, bool $archived): JsonResponse
    {
        $notebook = Notebook::owned($request->user())->find($id);

        if ($notebook === null) {
            return $this->notFound('Notebook not found.');
        }

        $notebook->fill([
            'status' => $archived ? 'archived' : 'active',
            'archived_at' => $archived ? now() : null,
            'client_updated_at' => now(),
        ])->save();

        return $this->success(
            $this->reload($notebook),
            $archived ? 'Notebook archived' : 'Notebook restored',
        );
    }

    /**
     * DELETE /api/notebooks/{id}
     *
     * SOFT delete. The row survives as a tombstone so the delete propagates to
     * other devices on the next pull (schema §2.4) — a hard delete would let
     * an offline device resurrect it.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $notebook = Notebook::owned($request->user())->find($id);

        if ($notebook === null) {
            return $this->notFound('Notebook not found.');
        }

        $notebook->client_updated_at = now();
        $notebook->save();
        $notebook->delete();

        return $this->success(null, 'Notebook deleted');
    }
}
