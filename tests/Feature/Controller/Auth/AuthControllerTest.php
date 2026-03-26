<?php

declare(strict_types=1);

use App\Models\{AuditLog, User};
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\{getJson, postJson};

it('returns 401 on logout when unauthenticated', function () {
    postJson('/api/auth/logout')->assertUnauthorized();
});

it('returns 401 on me when unauthenticated', function () {
    getJson('/api/auth/me')->assertUnauthorized();
});

describe('login', function () {
    it('returns token on valid credentials', function () {
        $user = User::factory()->create(['password' => 'secret123']);

        postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertOk()->assertJsonStructure(['token']);
    });

    it('updates last_login_at on successful login', function () {
        $user = User::factory()->create(['password' => 'secret123']);

        postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertOk();

        expect($user->fresh()->last_login_at)->not->toBeNull();
    });

    it('creates audit log on successful login', function () {
        $user = User::factory()->create(['password' => 'secret123']);

        postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertOk();

        expect(
            AuditLog::where('action', 'login')
                ->where('entity', 'User')
                ->where('entity_id', $user->id)
                ->exists()
        )->toBeTrue();
    });

    it('returns 401 for invalid password', function () {
        $user = User::factory()->create(['password' => 'secret123']);

        postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ])->assertUnauthorized()->assertJson(['message' => 'Invalid credentials']);
    });

    it('returns 401 for non-existent email', function () {
        postJson('/api/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'secret123',
        ])->assertUnauthorized();
    });

    it('returns 403 for inactive user', function () {
        $user = User::factory()->inactive()->create(['password' => 'secret123']);

        postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertForbidden()->assertJson(['message' => 'User account is disabled']);
    });

    it('returns 422 for missing fields', function () {
        postJson('/api/auth/login', [])->assertUnprocessable();
    });
});

describe('logout', function () {
    it('returns 204 and revokes token', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        postJson('/api/auth/logout')->assertNoContent();
    });
});

describe('me', function () {
    it('returns authenticated user with roles and permissions', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'is_active', 'roles', 'permissions']]);
    });
});
