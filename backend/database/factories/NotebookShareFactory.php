<?php

namespace Database\Factories;

use App\Models\Notebook;
use App\Models\NotebookShare;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotebookShare>
 */
class NotebookShareFactory extends Factory
{
    /**
     * Defaults to a read-only token link — the only kind M1 ships (Phase 010).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'notebook_id' => Notebook::factory(),
            'page_id' => null,
            'shared_by' => User::factory(),
            'shared_with_user_id' => null,
            'share_token' => bin2hex(random_bytes(32)),
            'access' => 'read',
        ];
    }
}
