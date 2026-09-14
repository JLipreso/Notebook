<?php

namespace Tests\Feature;

use App\Models\Notebook;
use App\Models\NotebookPage;
use App\Models\NotebookType;
use App\Models\PageAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The sync contract (2026-09-13-005 Phase 009, database-schema.md §2).
 *
 * Each clause of §2 gets its own test, because this is the one place the plan
 * demands real coverage: a silent bug here loses a student's homework.
 */
class SyncTest extends TestCase
{
    use RefreshDatabase;

    private function notebookRow(User $user, array $overrides = []): array
    {
        return array_merge([
            'id' => (string) Str::uuid7(),
            'notebook_type_id' => NotebookType::factory()->create()->id,
            'title' => 'Math 7',
            'school_year' => '2026-2027',
            'status' => 'active',
            'position' => 0,
            'client_updated_at' => now()->toISOString(),
        ], $overrides);
    }

    // ------------------------------------------------------------------- gate

    public function test_sync_requires_auth(): void
    {
        $this->getJson('/api/sync/notebooks')->assertUnauthorized();
        $this->postJson('/api/sync/notebooks', ['rows' => []])->assertUnauthorized();
    }

    /** {table} is a fixed map, never a free string into a query. */
    public function test_unknown_table_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/sync/users_secret')->assertNotFound();
        $this->actingAs($user)->getJson('/api/sync/password_reset_tokens')->assertNotFound();
    }

    public function test_read_only_tables_cannot_be_pushed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/sync/notebook_types', ['rows' => [['id' => (string) Str::uuid7()]]])
            ->assertStatus(422);

        $this->actingAs($user)->getJson('/api/sync/notebook_types')->assertOk();
    }

    // ------------------------------------------------------------------- pull

    public function test_pull_excludes_other_users_rows(): void
    {
        $mine = User::factory()->create();
        $theirs = User::factory()->create();

        Notebook::factory()->create(['user_id' => $mine->id]);
        Notebook::factory()->count(3)->create(['user_id' => $theirs->id]);

        $rows = $this->actingAs($mine)->getJson('/api/sync/notebooks')->assertOk()->json('rows');

        $this->assertCount(1, $rows);
    }

    /**
     * §2.2 — tombstones MUST come down. Without them a delete never propagates
     * and the row reappears on the next device that pulls.
     */
    public function test_pull_includes_tombstones(): void
    {
        $user = User::factory()->create();
        $notebook = Notebook::factory()->create(['user_id' => $user->id]);
        $notebook->delete();

        $rows = $this->actingAs($user)->getJson('/api/sync/notebooks')->json('rows');

        $this->assertCount(1, $rows);
        $this->assertNotNull($rows[0]['deleted_at']);
    }

    public function test_pull_filters_by_the_since_cursor(): void
    {
        $user = User::factory()->create();

        $old = Notebook::factory()->create(['user_id' => $user->id]);
        $old->forceFill(['updated_at' => now()->subDay()])->save();

        $cursor = now()->subHours(2)->toISOString();

        $fresh = Notebook::factory()->create(['user_id' => $user->id]);

        $rows = $this->actingAs($user)->getJson("/api/sync/notebooks?since={$cursor}")->json('rows');

        $this->assertCount(1, $rows);
        $this->assertSame($fresh->id, $rows[0]['id']);
    }

    /** The cursor is null once caught up, so the client's loop terminates. */
    public function test_pull_cursor_is_null_when_the_page_is_not_full(): void
    {
        $user = User::factory()->create();
        Notebook::factory()->count(2)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/api/sync/notebooks?limit=10')->json();

        $this->assertCount(2, $response['rows']);
        $this->assertNull($response['next_cursor']);
    }

    /** A full page returns a cursor; following it reaches the rest exactly once. */
    public function test_pull_pages_through_a_full_result_set(): void
    {
        $user = User::factory()->create();

        foreach (range(0, 4) as $i) {
            $notebook = Notebook::factory()->create(['user_id' => $user->id]);
            $notebook->forceFill(['updated_at' => now()->addSeconds($i)])->save();
        }

        $first = $this->actingAs($user)->getJson('/api/sync/notebooks?limit=2')->json();

        $this->assertCount(2, $first['rows']);
        $this->assertNotNull($first['next_cursor']);

        $second = $this->actingAs($user)
            ->getJson('/api/sync/notebooks?limit=2&since='.urlencode($first['next_cursor']))
            ->json();

        $this->assertCount(2, $second['rows']);

        // No overlap between pages.
        $firstIds = array_column($first['rows'], 'id');
        $secondIds = array_column($second['rows'], 'id');
        $this->assertEmpty(array_intersect($firstIds, $secondIds));
    }

    public function test_pull_child_tables_are_scoped_through_the_notebook(): void
    {
        $mine = User::factory()->create();
        $theirs = User::factory()->create();

        $myNotebook = Notebook::factory()->create(['user_id' => $mine->id]);
        NotebookPage::factory()->create(['notebook_id' => $myNotebook->id]);

        $theirNotebook = Notebook::factory()->create(['user_id' => $theirs->id]);
        NotebookPage::factory()->count(2)->create(['notebook_id' => $theirNotebook->id]);

        $rows = $this->actingAs($mine)->getJson('/api/sync/notebook_pages')->json('rows');

        $this->assertCount(1, $rows);
    }

    // ------------------------------------------------------------------- push

    public function test_push_creates_a_row_with_the_client_minted_id(): void
    {
        $user = User::factory()->create();
        $row = $this->notebookRow($user);

        $response = $this->actingAs($user)
            ->postJson('/api/sync/notebooks', ['rows' => [$row]])
            ->assertOk()
            ->json();

        $this->assertSame([$row['id']], $response['accepted']);
        $this->assertEmpty($response['rejected']);
        $this->assertDatabaseHas('notebooks', ['id' => $row['id'], 'user_id' => $user->id]);
    }

    /** §2.3 — ownership is ASSIGNED, never accepted from the row. */
    public function test_push_ignores_a_client_supplied_user_id(): void
    {
        $user = User::factory()->create();
        $victim = User::factory()->create();
        $row = $this->notebookRow($user, ['user_id' => $victim->id]);

        $this->actingAs($user)->postJson('/api/sync/notebooks', ['rows' => [$row]])->assertOk();

        $this->assertDatabaseHas('notebooks', ['id' => $row['id'], 'user_id' => $user->id]);
    }

    /** Pushing at someone else's existing row must not touch it. */
    public function test_push_cannot_overwrite_another_users_row(): void
    {
        $mine = User::factory()->create();
        $theirs = User::factory()->create();
        $notebook = Notebook::factory()->create(['user_id' => $theirs->id, 'title' => 'Theirs']);

        $response = $this->actingAs($mine)->postJson('/api/sync/notebooks', [
            'rows' => [$this->notebookRow($mine, ['id' => $notebook->id, 'title' => 'Stolen'])],
        ])->assertOk()->json();

        $this->assertEmpty($response['accepted']);
        $this->assertSame('forbidden', $response['rejected'][0]['reason']);
        $this->assertSame('Theirs', $notebook->fresh()->title);
    }

    /**
     * §2.3 LWW — an incoming row older than the stored one is rejected AND the
     * winning server copy comes back, so the loser can converge.
     */
    public function test_push_rejects_a_stale_row_and_returns_the_server_copy(): void
    {
        $user = User::factory()->create();

        $notebook = Notebook::factory()->create([
            'user_id' => $user->id,
            'title' => 'Newer on server',
            'client_updated_at' => now(),
        ]);

        $stale = $this->notebookRow($user, [
            'id' => $notebook->id,
            'title' => 'Older from device',
            'client_updated_at' => now()->subHour()->toISOString(),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/sync/notebooks', ['rows' => [$stale]])
            ->assertOk()
            ->json();

        $this->assertEmpty($response['accepted']);
        $this->assertSame('stale', $response['rejected'][0]['reason']);
        $this->assertSame('Newer on server', $response['rejected'][0]['server_copy']['title']);
        $this->assertSame('Newer on server', $notebook->fresh()->title);
    }

    public function test_push_accepts_a_newer_row(): void
    {
        $user = User::factory()->create();

        $notebook = Notebook::factory()->create([
            'user_id' => $user->id,
            'title' => 'Older on server',
            'client_updated_at' => now()->subHour(),
        ]);

        $this->actingAs($user)->postJson('/api/sync/notebooks', [
            'rows' => [$this->notebookRow($user, [
                'id' => $notebook->id,
                'title' => 'Newer from device',
                'client_updated_at' => now()->toISOString(),
            ])],
        ])->assertOk();

        $this->assertSame('Newer from device', $notebook->fresh()->title);
    }

    /** A retried push of the SAME edit must be idempotent, not a conflict. */
    public function test_push_with_an_equal_clock_is_idempotent(): void
    {
        $user = User::factory()->create();
        $clock = now()->toISOString();
        $row = $this->notebookRow($user, ['client_updated_at' => $clock]);

        $this->actingAs($user)->postJson('/api/sync/notebooks', ['rows' => [$row]])->assertOk();

        $second = $this->actingAs($user)
            ->postJson('/api/sync/notebooks', ['rows' => [$row]])
            ->assertOk()
            ->json();

        $this->assertSame([$row['id']], $second['accepted']);
        $this->assertDatabaseCount('notebooks', 1);
    }

    /** §2.4 — a delete travels as an ordinary update carrying deleted_at. */
    public function test_push_propagates_a_soft_delete(): void
    {
        $user = User::factory()->create();
        $notebook = Notebook::factory()->create([
            'user_id' => $user->id,
            'client_updated_at' => now()->subHour(),
        ]);

        $this->actingAs($user)->postJson('/api/sync/notebooks', [
            'rows' => [$this->notebookRow($user, [
                'id' => $notebook->id,
                'client_updated_at' => now()->toISOString(),
                'deleted_at' => now()->toISOString(),
            ])],
        ])->assertOk();

        $this->assertSoftDeleted('notebooks', ['id' => $notebook->id]);
    }

    public function test_push_can_restore_a_tombstoned_row(): void
    {
        $user = User::factory()->create();
        $notebook = Notebook::factory()->create([
            'user_id' => $user->id,
            'client_updated_at' => now()->subHour(),
        ]);
        $notebook->delete();

        $this->actingAs($user)->postJson('/api/sync/notebooks', [
            'rows' => [$this->notebookRow($user, [
                'id' => $notebook->id,
                'client_updated_at' => now()->toISOString(),
                'deleted_at' => null,
            ])],
        ])->assertOk();

        $this->assertNotSoftDeleted('notebooks', ['id' => $notebook->id]);
    }

    /** The server always stamps updated_at — it IS the pull cursor. */
    public function test_push_ignores_a_client_supplied_updated_at(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/sync/notebooks', [
            'rows' => [$this->notebookRow($user, ['updated_at' => '2000-01-01T00:00:00Z'])],
        ])->assertOk();

        $this->assertTrue(
            Carbon::parse(Notebook::first()->updated_at)->isAfter(now()->subMinute()),
            'updated_at must be server-stamped, never taken from the device.',
        );
    }

    public function test_push_sanitizes_page_content_and_derives_search_text(): void
    {
        $user = User::factory()->create();
        $notebook = Notebook::factory()->create(['user_id' => $user->id]);
        $id = (string) Str::uuid7();

        $this->actingAs($user)->postJson('/api/sync/notebook_pages', [
            'rows' => [[
                'id' => $id,
                'notebook_id' => $notebook->id,
                'position' => 0,
                'client_updated_at' => now()->toISOString(),
                'content' => ['type' => 'doc', 'content' => [
                    ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Safe text']]],
                    ['type' => 'script', 'content' => [['type' => 'text', 'text' => 'EVIL']]],
                ]],
                // Derived server-side; a client value must be ignored.
                'search_text' => 'INJECTED',
            ]],
        ])->assertOk();

        $page = NotebookPage::find($id);

        $this->assertCount(1, $page->content['content']);
        $this->assertSame('paragraph', $page->content['content'][0]['type']);
        $this->assertSame('Safe text', $page->search_text);
    }

    /** A page may not be grafted onto a notebook the caller does not own. */
    public function test_push_rejects_a_child_row_under_a_foreign_parent(): void
    {
        $mine = User::factory()->create();
        $theirs = User::factory()->create();
        $theirNotebook = Notebook::factory()->create(['user_id' => $theirs->id]);

        $response = $this->actingAs($mine)->postJson('/api/sync/notebook_pages', [
            'rows' => [[
                'id' => (string) Str::uuid7(),
                'notebook_id' => $theirNotebook->id,
                'position' => 0,
                'client_updated_at' => now()->toISOString(),
            ]],
        ])->assertOk()->json();

        $this->assertEmpty($response['accepted']);
        $this->assertSame('forbidden', $response['rejected'][0]['reason']);
        $this->assertDatabaseCount('notebook_pages', 0);
    }

    public function test_push_batch_is_capped(): void
    {
        $user = User::factory()->create();
        $rows = array_map(fn () => $this->notebookRow($user), range(1, 101));

        $this->actingAs($user)
            ->postJson('/api/sync/notebooks', ['rows' => $rows])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rows']);
    }

    /** A mixed batch reports per row — one bad row must not sink the good ones. */
    public function test_push_reports_per_row_not_per_batch(): void
    {
        $user = User::factory()->create();
        $theirs = User::factory()->create();
        $foreign = Notebook::factory()->create(['user_id' => $theirs->id]);

        $good = $this->notebookRow($user);
        $bad = $this->notebookRow($user, ['id' => $foreign->id]);

        $response = $this->actingAs($user)
            ->postJson('/api/sync/notebooks', ['rows' => [$good, $bad]])
            ->assertOk()
            ->json();

        $this->assertSame([$good['id']], $response['accepted']);
        $this->assertCount(1, $response['rejected']);
    }

    public function test_attachment_rows_sync_with_their_pending_status(): void
    {
        $user = User::factory()->create();
        $notebook = Notebook::factory()->create(['user_id' => $user->id]);
        $page = NotebookPage::factory()->create(['notebook_id' => $notebook->id]);
        $id = (string) Str::uuid7();

        $this->actingAs($user)->postJson('/api/sync/page_attachments', [
            'rows' => [[
                'id' => $id,
                'page_id' => $page->id,
                'kind' => 'image',
                'local_ref' => 'file:///device/photo.jpg',
                'upload_status' => 'pending',
                'client_updated_at' => now()->toISOString(),
            ]],
        ])->assertOk();

        $this->assertDatabaseHas('page_attachments', [
            'id' => $id,
            'upload_status' => 'pending',
            'local_ref' => 'file:///device/photo.jpg',
        ]);
    }

    public function test_users_pull_returns_only_the_caller(): void
    {
        $user = User::factory()->create();
        User::factory()->count(3)->create();

        $rows = $this->actingAs($user)->getJson('/api/sync/users')->assertOk()->json('rows');

        $this->assertCount(1, $rows);
        $this->assertSame($user->id, $rows[0]['id']);
    }
}
