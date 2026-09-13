<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Throwable;

/**
 * Firebase → Sanctum authentication (2026-09-13-005 Phase 004, D-015).
 *
 * Firebase owns the credential (email+password, Google); this API never sees a
 * password. The client signs in with Firebase, sends the resulting ID token
 * here, and gets a stateless Sanctum bearer for every subsequent call.
 */
class AuthController extends ApiController
{
    public function __construct(private readonly FirebaseAuth $firebase) {}

    /**
     * POST /api/auth/firebase?id_token&first_name&last_name&middle_name&birthday&mobile_number&role
     *
     * Verifies the Firebase ID token, then finds / links / creates the user and
     * issues a Sanctum token. Profile fields are required only when the user
     * does not exist yet (first sign-up).
     */
    public function firebase(Request $request): JsonResponse
    {
        $request->validate([
            'id_token' => ['required', 'string'],
        ]);

        try {
            $verified = $this->firebase->verifyIdToken($request->string('id_token')->toString());
        } catch (FailedToVerifyToken $e) {
            return $this->unauthorized('Invalid or expired Firebase token.');
        } catch (Throwable $e) {
            report($e);

            return $this->error('Could not verify the Firebase token.', 503);
        }

        $firebaseUid = $verified->claims()->get('sub');
        $email = $verified->claims()->get('email');

        if (! is_string($firebaseUid) || $firebaseUid === '' || ! is_string($email) || $email === '') {
            return $this->unauthorized('Firebase token is missing a uid or email.');
        }

        $user = User::where('firebase_uid', $firebaseUid)->first();

        // Known email from a different sign-in method (or a seeded admin):
        // link this Firebase identity to the existing row rather than
        // creating a second account for the same person (D-014).
        if ($user === null) {
            $user = User::where('email', $email)->first();

            if ($user !== null) {
                $user->forceFill(['firebase_uid' => $firebaseUid])->save();
            }
        }

        if ($user === null) {
            $user = $this->register($request, $firebaseUid, $email);
        }

        if ($user->status !== 'active') {
            return $this->forbidden('This account has been disabled.');
        }

        // Abilities carry the role so a token cannot outlive a role change.
        $token = $user->createToken('device', [$user->role])->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => $user->fresh(),
        ], 'Signed in');
    }

    /**
     * First sign-up. The profile fields the brief requires are validated here,
     * not on the token-only path, so returning users never resend them.
     */
    private function register(Request $request, string $firebaseUid, string $email): User
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'birthday' => ['required', 'date', 'before:today'],
            'mobile_number' => ['required', 'string', 'max:20'],
            // Admin is seeded, never self-assigned.
            'role' => ['required', Rule::in(['student', 'teacher'])],
        ]);

        return DB::transaction(function () use ($data, $firebaseUid, $email) {
            $user = new User();

            $user->forceFill([
                ...$data,
                'email' => $email,
                'firebase_uid' => $firebaseUid,
                // Firebase verified it before minting the token.
                'email_verified_at' => now(),
                'status' => 'active',
            ])->save();

            return $user;
        });
    }

    /**
     * GET /api/user
     *
     * The caller behind the bearer, in the contract shape.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->success($request->user());
    }

    /**
     * POST /api/auth/logout
     *
     * Revokes only the calling device's token — other devices stay signed in.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Signed out');
    }

    /**
     * POST /api/auth/device?platform&device_identifier&device_name&fcm_token
     *
     * Upserts the calling device for FCM push targeting (schema §4).
     */
    public function device(Request $request): JsonResponse
    {
        $data = $request->validate([
            'platform' => ['required', Rule::in(['android', 'ios', 'web'])],
            'device_identifier' => ['required', 'string', 'max:128'],
            'device_name' => ['nullable', 'string', 'max:100'],
            'fcm_token' => ['nullable', 'string', 'max:255'],
        ]);

        $device = UserDevice::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'device_identifier' => $data['device_identifier'],
            ],
            [...$data, 'last_seen_at' => now()],
        );

        return $this->success($device, 'Device registered');
    }
}
