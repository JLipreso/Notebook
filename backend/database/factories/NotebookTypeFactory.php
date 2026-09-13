<?php

namespace Database\Factories;

use App\Models\NotebookType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NotebookType>
 */
class NotebookTypeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'type_'.Str::lower(Str::random(8)),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            // Mirrors the PageTemplate contract shape (see NotebookTypeSeeder).
            'page_template' => [
                'ruling' => 'single_ruled',
                'line_spacing_mm' => 8.0,
                'margin' => null,
                'grid' => null,
                'header_fields' => [],
                'footer_fields' => [],
                'default_blocks' => ['paragraph'],
            ],
            'requires_ink' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
