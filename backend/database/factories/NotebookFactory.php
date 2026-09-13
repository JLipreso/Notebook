<?php

namespace Database\Factories;

use App\Models\Notebook;
use App\Models\NotebookType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notebook>
 */
class NotebookFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'notebook_type_id' => NotebookType::factory(),
            'title' => fake()->words(3, true),
            'school_year' => '2026-2027',
            'status' => 'active',
            'position' => 0,
            'client_updated_at' => now(),
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
            'archived_at' => now(),
        ]);
    }
}
