<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Notebook;
use App\Models\NotebookPage;
use App\Models\NotebookShare;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Share links and notifications (2026-09-13-005 Phase 010).
 *
 * The public resolve endpoint gets the most attention: it is the only
 * unauthenticated route in the app that returns user content.
 */
class ShareNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function notebookWithPages(User $user, int $pages = 3): Notebook
    {
        $notebook = Notebook::factory()->create(['user_id' => $user->id]);

        foreach (range(0, $pages - 1) as $i) {
            NotebookPage::factory()->create([
                'notebook_id' => $notebook->id,
                'position' => $i,
                'title' => "Page {$i}",
            ]);
        }

        return $notebook;
    }

    // ----------------------------------------------------------------- create

    public function test_share_creation_requires_auth(): void
    {
        $this->postJson('/api/shares', [])->assertUnauthorized();
    }

    public function test_owner_can_create_a_read_only_link(): void
    {
        $user = User::factory()->create();
        $notebook = $this->notebookWithPages($user);

        $response = $this->actingAs($user)
            ->postJson('/api/shares', ['notebook_id' => $notebook->id])
            ->assertCreated()
            ->json('data');

        $this->assertSame('read', $response['access']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $response['share_token']);
        $this->assertTrue($response['is_active']);
    }

    public function test_cannot_share_another_users_notebook(): void
    {
        $mine = User::factory()->create();
        $theirs = User::factory()->create();
        $notebook = Notebook::factory()->create(['user_id' => $theirs->id]);

        $this->actingAs($mine)
            ->postJson('/api/shares', ['notebook_id' => $notebook->id])
            ->assertNotFound();

        $this->assertDatabaseCount('notebook_shares', 0);
    }

    /** A page-scoped link must name a page IN that notebook. */
    public function test_page_must_belong_to_the_shared_notebook(): void
    {
        $user = User::factory()->create();
        $notebook = $this->notebookWithPages($user, 1);
        $other = $this->notebookWithPages($user, 1);
        $foreignPage = NotebookPage::where('notebook_id', $other->id)->first();

        $this->actingAs($user)
            ->postJson('/api/shares', [
                'notebook_id' => $notebook->id,
                'page_id' => $foreignPage->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['page_id']);
    }

    /** M1 is read-only; a read_write link would mint something unhonourable. */
    public function test_read_write_access_is_rejected(): void
    {
        $user = User::factory()->create();
        $notebook = $this->notebookWithPages($user, 1);

        $this->actingAs($user)
            ->postJson('/api/shares', ['notebook_id' => $notebook->id, 'access' => 'read_write'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['access']);
    }

    public function test_tokens_are_unique_per_share(): void
    {
        $user = User::factory()->create();
        $notebook = $this->notebookWithPages($user, 1);

        $first = $this->actingAs($user)->postJson('/api/shares', ['notebook_id' => $notebook->id])
            ->json('data.share_token');
        $second = $this->actingAs($user)->postJson('/api/shares', ['notebook_id' => $notebook->id])
            ->json('data.share_token');

        $this->assertNotSame($first, $second);
    }

    // ---------------------------------------------------------------- resolve

    public function test_a_valid_link_resolves_signed_out(): void
    {
        $user = User::factory()->create();
        $notebook = $this->notebookWithPages($user, 3);
        $token = $this->actingAs($user)
            ->postJson('/api/shares', ['notebook_id' => $notebook->id])
            ->json('data.share_token');

        // No actingAs — this is the signed-out path.
        $data = $this->getJson("/api/shared/{$token}")->assertOk()->json('data');

        $this->assertSame($notebook->title, $data['notebook']['title']);
        $this->assertCount(3, $data['pages']);
        $this->assertSame('read', $data['access']);
        $this->assertNotNull($data['notebook']['notebook_type']['page_template']);
    }

    /** The public shape must not leak more than it renders. */
    public function test_the_public_shape_omits_owner_and_internal_fields(): void
    {
        $user = User::factory()->create();
        $notebook = $this->notebookWithPages($user, 1);
        $token = $this->actingAs($user)
            ->postJson('/api/shares', ['notebook_id' => $notebook->id])
            ->json('data.share_token');

        $data = $this->getJson("/api/shared/{$token}")->json('data');

        $this->assertArrayNotHasKey('user_id', $data['notebook']);
        $this->assertArrayNotHasKey('search_text', $data['pages'][0]);
        $this->assertArrayNotHasKey('client_updated_at', $data['pages'][0]);
    }

    public function test_a_page_scoped_link_shows_exactly_that_page(): void
    {
        $user = User::factory()->create();
        $notebook = $this->notebookWithPages($user, 4);
        $page = NotebookPage::where('notebook_id', $notebook->id)->where('position', 2)->first();

        $token = $this->actingAs($user)
            ->postJson('/api/shares', ['notebook_id' => $notebook->id, 'page_id' => $page->id])
            ->json('data.share_token');

        $data = $this->getJson("/api/shared/{$token}")->json('data');

        $this->assertCount(1, $data['pages']);
        $this->assertSame($page->id, $data['pages'][0]['id']);
    }

    public function test_a_revoked_link_is_dead(): void
    {
        $user = User::factory()->create();
        $notebook = $this->notebookWithPages($user, 1);
        $share = $this->actingAs($user)
            ->postJson('/api/shares', ['notebook_id' => $notebook->id])
            ->json('data');

        $this->getJson("/api/shared/{$share['share_token']}")->assertOk();

        $this->actingAs($user)->postJson("/api/shares/{$share['id']}/revoke")->assertOk();

        $this->getJson("/api/shared/{$share['share_token']}")
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_an_expired_link_is_dead(): void
    {
        $user = User::factory()->create();
        $notebook = $this->notebookWithPages($user, 1);

        $share = NotebookShare::factory()->create([
            'notebook_id' => $notebook->id,
            'shared_by' => $user->id,
            'expires_at' => now()->subMinute(),
        ]);

        $this->getJson("/api/shared/{$share->share_token}")->assertNotFound();
    }

    public function test_an_unknown_token_is_the_same_404(): void
    {
        // Identical response to revoked/expired — the endpoint must not confirm
        // that a token ever existed.
        $this->getJson('/api/shared/'.str_repeat('a', 64))
            ->assertNotFound()
            ->assertJsonPath('message', 'This link is no longer available.');
    }

    public function test_a_malformed_token_does_not_match_the_route(): void
    {
        $this->getJson('/api/shared/short')->assertNotFound();
        $this->getJson('/api/shared/'.str_repeat('Z', 64))->assertNotFound();
    }

    public function test_deleting_the_notebook_kills_the_link(): void
    {
        $user = User::factory()->create();
        $notebook = $this->notebookWithPages($user, 1);
        $token = $this->actingAs($user)
            ->postJson('/api/shares', ['notebook_id' => $notebook->id])
            ->json('data.share_token');

        $notebook->delete();

        $this->getJson("/api/shared/{$token}")->assertNotFound();
    }

    // ----------------------------------------------------------------- manage

    public function test_list_shows_only_own_shares_with_status_flags(): void
    {
        $mine = User::factory()->create();
        $theirs = User::factory()->create();

        $myNotebook = $this->notebookWithPages($mine, 1);
        $this->actingAs($mine)->postJson('/api/shares', ['notebook_id' => $myNotebook->id]);

        $theirNotebook = Notebook::factory()->create(['user_id' => $theirs->id]);
        NotebookShare::factory()->create([
            'notebook_id' => $theirNotebook->id,
            'shared_by' => $theirs->id,
        ]);

        $shares = $this->actingAs($mine)->getJson('/api/shares')->assertOk()->json('data');

        $this->assertCount(1, $shares);
        $this->assertTrue($shares[0]['is_active']);
        $this->assertFalse($shares[0]['is_revoked']);
    }

    public function test_cannot_revoke_another_users_share(): void
    {
        $mine = User::factory()->create();
        $theirs = User::factory()->create();
        $notebook = Notebook::factory()->create(['user_id' => $theirs->id]);
        $share = NotebookShare::factory()->create([
            'notebook_id' => $notebook->id,
            'shared_by' => $theirs->id,
        ]);

        $this->actingAs($mine)->postJson("/api/shares/{$share->id}/revoke")->assertNotFound();

        $this->assertNull($share->fresh()->revoked_at);
    }

    public function test_revoking_twice_is_idempotent(): void
    {
        $user = User::factory()->create();
        $notebook = $this->notebookWithPages($user, 1);
        $share = $this->actingAs($user)
            ->postJson('/api/shares', ['notebook_id' => $notebook->id])
            ->json('data');

        $first = $this->actingAs($user)->postJson("/api/shares/{$share['id']}/revoke")
            ->json('data.revoked_at');
        $second = $this->actingAs($user)->postJson("/api/shares/{$share['id']}/revoke")
            ->json('data.revoked_at');

        $this->assertSame($first, $second);
    }

    // ---------------------------------------------------------- notifications

    /** Notify is the ONE way a notification is created (M2/M3 reuse it). */
    public function test_notify_send_creates_a_row(): void
    {
        $user = User::factory()->create();

        $notification = Notify::send($user->id, 'share_received', 'A notebook was shared', 'body', [
            'route' => 'notebook',
            'params' => ['id' => 'abc'],
        ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'user_id' => $user->id,
            'type' => 'share_received',
        ]);
        $this->assertSame(['route' => 'notebook', 'params' => ['id' => 'abc']], $notification->data);
    }

    public function test_notify_send_many_writes_one_row_per_recipient(): void
    {
        $users = User::factory()->count(3)->create();

        // Duplicates collapse — a person gets one row, not two.
        $ids = [...$users->pluck('id')->all(), $users->first()->id];

        Notify::sendMany($ids, 'invite_received', 'You were invited');

        $this->assertDatabaseCount('notifications', 3);
    }

    public function test_notifications_list_is_paginated_and_own_only(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        foreach (range(1, 3) as $i) Notify::send($user->id, 'welcome', "Mine {$i}");
        Notify::send($other->id, 'welcome', 'Theirs');

        $response = $this->actingAs($user)->getJson('/api/notifications')->assertOk()->json();

        $this->assertCount(3, $response['data']);
        $this->assertSame(3, $response['meta']['total']);
    }

    public function test_unread_notifications_sort_first(): void
    {
        $user = User::factory()->create();

        $read = Notify::send($user->id, 'welcome', 'Already read');
        $read->forceFill(['read_at' => now(), 'created_at' => now()->addMinute()])->save();

        Notify::send($user->id, 'welcome', 'Still unread');

        $rows = $this->actingAs($user)->getJson('/api/notifications')->json('data');

        $this->assertSame('Still unread', $rows[0]['title']);
    }

    public function test_mark_read_and_unread_count(): void
    {
        $user = User::factory()->create();
        $notification = Notify::send($user->id, 'welcome', 'Hello');

        $this->actingAs($user)->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data', 1);

        $this->actingAs($user)->postJson("/api/notifications/{$notification->id}/read")->assertOk();

        $this->assertNotNull($notification->fresh()->read_at);
        $this->actingAs($user)->getJson('/api/notifications/unread-count')->assertJsonPath('data', 0);
    }

    public function test_marking_read_twice_does_not_move_the_timestamp(): void
    {
        $user = User::factory()->create();
        $notification = Notify::send($user->id, 'welcome', 'Hello');

        $this->actingAs($user)->postJson("/api/notifications/{$notification->id}/read");
        $first = $notification->fresh()->read_at;

        $this->actingAs($user)->postJson("/api/notifications/{$notification->id}/read");

        $this->assertEquals($first, $notification->fresh()->read_at);
    }

    public function test_cannot_read_another_users_notification(): void
    {
        $mine = User::factory()->create();
        $theirs = User::factory()->create();
        $notification = Notify::send($theirs->id, 'welcome', 'Theirs');

        $this->actingAs($mine)->postJson("/api/notifications/{$notification->id}/read")->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_mark_all_read_affects_only_the_caller(): void
    {
        $mine = User::factory()->create();
        $theirs = User::factory()->create();

        Notify::send($mine->id, 'welcome', 'Mine');
        $their = Notify::send($theirs->id, 'welcome', 'Theirs');

        $this->actingAs($mine)->postJson('/api/notifications/read-all')->assertOk();

        $this->assertSame(0, AppNotification::where('user_id', $mine->id)->whereNull('read_at')->count());
        $this->assertNull($their->fresh()->read_at);
    }
}
