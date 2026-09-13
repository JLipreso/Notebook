<?php

namespace Database\Factories;

use App\Models\NotebookPage;
use App\Models\PageAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageAttachment>
 */
class PageAttachmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'page_id' => NotebookPage::factory(),
            'file_upload_id' => null,
            'kind' => 'image',
            'local_ref' => null,
            'upload_status' => 'pending',
            'client_updated_at' => now(),
        ];
    }

    public function uploaded(): static
    {
        return $this->state(fn (array $attributes) => [
            'upload_status' => 'uploaded',
        ]);
    }
}
