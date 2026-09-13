<?php

namespace Database\Factories;

use App\Models\Notebook;
use App\Models\NotebookPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotebookPage>
 */
class NotebookPageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $text = fake()->sentence();

        return [
            'notebook_id' => Notebook::factory(),
            'position' => 0,
            'title' => fake()->words(2, true),
            // Real Tiptap JSON, never HTML (D-018).
            'content' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [['type' => 'text', 'text' => $text]],
                    ],
                ],
            ],
            'search_text' => $text,
            'client_updated_at' => now(),
        ];
    }
}
