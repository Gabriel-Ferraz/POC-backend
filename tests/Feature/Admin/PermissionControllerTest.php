<?php

declare(strict_types=1);

use App\Models\{Permission, User};
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\getJson;

it('returns 401 on permissions index when unauthenticated', function () {
    getJson('/api/admin/permissions')->assertUnauthorized();
});

describe('index', function () {
    beforeEach(function () {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        Sanctum::actingAs($user);
    });

    it('returns all permissions', function () {
        Permission::firstOrCreate(['name' => 'test.permission', 'guard_name' => 'web']);

        getJson('/api/admin/permissions')->assertOk();
    });
});

describe('show', function () {
    beforeEach(function () {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        Sanctum::actingAs($user);
    });

    it('returns permission resource', function () {
        $perm = Permission::firstOrCreate(['name' => 'show.permission', 'guard_name' => 'web']);

        getJson("/api/admin/permissions/{$perm->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'show.permission');
    });

    it('returns 404 for non-existent permission', function () {
        getJson('/api/admin/permissions/99999')->assertNotFound();
    });
});
