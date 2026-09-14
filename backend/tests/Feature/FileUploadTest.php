<?php

namespace Tests\Feature;

use App\Models\FileUpload;
use App\Models\Notebook;
use App\Models\NotebookPage;
use App\Models\PageAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Uploads, quota and page attachments (2026-09-13-005 Phase 008).
 *
 * The load-bearing assertions: MIME is judged by CONTENT not extension, the
 * quota cannot be walked past one file at a time, and a file is never hard
 * deleted.
 */
class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** A real PNG — UploadedFile::fake()->image() produces genuine image bytes. */
    private function png(string $name = 'photo.png', int $kb = 40): UploadedFile
    {
        return UploadedFile::fake()->image($name, 100, 100)->size($kb);
    }

    private function pdf(string $name = 'notes.pdf', int $kb = 100): UploadedFile
    {
        // create() with a mime produces a file whose content sniffs as that type.
        return UploadedFile::fake()->create($name, $kb, 'application/pdf');
    }

    public function test_upload_requires_auth(): void
    {
        $this->postJson('/api/files', ['kind' => 'image'])->assertUnauthorized();
    }

    public function test_image_upload_stores_the_file_and_row(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/files', ['file' => $this->png(), 'kind' => 'image'])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['id', 'path', 'mime_type', 'size_bytes', 'sha256', 'url']]);

        $path = $response->json('data.path');

        Storage::disk('public')->assertExists($path);
        $this->assertStringStartsWith("uploads/{$user->id}/", $path);
        $this->assertDatabaseHas('file_uploads', ['user_id' => $user->id, 'disk' => 'public']);
        $this->assertNotNull($response->json('data.sha256'));
    }

    /**
     * The phase file's explicit case: rename an executable to .jpg.
     *
     * ================================ READ THIS ===============================
     * This CANNOT be tested with UploadedFile::fake(). Laravel's test double
     * (Illuminate\Http\Testing\File) overrides getMimeType() to return
     * `MimeType::from($this->name)` — the type guessed from the FILENAME. Under
     * a fake, a disguised executable reports 'image/jpeg' and sails through,
     * even though production rejects it.
     *
     * So this builds a REAL UploadedFile over real bytes on disk, which is the
     * only way to exercise the actual sniffing path. Confirmed separately: the
     * same bytes through Symfony's File::getMimeType() report
     * 'application/octet-stream', and a genuine PNG named .jpg reports
     * 'image/png' — content wins over extension in both directions.
     * ==========================================================================
     */
    public function test_mime_is_judged_by_content_not_extension(): void
    {
        $user = User::factory()->create();

        $tmp = tempnam(sys_get_temp_dir(), 'evil').'.jpg';
        file_put_contents($tmp, "MZ\x90\x00this is not an image at all");

        $disguised = new UploadedFile($tmp, 'evil.jpg', 'image/jpeg', null, true);

        $this->actingAs($user)
            ->postJson('/api/files', ['file' => $disguised, 'kind' => 'image'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['file']);

        $this->assertDatabaseCount('file_uploads', 0);

        @unlink($tmp);
    }

    /** The mirror case: real image bytes under a wrong extension are ACCEPTED. */
    public function test_real_image_bytes_are_accepted_whatever_the_extension(): void
    {
        $user = User::factory()->create();

        $tmp = tempnam(sys_get_temp_dir(), 'img').'.txt';
        file_put_contents($tmp, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));

        $png = new UploadedFile($tmp, 'photo.txt', 'text/plain', null, true);

        $this->actingAs($user)
            ->postJson('/api/files', ['file' => $png, 'kind' => 'image'])
            ->assertCreated()
            ->assertJsonPath('data.mime_type', 'image/png');

        @unlink($tmp);
    }

    public function test_pdf_cannot_be_uploaded_as_an_image(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/files', ['file' => $this->pdf(), 'kind' => 'image'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_pdf_upload_is_accepted_for_the_pdf_kind(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/files', ['file' => $this->pdf(), 'kind' => 'pdf'])
            ->assertCreated()
            ->assertJsonPath('data.mime_type', 'application/pdf');
    }

    public function test_unknown_kind_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/files', ['file' => $this->png(), 'kind' => 'executable'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['kind']);
    }

    public function test_size_cap_is_enforced_per_kind(): void
    {
        $user = User::factory()->create();

        // 6 MB against the 5 MB cover cap.
        $this->actingAs($user)
            ->postJson('/api/files', ['file' => $this->png('big.png', 6 * 1024), 'kind' => 'cover'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);

        // The same file is fine as an image (10 MB cap).
        $this->actingAs($user)
            ->postJson('/api/files', ['file' => $this->png('big.png', 6 * 1024), 'kind' => 'image'])
            ->assertCreated();
    }

    /**
     * The quota is checked against the SUM of existing uploads, so it cannot be
     * walked past one small file at a time.
     */
    public function test_quota_is_enforced_against_the_running_total(): void
    {
        $user = User::factory()->create();
        $quotaBytes = (int) config('notebook.quota_mb') * 1024 * 1024;

        // Pre-fill to 1 MB below the quota without touching the disk.
        FileUpload::factory()->create([
            'user_id' => $user->id,
            'size_bytes' => $quotaBytes - 1024 * 1024,
        ]);

        // 2 MB does not fit in the remaining 1 MB.
        $this->actingAs($user)
            ->postJson('/api/files', ['file' => $this->png('over.png', 2 * 1024), 'kind' => 'image'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['file']);

        // Something that does fit is accepted.
        $this->actingAs($user)
            ->postJson('/api/files', ['file' => $this->png('ok.png', 200), 'kind' => 'image'])
            ->assertCreated();
    }

    public function test_quota_is_per_user(): void
    {
        $mine = User::factory()->create();
        $theirs = User::factory()->create();
        $quotaBytes = (int) config('notebook.quota_mb') * 1024 * 1024;

        // Their usage fills the quota; mine must be unaffected.
        FileUpload::factory()->create(['user_id' => $theirs->id, 'size_bytes' => $quotaBytes]);

        $this->actingAs($mine)
            ->postJson('/api/files', ['file' => $this->png(), 'kind' => 'image'])
            ->assertCreated();
    }

    public function test_usage_reports_own_total_only(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        FileUpload::factory()->create(['user_id' => $user->id, 'size_bytes' => 1024]);
        FileUpload::factory()->create(['user_id' => $other->id, 'size_bytes' => 9999]);

        $this->actingAs($user)->getJson('/api/files/usage')
            ->assertOk()
            ->assertJsonPath('data.used_bytes', 1024);
    }

    public function test_file_show_is_own_file_only(): void
    {
        $mine = User::factory()->create();
        $theirs = User::factory()->create();
        $upload = FileUpload::factory()->create(['user_id' => $theirs->id]);

        $this->actingAs($mine)->getJson("/api/files/{$upload->id}")->assertNotFound();
        $this->actingAs($theirs)->getJson("/api/files/{$upload->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $upload->id);
    }

    // ------------------------------------------------------------ attachments

    public function test_attachment_links_an_upload_to_a_page(): void
    {
        $user = User::factory()->create();
        $notebook = Notebook::factory()->create(['user_id' => $user->id]);
        $page = NotebookPage::factory()->create(['notebook_id' => $notebook->id]);
        $upload = FileUpload::factory()->create(['user_id' => $user->id]);
        $id = (string) Str::uuid7();

        $this->actingAs($user)
            ->postJson("/api/pages/{$page->id}/attachments", [
                'id' => $id,
                'kind' => 'image',
                'file_upload_id' => $upload->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.upload_status', 'uploaded')
            ->assertJsonPath('data.file_upload.id', $upload->id);
    }

    /** Without a file yet, the row is 'pending' — the offline path (§2.5). */
    public function test_attachment_without_an_upload_is_pending(): void
    {
        $user = User::factory()->create();
        $notebook = Notebook::factory()->create(['user_id' => $user->id]);
        $page = NotebookPage::factory()->create(['notebook_id' => $notebook->id]);

        $this->actingAs($user)
            ->postJson("/api/pages/{$page->id}/attachments", [
                'id' => (string) Str::uuid7(),
                'kind' => 'image',
                'local_ref' => 'file:///device/photo.jpg',
            ])
            ->assertCreated()
            ->assertJsonPath('data.upload_status', 'pending')
            ->assertJsonPath('data.file_upload', null);
    }

    /** A guessed upload id must not expose someone else's file. */
    public function test_attachment_cannot_reference_another_users_upload(): void
    {
        $mine = User::factory()->create();
        $theirs = User::factory()->create();
        $notebook = Notebook::factory()->create(['user_id' => $mine->id]);
        $page = NotebookPage::factory()->create(['notebook_id' => $notebook->id]);
        $upload = FileUpload::factory()->create(['user_id' => $theirs->id]);

        $this->actingAs($mine)
            ->postJson("/api/pages/{$page->id}/attachments", [
                'id' => (string) Str::uuid7(),
                'kind' => 'image',
                'file_upload_id' => $upload->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file_upload_id']);
    }

    public function test_attachments_on_another_users_page_are_404(): void
    {
        $mine = User::factory()->create();
        $theirs = User::factory()->create();
        $notebook = Notebook::factory()->create(['user_id' => $theirs->id]);
        $page = NotebookPage::factory()->create(['notebook_id' => $notebook->id]);

        $this->actingAs($mine)->getJson("/api/pages/{$page->id}/attachments")->assertNotFound();
        $this->actingAs($mine)->postJson("/api/pages/{$page->id}/attachments", [
            'id' => (string) Str::uuid7(),
            'kind' => 'image',
        ])->assertNotFound();
    }

    /** The row goes; the FILE never does — another row may reference it. */
    public function test_detaching_soft_deletes_the_row_and_keeps_the_file(): void
    {
        $user = User::factory()->create();
        $notebook = Notebook::factory()->create(['user_id' => $user->id]);
        $page = NotebookPage::factory()->create(['notebook_id' => $notebook->id]);
        $upload = FileUpload::factory()->create(['user_id' => $user->id]);
        $attachment = PageAttachment::factory()->uploaded()->create([
            'page_id' => $page->id,
            'file_upload_id' => $upload->id,
        ]);

        $this->actingAs($user)->deleteJson("/api/attachments/{$attachment->id}")->assertOk();

        $this->assertSoftDeleted('page_attachments', ['id' => $attachment->id]);
        $this->assertDatabaseHas('file_uploads', ['id' => $upload->id]);
    }
}
