<?php

namespace Database\Factories;

use App\Models\FileUpload;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FileUpload>
 */
class FileUploadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'disk' => 'public',
            'path' => 'uploads/'.Str::uuid7().'.png',
            'original_name' => 'photo.png',
            'mime_type' => 'image/png',
            'size_bytes' => 1024,
            'sha256' => hash('sha256', Str::random()),
        ];
    }
}
