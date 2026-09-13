<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Lcobucci\JWT\Token\DataSet;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Token\Signature;
use Mockery;
use Tests\TestCase;

/**
 * Firebase → Sanctum auth (2026-09-13-005 Phase 004, D-015).
 *
 * The Firebase verifier is bound to a fake in the container — these tests never
 * touch the network or need real credentials, so they run in CI.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** Bind a verifier that accepts one token and returns those claims. */
    private function fakeFirebase(string $uid, string $email, string $accepts = 'valid-token'): void
    {
        $token = new Plain(
            new DataSet([], ''),
            new DataSet(['sub' => $uid, 'email' => $email], ''),
            new Signature('', ''),
        );

        $mock = Mockery::mock(FirebaseAuth::class);
        $mock->shouldReceive('verifyIdToken')
            ->andReturnUsing(function (string $given) use ($token, $accepts) {
                if ($given !== $accepts) {
                    throw new FailedToVerifyToken('The token is invalid.');
                }

                return $token;
            });

        $this->app->instance(FirebaseAuth::class, $mock);
    }

    private function signUpPayload(array $overrides = []): array
    {
        return array_merge([
            'id_token' => 'valid-token',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'birthday' => '2012-06-14',
            'mobile_number' => '09171234567',
            'role' => 'student',
        ], $overrides);
    }

    public function test_first_exchange_creates_the_user(): void
    {
        $this->fakeFirebase('firebase-uid-1', 'maria@example.com');

        $response = $this->postJson('/api/auth/firebase', $this->signUpPayload());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'message', 'data' => ['token', 'user' => ['id', 'email', 'role']]]);

        $this->assertDatabaseHas('users', [
            'email' => 'maria@example.com',
            'firebase_uid' => 'firebase-uid-1',
            'role' => 'student',
        ]);

        // Firebase already verified the address before minting the token.
        $this->assertNotNull(User::where('email', 'maria@example.com')->first()->email_verified_at);
    }

    public function test_second_exchange_reuses_the_same_user(): void
    {
        $this->fakeFirebase('firebase-uid-1', 'maria@example.com');

        $this->postJson('/api/auth/firebase', $this->signUpPayload())->assertOk();
        // No profile fields the second time — they must not be required.
        $this->postJson('/api/auth/firebase', ['id_token' => 'valid-token'])->assertOk();

        $this->assertDatabaseCount('users', 1);
    }

    public function test_existing_email_links_the_firebase_uid(): void
    {
        // A user who exists without a Firebase identity (e.g. a seeded admin).
        $user = User::factory()->create(['email' => 'maria@example.com', 'firebase_uid' => null]);

        $this->fakeFirebase('firebase-uid-1', 'maria@example.com');

        $this->postJson('/api/auth/firebase', ['id_token' => 'valid-token'])->assertOk();

        $this->assertDatabaseCount('users', 1);
        $this->assertSame('firebase-uid-1', $user->fresh()->firebase_uid);
    }

    public function test_bad_token_is_rejected_with_the_envelope(): void
    {
        $this->fakeFirebase('firebase-uid-1', 'maria@example.com');

        $this->postJson('/api/auth/firebase', ['id_token' => 'garbage'])
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_signup_requires_the_brief_profile_fields(): void
    {
        $this->fakeFirebase('firebase-uid-new', 'new@example.com');

        $this->postJson('/api/auth/firebase', ['id_token' => 'valid-token'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name', 'birthday', 'mobile_number', 'role']);
    }

    public function test_role_cannot_be_admin_at_signup(): void
    {
        $this->fakeFirebase('firebase-uid-2', 'sneaky@example.com');

        $this->postJson('/api/auth/firebase', $this->signUpPayload(['role' => 'admin']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    public function test_role_from_the_client_cannot_change_an_existing_role(): void
    {
        $user = User::factory()->create([
            'email' => 'teacher@example.com',
            'firebase_uid' => 'firebase-uid-3',
            'role' => 'teacher',
        ]);

        $this->fakeFirebase('firebase-uid-3', 'teacher@example.com');

        $this->postJson('/api/auth/firebase', $this->signUpPayload([
            'role' => 'student',
        ]))->assertOk();

        $this->assertSame('teacher', $user->fresh()->role);
    }

    public function test_disabled_account_cannot_sign_in(): void
    {
        User::factory()->create([
            'email' => 'blocked@example.com',
            'firebase_uid' => 'firebase-uid-4',
            'status' => 'disabled',
        ]);

        $this->fakeFirebase('firebase-uid-4', 'blocked@example.com');

        $this->postJson('/api/auth/firebase', ['id_token' => 'valid-token'])
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_me_returns_the_caller_and_requires_a_bearer(): void
    {
        $user = User::factory()->create();

        $this->getJson('/api/user')->assertUnauthorized();

        $this->actingAs($user)->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create();
        $keep = $user->createToken('other-device')->plainTextToken;
        $drop = $user->createToken('this-device')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$drop}")
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 1);

        // The other device is still signed in.
        $this->withHeader('Authorization', "Bearer {$keep}")
            ->getJson('/api/user')
            ->assertOk();
    }

    public function test_device_registration_is_idempotent(): void
    {
        $user = User::factory()->create();

        $payload = ['platform' => 'android', 'device_identifier' => 'device-abc', 'fcm_token' => 'token-1'];

        $this->actingAs($user)->postJson('/api/auth/device', $payload)->assertOk();
        $this->actingAs($user)->postJson('/api/auth/device', [...$payload, 'fcm_token' => 'token-2'])->assertOk();

        // UNIQUE(user_id, device_identifier) — one row, updated.
        $this->assertDatabaseCount('user_devices', 1);
        $this->assertDatabaseHas('user_devices', ['device_identifier' => 'device-abc', 'fcm_token' => 'token-2']);
    }

    public function test_token_carries_the_role_as_an_ability(): void
    {
        $this->fakeFirebase('firebase-uid-5', 'teach@example.com');

        $this->postJson('/api/auth/firebase', $this->signUpPayload([
            'role' => 'teacher',
            'first_name' => 'Ana',
        ]))->assertOk();

        $user = User::where('email', 'teach@example.com')->firstOrFail();

        $this->assertSame(['teacher'], $user->tokens()->first()->abilities);
    }

    /**
     * Framework-thrown errors must use the SAME envelope as ApiController —
     * the shared TS ApiResponse<T> declares `success` on every response, so
     * Laravel's default {message, errors} shape would break the contract.
     */
    public function test_framework_errors_use_the_frozen_envelope(): void
    {
        $this->fakeFirebase('firebase-uid-9', 'env@example.com');

        // 422 from validation
        $this->postJson('/api/auth/firebase', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message', 'errors']);

        // 401 from the auth middleware
        $this->getJson('/api/user')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors', null);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
