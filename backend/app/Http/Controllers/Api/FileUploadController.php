<?php

namespace App\Http\Controllers\Api;

use App\Models\FileUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * File uploads (2026-09-13-005 Phase 008).
 *
 * Laravel storage disks only, NEVER FTP (validation lesson). Two rules carry
 * the security here:
 *
 *   1. MIME is read from the file's CONTENT, not its extension and not the
 *      client-supplied Content-Type. Renaming evil.exe to photo.jpg must fail.
 *   2. The quota is checked against the SUM of the user's existing uploads, so
 *      a user cannot walk past it one small file at a time.
 *
 * `disk` stays 'public' for M1; moving to s3 is a config swap (schema §5).
 */
class FileUploadController extends ApiController
{
    private const COLUMNS = [
        'id', 'user_id', 'disk', 'path', 'original_name', 'mime_type',
        'size_bytes', 'sha256', 'created_at', 'updated_at',
    ];

    /**
     * POST /api/files?file&kind
     */
    public function store(Request $request): JsonResponse
    {
        $kinds = array_keys(config('notebook.upload_mimes', []));

        $request->validate([
            'file' => ['required', 'file'],
            'kind' => ['required', Rule::in($kinds)],
        ]);

        $file = $request->file('file');
        $kind = $request->string('kind')->toString();

        // Content-based sniffing: getMimeType() reads the file's magic bytes,
        // getClientMimeType() would trust the browser. Never the latter.
        $mime = $file->getMimeType();
        $allowed = config("notebook.upload_mimes.{$kind}", []);

        if (! in_array($mime, $allowed, true)) {
            return $this->validationError([
                'file' => ["A {$kind} must be one of: ".implode(', ', $allowed).". This file is {$mime}."],
            ]);
        }

        $size = $file->getSize();
        $maxBytes = (int) config("notebook.upload_max_bytes.{$kind}", 10 * 1024 * 1024);

        if ($size > $maxBytes) {
            $maxMb = round($maxBytes / 1024 / 1024, 1);

            return $this->validationError([
                'file' => ["A {$kind} may be at most {$maxMb} MB."],
            ]);
        }

        $user = $request->user();
        $quotaBytes = (int) config('notebook.quota_mb', 500) * 1024 * 1024;
        $used = (int) FileUpload::where('user_id', $user->id)->sum('size_bytes');

        if ($used + $size > $quotaBytes) {
            $remaining = max(0, $quotaBytes - $used);

            return $this->validationError([
                'file' => [sprintf(
                    'This would exceed your %d MB storage. You have %s MB free.',
                    config('notebook.quota_mb', 500),
                    number_format($remaining / 1024 / 1024, 1),
                )],
            ], 'Storage quota exceeded');
        }

        // Hash BEFORE storing: the temp file is still readable and this is the
        // only moment both the bytes and the row are in hand.
        $sha256 = hash_file('sha256', $file->getRealPath());

        $extension = $file->extension() ?: 'bin';
        $path = $file->storeAs(
            "uploads/{$user->id}",
            Str::uuid7().'.'.$extension,
            'public',
        );

        $upload = new FileUpload();

        $upload->forceFill([
            'user_id' => $user->id,
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $mime,
            'size_bytes' => $size,
            'sha256' => $sha256,
        ])->save();

        return $this->created($this->present($upload->fresh()), 'File uploaded');
    }

    /**
     * GET /api/files/{id}
     *
     * Own file only. Returns the row plus its served URL rather than streaming
     * bytes — the public disk serves the file itself, and an <img> needs a URL.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $upload = FileUpload::query()
            ->select(self::COLUMNS)
            ->where('user_id', $request->user()->id)
            ->find($id);

        return $upload === null
            ? $this->notFound('File not found.')
            : $this->success($this->present($upload));
    }

    /**
     * GET /api/files/usage
     *
     * What the quota UI needs. Literal route — registered before {id}.
     */
    public function usage(Request $request): JsonResponse
    {
        $quotaMb = (int) config('notebook.quota_mb', 500);
        $used = (int) FileUpload::where('user_id', $request->user()->id)->sum('size_bytes');

        return $this->success([
            'used_bytes' => $used,
            'quota_bytes' => $quotaMb * 1024 * 1024,
            'quota_mb' => $quotaMb,
        ]);
    }

    /** Attach the served URL — the contract's FileUploadWithUrl shape. */
    private function present(FileUpload $upload): array
    {
        return [
            ...$upload->only(self::COLUMNS),
            'url' => Storage::disk($upload->disk)->url($upload->path),
        ];
    }
}
