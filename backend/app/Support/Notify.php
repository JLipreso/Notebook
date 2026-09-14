<?php

namespace App\Support;

use App\Models\AppNotification;

/**
 * The one way a notification gets created (2026-09-13-005 Phase 010).
 *
 * Every later feature calls this — M2's course invitations and quiz
 * submissions, M3's payment verifications. Centralising it now means those
 * phases add a `type` constant and a call site, not a second notification
 * pathway that drifts from this one.
 *
 * Multi-recipient is one row EACH, deliberately: that is what makes the table
 * the brief's "seen log" (schema §5, improvement #3).
 */
class Notify
{
    /**
     * @param  array<string, mixed>|null  $data  deep-link payload: { route, params }
     */
    public static function send(
        string $userId,
        string $type,
        string $title,
        ?string $body = null,
        ?array $data = null,
        ?string $actorUserId = null,
    ): AppNotification {
        $notification = new AppNotification();

        $notification->forceFill([
            'user_id' => $userId,
            'actor_user_id' => $actorUserId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'read_at' => null,
        ])->save();

        return $notification;
    }

    /**
     * @param  list<string>  $userIds
     * @param  array<string, mixed>|null  $data
     * @return list<AppNotification>
     */
    public static function sendMany(
        array $userIds,
        string $type,
        string $title,
        ?string $body = null,
        ?array $data = null,
        ?string $actorUserId = null,
    ): array {
        return array_map(
            fn (string $userId) => self::send($userId, $type, $title, $body, $data, $actorUserId),
            array_values(array_unique($userIds)),
        );
    }

    /** The one notification M1 actually fires — seeded at registration. */
    public static function welcome(string $userId): AppNotification
    {
        return self::send(
            $userId,
            'welcome',
            'Welcome to Notebook',
            'Your notebooks are yours forever. Start by creating your first one.',
            ['route' => 'library'],
        );
    }
}
