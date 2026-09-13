<?php

namespace Tests\Feature;

use App\Models\Notebook;
use App\Models\NotebookType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The notebook shelf (2026-09-13-005 Phase 006).
 *
 * The load-bearing assertions are ownership isolation (cross-user access is a
 * 404, never a 403) and that a CLIENT-MINTED id survives verbatim — Phase 009's
 * offline sync depends on both.
 */
class NotebookTest extends TestCase
{
    use RefreshDatabase;

    private function payload(NotebookType $type, array $overrides = []): array
    {
        return array_merge([
            'id' => (string) Str::uuid7(),
            'notebook_type_id' => $type->id,
            'title' => 'Math 7',
            'school_year' => '2026-2027',
        ], $overrides);
    }

    public function test_every_endpoint_requires_auth(): void
    {
        $this->getJson('/api/notebooks')->assertUnauthorized();
        $this->postJson('/api/notebooks', [])->assertUnauthorized();
        $this->getJson('/api/notebook-types')->assertUnauthorized();
    }

    public function test_types_returns_only_active_sorted(): void
    {
        $user = User::factory()->create();
        NotebookType::factory()->create(['sort_order' => 2, 'key' => 'second']);
        NotebookType::factory()->create(['sort_order' => 1, 'key' => 'first']);
        NotebookType::factory()->create(['sort_order' => 0, 'key' => 'hidden', 'is_active' => false]);

        $response = $this->actingAs($user)->getJson('/api/notebook-types')->assertOk();

        $this->assertCount(2, $response->json('data'));
        $this->assertSame('first', $response->json('data.0.key'));
    }

    public function test_client_minted_id_is_stored_verbatim(): void
    {
        $user = User::factory()->create();
        $type = NotebookType::factory()->create();
        $id = (string) Str::uuid7();

        $this->actingAs($user)
            ->postJson('/api/notebooks', $this->payload($type, ['id' => $id]))
            ->assertCreated()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.user_id', $user->id);

        $this->assertDatabaseHas('notebooks', ['id' => $id, 'user_id' => $user->id]);
    }

    public function test_duplicate_id_is_rejected(): void
    {
        $user = User::factory()->create();
        $type = NotebookType::factory()->create();
        $payload = $this->payload($type);

        $this->actingAs($user)->postJson('/api/notebooks', $payload)->assertCreated();
        $this->actingAs($user)->postJson('/api/notebooks', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['id']);
    }

    public function test_user_id_cannot_be_set_from_the_request(): void
    {
        $user = User::factory()->create();
        $victim = User::factory()->create();
        $type = NotebookType::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/notebooks', $this->payload($type, ['user_id' => $victim->id]))
            ->assertCreated()
            ->assertJsonPath('data.user_id', $user->id);
    }

    public function test_school_year_format_is_enforced(): void
    {
        $user = User::factory()->create();
        $type = NotebookType::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/notebooks', $this->payload($type, ['school_year' => '2026']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['school_year']);
    }

    public function test_index_lists_only_own_notebooks(): void
    {
        $mine = User::factory()->create();
        $theirs = User::factory()->create();
        $type = NotebookType::factory()->create();

        Notebook::factory()->create(['user_id' => $mine->id, 'notebook_type_id' => $type->id]);
        Notebook::factory()->count(2)->create(['user_id' => $theirs->id, 'notebook_type_id' => $type->id]);

        $response = $this->actingAs($mine)->getJson('/api/notebooks')->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    public function test_index_filters_by_status_and_school_year(): void
    {
        $user = User::factory()->create();
        $type = NotebookType::factory()->create();

        Notebook::factory()->create(['user_id' => $user->id, 'notebook_type_id' => $type->id, 'school_year' => '2026-2027']);
        Notebook::factory()->archived()->create(['user_id' => $user->id, 'notebook_type_id' => $type->id, 'school_year' => '2025-2026']);

        $this->assertCount(1, $this->actingAs($user)->getJson('/api/notebooks?status=active')->json('data'));
        $this->assertCount(1, $this->actingAs($user)->getJson('/api/notebooks?status=archived')->json('data'));
        $this->assertCount(1, $this->actingAs($user)->getJson('/api/notebooks?school_year=2025-2026')->json('data'));
    }

    /**
     * 404, never 403: a 403 would confirm the id exists and belongs to someone.
     */
    public function test_cross_user_access_is_a_404_everywhere(): void
    {
        $mine = User::factory()->create();
        $theirs = User::factory()->create();
        $notebook = Notebook::factory()->create(['user_id' => $theirs->id]);

        $this->actingAs($mine)->getJson("/api/notebooks/{$notebook->id}")->assertNotFound();
        $this->actingAs($mine)->putJson("/api/notebooks/{$notebook->id}", ['title' => 'Stolen'])->assertNotFound();
        $this->actingAs($mine)->postJson("/api/notebooks/{$notebook->id}/archive")->assertNotFound();
        $this->actingAs($mine)->deleteJson("/api/notebooks/{$notebook->id}")->assertNotFound();

        // Untouched.
        $this->assertSame($notebook->title, $notebook->fresh()->title);
    }

    public function test_update_changes_only_allowed_fields(): void
    {
        $user = User::factory()->create();
        $notebook = Notebook::factory()->create(['user_id' => $user->id, 'title' => 'Old']);

        $this->actingAs($user)
            ->putJson("/api/notebooks/{$notebook->id}", [
                'title' => 'New',
                // Neither of these is fillable through this endpoint.
                'user_id' => User::factory()->create()->id,
                'status' => 'archived',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'New');

        $notebook->refresh();
        $this->assertSame($user->id, $notebook->user_id);
        $this->assertSame('active', $notebook->status);
    }

    public function test_archive_round_trip(): void
    {
        $user = User::factory()->create();
        $notebook = Notebook::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->postJson("/api/notebooks/{$notebook->id}/archive")
            ->assertOk()
            ->assertJsonPath('data.status', 'archived');

        $this->assertNotNull($notebook->fresh()->archived_at);

        $this->actingAs($user)->postJson("/api/notebooks/{$notebook->id}/unarchive")
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->assertNull($notebook->fresh()->archived_at);
    }

    /**
     * Sync propagates deletes as tombstones (schema §2.4) — a hard delete would
     * let an offline device resurrect the row on its next push.
     */
    public function test_delete_is_soft_and_leaves_a_tombstone(): void
    {
        $user = User::factory()->create();
        $notebook = Notebook::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->deleteJson("/api/notebooks/{$notebook->id}")->assertOk();

        $this->assertSoftDeleted('notebooks', ['id' => $notebook->id]);
        $this->assertDatabaseCount('notebooks', 1);

        // And it leaves the shelf.
        $this->assertCount(0, $this->actingAs($user)->getJson('/api/notebooks')->json('data'));
    }

    public function test_non_uuid_id_does_not_match_the_route(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/notebooks/not-a-uuid')->assertNotFound();
    }

    public function test_position_defaults_to_the_end_of_the_shelf(): void
    {
        $user = User::factory()->create();
        $type = NotebookType::factory()->create();

        Notebook::factory()->count(2)->create(['user_id' => $user->id, 'notebook_type_id' => $type->id]);

        $this->actingAs($user)
            ->postJson('/api/notebooks', $this->payload($type))
            ->assertCreated()
            ->assertJsonPath('data.position', 2);
    }
}
