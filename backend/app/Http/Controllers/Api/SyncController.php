<?php

namespace App\Http\Controllers\Api;

use App\Models\Notebook;
use App\Models\NotebookPage;
use App\Models\NotebookType;
use App\Models\PageAttachment;
use App\Models\User;
use App\Support\TiptapSanitizer;
use App\Support\TiptapText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The offline sync engine's server half (2026-09-13-005 Phase 009).
 *
 * Implements database-schema.md §2 verbatim. That section is the contract; any
 * deviation here needs a decision, not a code comment.
 *
 * The three clauses that carry everything:
 *
 *   §2.2 PULL returns rows with updated_at > since INCLUDING TOMBSTONES. Drop
 *        the tombstones and a delete never propagates — the row simply
 *        reappears on the next device that pulls.
 *
 *   §2.3 PUSH is last-write-wins on client_updated_at. An incoming row older
 *        than the stored one is REJECTED and the winning server copy is
 *        returned, so the loser converges instead of silently overwriting.
 *
 *   §2.4 Deletes are soft on both sides and travel as ordinary updates.
 */
class SyncController extends ApiController
{
    /**
     * GET /api/sync/{table}?since&limit
     */
    public function pull(Request $request, string $table): JsonResponse
    {
        if (! $this->isSyncable($table)) {
            return $this->notFound("Unknown sync table [{$table}].");
        }

        $data = $request->validate([
            'since' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1'],
        ]);

        $max = (int) config('notebook.sync_pull_limit', 500);
        $limit = min((int) ($data['limit'] ?? $max), $max);
        $since = isset($data['since']) ? Carbon::parse($data['since']) : null;

        $query = $this->scopedQuery($request, $table);

        if ($since !== null) {
            $query->where('updated_at', '>', $since);
        }

        $rows = $query->orderBy('updated_at')->orderBy('id')->limit($limit)->get();

        // The cursor is the LAST row's updated_at. Null when caught up, so the
        // client stops looping.
        $nextCursor = $rows->count() === $limit && $rows->isNotEmpty()
            ? Carbon::parse($rows->last()->updated_at)->toISOString()
            : null;

        return response()->json([
            'rows' => $rows,
            'next_cursor' => $nextCursor,
        ]);
    }

    /**
     * POST /api/sync/{table}?rows
     *
     * RW tables only — pushing to a read-only cache is a client bug, answered
     * as 422 rather than silently ignored.
     */
    public function push(Request $request, string $table): JsonResponse
    {
        if (! $this->isSyncable($table)) {
            return $this->notFound("Unknown sync table [{$table}].");
        }

        if (! in_array($table, config('notebook.sync_tables.rw', []), true)) {
            return $this->validationError(
                ['table' => ["[{$table}] is pull-only and cannot be pushed."]],
                'Table is not writable',
            );
        }

        $max = (int) config('notebook.sync_push_limit', 100);

        $request->validate([
            'rows' => ['required', 'array', "max:{$max}"],
            'rows.*.id' => ['required', 'uuid'],
        ]);

        $accepted = [];
        $rejected = [];

        foreach ($request->input('rows') as $row) {
            $result = $this->applyRow($request, $table, $row);

            if ($result['ok']) {
                $accepted[] = $row['id'];
            } else {
                $rejected[] = [
                    'row' => $row,
                    'server_copy' => $result['server_copy'],
                    'reason' => $result['reason'],
                ];
            }
        }

        return response()->json([
            'accepted' => $accepted,
            'rejected' => $rejected,
        ]);
    }

    /**
     * One row, through the §2.3 gauntlet: ownership, then LWW, then upsert.
     *
     * @param  array<string, mixed>  $row
     * @return array{ok: bool, server_copy: mixed, reason: string|null}
     */
    private function applyRow(Request $request, string $table, array $row): array
    {
        $existing = $this->scopedQuery($request, $table)->find($row['id']);

        // An id that exists but is NOT the caller's must look identical to one
        // that does not exist — otherwise push doubles as an existence oracle.
        if ($existing === null && $this->existsElsewhere($table, $row['id'])) {
            return ['ok' => false, 'server_copy' => null, 'reason' => 'forbidden'];
        }

        if (! $this->parentIsOwned($request, $table, $row)) {
            return ['ok' => false, 'server_copy' => null, 'reason' => 'forbidden'];
        }

        $incomingClock = isset($row['client_updated_at'])
            ? Carbon::parse($row['client_updated_at'])
            : null;

        // §2.3 LWW. Equal clocks are NOT a conflict — a retried push of the same
        // edit must be idempotent, not a rejection.
        if ($existing !== null && $incomingClock !== null && $existing->client_updated_at !== null) {
            if ($incomingClock->lessThan($existing->client_updated_at)) {
                return ['ok' => false, 'server_copy' => $existing, 'reason' => 'stale'];
            }
        }

        try {
            $payload = $this->sanitizePayload($table, $row, $request);
        } catch (RuntimeException $e) {
            return ['ok' => false, 'server_copy' => $existing, 'reason' => 'invalid'];
        }

        DB::transaction(function () use ($table, $row, $payload, $existing) {
            $model = $existing ?? $this->newModel($table);

            $model->forceFill([...$payload, 'id' => $row['id']]);
            // The server always stamps its own updated_at — it IS the pull
            // cursor, so a device clock must never set it (§2.3).
            $model->updated_at = now();
            $model->save();

            // §2.4: a delete arrives as an ordinary update carrying deleted_at.
            $wantsDelete = ! empty($row['deleted_at']);

            if ($wantsDelete && $model->deleted_at === null) {
                $model->delete();
            } elseif (! $wantsDelete && $model->deleted_at !== null) {
                $model->restore();
            }
        });

        return ['ok' => true, 'server_copy' => null, 'reason' => null];
    }

