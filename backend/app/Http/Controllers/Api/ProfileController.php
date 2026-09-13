<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The signed-in user's own profile (2026-09-13-005 Phase 005).
 *
 * Email and role are deliberately NOT editable here: email is the Firebase
 * identity anchor (D-015) and role never comes from the client after creation
 * (D-014). An admin tool changes those, not the profile screen.
 */
class ProfileController extends ApiController
{
    /**
     * GET /api/profile
     */
    public function show(Request $request): JsonResponse
    {
        return $this->success($request->user());
    }

    /**
     * PUT /api/profile?first_name&last_name&middle_name&birthday&mobile_number&barangay_code&address_line&avatar_path
     */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'birthday' => ['sometimes', 'required', 'date', 'before:today'],
            'mobile_number' => ['sometimes', 'required', 'string', 'max:20'],
            // PSGC natural key — the FK is MySQL-only (sqlite cannot ALTER ADD
            // CONSTRAINT), so this rule is the real guard on both engines.
            'barangay_code' => ['nullable', 'string', 'size:10', 'exists:barangays,code'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'avatar_path' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $user->fill($data)->save();

        return $this->success($user->fresh(), 'Profile updated');
    }
}
