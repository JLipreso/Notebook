<?php

namespace Tests\Feature;

use App\Models\Notebook;
use App\Models\NotebookPage;
use App\Models\NotebookShare;
use App\Models\NotebookType;
use App\Models\PageAttachment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * M1 schema smoke tests (2026-09-13-005 Phase 002).
 *
 * Proves the load-bearing guarantees the later phases assume: UUIDv7 keys,
 * client-minted ids surviving verbatim, soft-delete tombstones, the sync
 * columns, and the share CHECK constraint.
 */
class M1SchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_get_uuid_v7_primary_keys(): void
    {
        $user = User::factory()->create();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $user->id,
            'Primary keys must be UUIDv7 (D-013) — note the literal 7 in the version nibble.',
        );
    }

    public function test_client_minted_id_is_stored_verbatim(): void
    {
        // The offline device mints the id and the server accepts it as-is (D-013).
        $id = (string) Str::uuid7();

        $notebook = Notebook::factory()->create(['id' => $id]);

        $this->assertSame($id, $notebook->fresh()->id);
    }

    /**
     * @return list<array{class-string, string}>
     */
    public static function rwTables(): array
    {
        return [
            'notebooks' => [Notebook::class, 'notebooks'],
            'notebook_pages' => [NotebookPage::class, 'notebook_pages'],
            'page_attachments' => [PageAttachment::class, 'page_attachments'],
        ];
    }

    /**
     * @param  class-string  $model
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('rwTables')]
    public function test_rw_table_soft_deletes_and_restores(string $model, string $table): void
    {
        $row = $model::factory()->create();

        $row->delete();

        // The row must SURVIVE as a tombstone — sync propagates deletes as
        // updates with deleted_at set (schema §2.4).
        $this->assertSoftDeleted($table, ['id' => $row->id]);
        $this->assertDatabaseCount($table, 1);

        $row->restore();

        $this->assertNotSoftDeleted($table, ['id' => $row->id]);
    }

    /**
     * @param  class-string  $model
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('rwTables')]
    public function test_rw_table_carries_client_updated_at(string $model, string $table): void
    {
        // Phase 009's last-write-wins depends on this column existing from the
        // very first migration.
        $row = $model::factory()->create(['client_updated_at' => now()]);

        $this->assertNotNull($row->fresh()->client_updated_at);
    }

    public function test_page_content_round_trips_as_tiptap_json(): void
    {
        $page = NotebookPage::factory()->create();

        $content = $page->fresh()->content;

        $this->assertIsArray($content, 'content must cast to array, never a JSON string (D-018).');
        $this->assertSame('doc', $content['type']);
    }

    public function test_notebook_type_page_template_casts_to_array(): void
    {
        $type = NotebookType::factory()->create();

        $this->assertIsArray($type->fresh()->page_template);
    }

    public function test_share_requires_exactly_one_target(): void
    {
        $notebook = Notebook::factory()->create();

        // Neither a user nor a token — the CHECK must reject it (schema §5).
        $this->expectException(QueryException::class);

        NotebookShare::factory()->create([
            'notebook_id' => $notebook->id,
            'shared_by' => $notebook->user_id,
            'shared_with_user_id' => null,
            'share_token' => null,
        ]);
    }

    public function test_share_accepts_a_token_link(): void
    {
        $notebook = Notebook::factory()->create();

        $share = NotebookShare::factory()->create([
            'notebook_id' => $notebook->id,
            'shared_by' => $notebook->user_id,
            'shared_with_user_id' => null,
            'share_token' => bin2hex(random_bytes(32)),
        ]);

        $this->assertDatabaseHas('notebook_shares', ['id' => $share->id]);
    }

    public function test_user_relationships_resolve(): void
    {
        $user = User::factory()->create();
        $notebook = Notebook::factory()->create(['user_id' => $user->id]);
        $page = NotebookPage::factory()->create(['notebook_id' => $notebook->id]);
        PageAttachment::factory()->create(['page_id' => $page->id]);

        $this->assertTrue($user->notebooks->contains($notebook));
        $this->assertTrue($notebook->pages->contains($page));
        $this->assertCount(1, $page->attachments);
        $this->assertSame($user->id, $notebook->user->id);
    }

    public function test_owned_scope_isolates_users(): void
    {
        $mine = User::factory()->create();
        $theirs = User::factory()->create();

        Notebook::factory()->create(['user_id' => $mine->id]);
        Notebook::factory()->create(['user_id' => $theirs->id]);

        $this->assertCount(1, Notebook::owned($mine)->get());
    }
}
