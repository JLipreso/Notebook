<?php

namespace App\Http\Controllers\Api;

use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * In-app notifications (2026-09-13-005 Phase 010).
 *
 * ONE table for every role and event type (schema §5, improvement #3). Rows are
 * created through App\Support\Notify — never inserted from a controller — so
 * M2's invitations and M3's payment confirmations add a call site, not a second
 * pathway.
 *
 * Only `welcome` fires in M1, seeded at registration.
 */
class NotificationController extends ApiController
{
    private const COLUMNS = [
        'id', 'user_id', 'actor_user_id', 'type', 'title', 'body', 'data',
        'read_at', 'created_at', 'updated_at',
    ];

    /**
     * GET /api/notifications?page
     *
     * Paginated envelope — unread first, then newest.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AppNotification::query()
            ->select(self::COLUMNS)
            ->where('user_id', $request->user()->id)
            // Unread float to the top; the index is (user_id, read_at, created_at).
            ->orderByRaw('read_at is null desc')
            ->orderByDesc('created_at');

        return $this->paginated($query, 20);
    }

    /**
     * GET /api/notifications/unread-count
     *
     * Literal route — registered before {id}.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = AppNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return $this->success($count);
    }

    /**
     * POST /api/notifications/{id}/read
     */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = AppNotification::where('user_id', $request->user()->id)->find($id);

        if ($notification === null) {
            return $this->notFound('Notification not found.');
        }

        // Idempotent: re-reading must not move the timestamp.
        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        return $this->success(null, 'Marked read');
    }

    /**
     * POST /api/notifications/read-all
     */
    public function markAllRead(Request $request): JsonResponse
    {
        AppNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);

        return $this->success(null, 'All marked read');
    }
}
