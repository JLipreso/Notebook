<?php

namespace Tests\Feature;

use App\Models\Notebook;
use App\Models\NotebookPage;
use App\Models\User;
use App\Support\TiptapSanitizer;
use App\Support\TiptapText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/**
 * Pages, sanitization and search_text (2026-09-13-005 Phase 007).
 *
 * The load-bearing assertions: content is a JSON round trip with NO HTML
 * (D-018), the whitelist is enforced server-side whatever the client sends,
 * and page ownership is transitive through the notebook.
 */
class NotebookPageTest extends TestCase
{
    use RefreshDatabase;

    private function doc(array ...$nodes): array
    {
        return ['type' => 'doc', 'content' => $nodes];
    }

    private function paragraph(string $text): array
    {
        return ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text]]];
    }

    // ---------------------------------------------------------------- flatten

    public function test_flatten_extracts_text_in_document_order(): void
    {
        $doc = $this->doc(
            ['type' => 'heading', 'attrs' => ['level' => 1], 'content' => [['type' => 'text', 'text' => 'Fractions']]],
            $this->paragraph('Like fractions have the same denominator.'),
        );

        $this->assertSame(
            'Fractions Like fractions have the same denominator.',
            TiptapText::flatten($doc),
        );
    }

    public function test_flatten_handles_empty_and_null(): void
    {
        $this->assertSame('', TiptapText::flatten(null));
        $this->assertSame('', TiptapText::flatten(['type' => 'doc']));
        $this->assertSame('', TiptapText::flatten($this->doc($this->paragraph(''))));
    }

    public function test_flatten_collapses_whitespace(): void
    {
        $doc = $this->doc($this->paragraph("spaced   out\n\ntext"));

        $this->assertSame('spaced out text', TiptapText::flatten($doc));
    }

    // -------------------------------------------------------------- sanitizer

    public function test_sanitizer_drops_disallowed_nodes(): void
    {
        $doc = $this->doc(
            $this->paragraph('kept'),
            ['type' => 'script', 'content' => [['type' => 'text', 'text' => 'evil']]],
            ['type' => 'iframe'],
        );

        $clean = TiptapSanitizer::clean($doc);

        $this->assertCount(1, $clean['content']);
        $this->assertSame('paragraph', $clean['content'][0]['type']);
    }

    public function test_sanitizer_drops_disallowed_marks(): void
    {
        $doc = $this->doc([
            'type' => 'paragraph',
            'content' => [[
                'type' => 'text',
                'text' => 'hi',
                'marks' => [['type' => 'bold'], ['type' => 'link', 'attrs' => ['href' => 'javascript:alert(1)']]],
            ]],
        ]);

        $marks = TiptapSanitizer::clean($doc)['content'][0]['content'][0]['marks'];

        $this->assertSame([['type' => 'bold']], $marks);
    }

    public function test_sanitizer_strips_nested_disallowed_nodes(): void
    {
        $doc = $this->doc([
            'type' => 'bulletList',
            'content' => [[
                'type' => 'listItem',
                'content' => [$this->paragraph('ok'), ['type' => 'object']],
            ]],
        ]);

        $item = TiptapSanitizer::clean($doc)['content'][0]['content'][0];

        $this->assertCount(1, $item['content']);
        $this->assertSame('paragraph', $item['content'][0]['type']);
    }

    public function test_sanitizer_caps_heading_levels(): void
    {
        $doc = $this->doc(['type' => 'heading', 'attrs' => ['level' => 6], 'content' => []]);

        $this->assertSame(1, TiptapSanitizer::clean($doc)['content'][0]['attrs']['level']);
    }

    public function test_sanitizer_rejects_oversized_content(): void
    {
        $doc = $this->doc($this->paragraph(str_repeat('x', 600 * 1024)));

        $this->expectException(RuntimeException::class);
        TiptapSanitizer::clean($doc);
    }

    public function test_sanitizer_always_returns_a_doc_root(): void
    {
        $clean = TiptapSanitizer::clean(['type' => 'paragraph', 'content' => []]);

        $this->assertSame('doc', $clean['type']);
    }

    // ------------------------------------------------------------------ pages

    public function test_pages_require_auth(): void
    {
        $notebook = Notebook::factory()->create();

        $this->getJson("/api/notebooks/{$notebook->id}/pages")->assertUnauthorized();
    }

    public function test_client_minted_page_id_is_stored_verbatim(): void
    {
        $user = User::factory()->create();
        $notebook = Notebook::factory()->create(['user_id' => $user->id]);
        $id = (string) Str::uuid7();

        $this->actingAs($user)
            ->postJson("/api/notebooks/{$notebook->id}/pages", [
                'id' => $id,
                'position' => 0,
                'content' => $this->doc($this->paragraph('Hello')),
            ])
            ->assertCreated()
            ->assertJsonPath('data.id', $id);

        $this->assertDatabaseHas('notebook_pages', ['id' => $id, 'notebook_id' => $notebook->id]);
    }

    public function test_content_round_trips_as_json_not_html(): void
    {
        $user = User::factory()->create();
        $notebook = Notebook::factory()->create(['user_id' => $user->id]);
        $content = $this->doc(
            ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Week 1']]],
            $this->paragraph('Monday'),
        );

        $id = (string) Str::uuid7();

        $this->actingAs($user)->postJson("/api/notebooks/{$notebook->id}/pages", [
            'id' => $id,
            'content' => $content,
        ])->assertCreated();

        $stored = $this->actingAs($user)->getJson("/api/pages/{$id}")->json('data.content');

        $this->assertSame($content, $stored);
        $this->assertIsArray($stored, 'content must never be serialized to an HTML string (D-018).');
    }

    public function test_search_text_is_maintained_on_write_and_not_client_settable(): void
    {
        $user = User::factory()->create();
        $notebook = Notebook::factory()->create(['user_id' => $user->id]);
        $id = (string) Str::uuid7();

        $this->actingAs($user)->postJson("/api/notebooks/{$notebook->id}/pages", [
            'id' => $id,
            'content' => $this->doc($this->paragraph('Fractions and decimals')),
            // Ignored: search_text is derived.
            'search_text' => 'INJECTED',
        ])->assertCreated();

        $this->assertSame('Fractions and decimals', NotebookPage::find($id)->search_text);

        $this->actingAs($user)->putJson("/api/pages/{$id}", [
            'content' => $this->doc($this->paragraph('Now about angles')),
        ])->assertOk();

        $this->assertSame('Now about angles', NotebookPage::find($id)->search_text);
    }

    public function test_disallowed_nodes_are_stripped_through_the_api(): void
    {
        $user = User::factory()->create();
        $notebook = Notebook::factory()->create(['user_id' => $user->id]);
        $id = (string) Str::uuid7();

        $this->actingAs($user)->postJson("/api/notebooks/{$notebook->id}/pages", [
            'id' => $id,
            'content' => $this->doc($this->paragraph('safe'), ['type' => 'script']),
        ])->assertCreated();

        $content = NotebookPage::find($id)->content;

        $this->assertCount(1, $content['content']);
        $this->assertSame('paragraph', $content['content'][0]['type']);
    }

    public function test_oversized_content_is_a_422_envelope(): void
    {
        $user = User::factory()->create();
        $notebook = Notebook::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->postJson("/api/notebooks/{$notebook->id}/pages", [
            'id' => (string) Str::uuid7(),
            'content' => $this->doc($this->paragraph(str_repeat('x', 600 * 1024))),
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['content']);
    }

    /** Ownership is transitive: the page belongs to whoever owns the notebook. */
    public function test_pages_in_another_users_notebook_are_404(): void
    {
        $mine = User::factory()->create();
        $theirs = User::factory()->create();
        $notebook = Notebook::factory()->create(['user_id' => $theirs->id]);
        $page = NotebookPage::factory()->create(['notebook_id' => $notebook->id]);

        $this->actingAs($mine)->getJson("/api/notebooks/{$notebook->id}/pages")->assertNotFound();
        $this->actingAs($mine)->getJson("/api/pages/{$page->id}")->assertNotFound();
        $this->actingAs($mine)->putJson("/api/pages/{$page->id}", ['title' => 'Stolen'])->assertNotFound();
        $this->actingAs($mine)->deleteJson("/api/pages/{$page->id}")->assertNotFound();

        $this->assertSame($page->title, $page->fresh()->title);
    }

    public function test_pages_are_ordered_by_position(): void
    {
        $user = User::factory()->create();
        $notebook = Notebook::factory()->create(['user_id' => $user->id]);

        NotebookPage::factory()->create(['notebook_id' => $notebook->id, 'position' => 2, 'title' => 'third']);
        NotebookPage::factory()->create(['notebook_id' => $notebook->id, 'position' => 0, 'title' => 'first']);
        NotebookPage::factory()->create(['notebook_id' => $notebook->id, 'position' => 1, 'title' => 'second']);

        $titles = collect($this->actingAs($user)->getJson("/api/notebooks/{$notebook->id}/pages")->json('data'))
            ->pluck('title')
            ->all();

        $this->assertSame(['first', 'second', 'third'], $titles);
    }

    public function test_page_delete_is_soft(): void
    {
        $user = User::factory()->create();
        $notebook = Notebook::factory()->create(['user_id' => $user->id]);
        $page = NotebookPage::factory()->create(['notebook_id' => $notebook->id]);

        $this->actingAs($user)->deleteJson("/api/pages/{$page->id}")->assertOk();

        $this->assertSoftDeleted('notebook_pages', ['id' => $page->id]);
        $this->assertCount(0, $this->actingAs($user)->getJson("/api/notebooks/{$notebook->id}/pages")->json('data'));
    }
}