    /**
     * Columns the client may write, per table. Anything else — user_id,
     * search_text, server timestamps — is derived or owned by the server.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function sanitizePayload(string $table, array $row, Request $request): array
    {
        $pick = fn (array $keys) => array_intersect_key($row, array_flip($keys));

        return match ($table) {
            'notebooks' => [
                ...$pick(['notebook_type_id', 'title', 'school_year', 'cover_upload_id',
                    'font_family', 'status', 'archived_at', 'position', 'client_updated_at']),
                // Ownership is assigned, never accepted (§2.3).
                'user_id' => $request->user()->id,
            ],

            'notebook_pages' => (function () use ($pick, $row) {
                $payload = $pick(['notebook_id', 'position', 'title', 'client_updated_at']);

                if (array_key_exists('content', $row)) {
                    $content = TiptapSanitizer::clean($row['content']);
                    $payload['content'] = $content;
                    // Derived server-side, exactly as in Phase 007.
                    $payload['search_text'] = TiptapText::flatten($content);
                }

                return $payload;
            })(),

            'page_attachments' => $pick([
                'page_id', 'file_upload_id', 'kind', 'local_ref', 'upload_status', 'client_updated_at',
            ]),

            default => [],
        };
    }

    /**
     * A child row must point at a parent the caller owns — otherwise a push
     * could graft a page onto someone else's notebook.
     *
     * @param  array<string, mixed>  $row
     */
    private function parentIsOwned(Request $request, string $table, array $row): bool
    {
        if ($table === 'notebook_pages') {
            $notebookId = $row['notebook_id'] ?? null;

            return is_string($notebookId)
                && Notebook::owned($request->user())->withTrashed()->whereKey($notebookId)->exists();
        }

        if ($table === 'page_attachments') {
            $pageId = $row['page_id'] ?? null;

            if (! is_string($pageId)) {
                return false;
            }

            $page = NotebookPage::withTrashed()->find($pageId);

            return $page !== null
                && Notebook::owned($request->user())->withTrashed()->whereKey($page->notebook_id)->exists();
        }

        return true;
    }

    /** Does this id belong to somebody else? Used to keep 'missing' opaque. */
    private function existsElsewhere(string $table, string $id): bool
    {
        return match ($table) {
            'notebooks' => Notebook::withTrashed()->whereKey($id)->exists(),
            'notebook_pages' => NotebookPage::withTrashed()->whereKey($id)->exists(),
            'page_attachments' => PageAttachment::withTrashed()->whereKey($id)->exists(),
            default => false,
        };
    }

    /**
     * Every pull and push query starts here: scoped to the caller and INCLUDING
     * tombstones (§2.2). Child tables scope through their parent notebook.
     */
    private function scopedQuery(Request $request, string $table): Builder
    {
        $user = $request->user();

        return match ($table) {
            'notebooks' => Notebook::withTrashed()->where('user_id', $user->id),

            'notebook_pages' => NotebookPage::withTrashed()->whereIn(
                'notebook_id',
                Notebook::withTrashed()->where('user_id', $user->id)->select('id'),
            ),

            'page_attachments' => PageAttachment::withTrashed()->whereIn(
                'page_id',
                NotebookPage::withTrashed()->whereIn(
                    'notebook_id',
                    Notebook::withTrashed()->where('user_id', $user->id)->select('id'),
                )->select('id'),
            ),

            // RO caches.
            'notebook_types' => NotebookType::query(),
            'users' => User::withTrashed()->whereKey($user->id),
        };
    }

    private function newModel(string $table): Notebook|NotebookPage|PageAttachment
    {
        return match ($table) {
            'notebooks' => new Notebook(),
            'notebook_pages' => new NotebookPage(),
            'page_attachments' => new PageAttachment(),
        };
    }

    private function isSyncable(string $table): bool
    {
        $tables = config('notebook.sync_tables', []);

        return in_array($table, [...$tables['rw'] ?? [], ...$tables['ro'] ?? []], true);
    }
}
