<?php

declare(strict_types=1);

use App\Models\{AuditLog, User};
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\{Permission, Role};

use function Pest\Laravel\{deleteJson, getJson, postJson, putJson};

it('returns 401 on users index when unauthenticated', function () {
    getJson('/api/admin/users')->assertUnauthorized();
});

describe('index', function () {
    beforeEach(function () {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        Sanctum::actingAs($user);
    });

    it('returns paginated user list', function () {
        User::factory()->count(3)->create();

        getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    });
});

describe('store', function () {
    beforeEach(function () {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        Sanctum::actingAs($user);
        Permission::firstOrCreate(['name' => 'admin.users.store', 'guard_name' => 'web']);
    });

    it('creates user and returns 201', function () {
        postJson('/api/admin/users', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'secret123',
        ])->assertCreated()->assertJsonPath('data.email', 'new@example.com');

        expect(User::where('email', 'new@example.com')->exists())->toBeTrue();
    });

    it('creates audit log on store', function () {
        $before = AuditLog::count();

        postJson('/api/admin/users', [
            'name' => 'Audit User',
            'email' => 'audit@example.com',
            'password' => 'secret123',
        ])->assertCreated();

        expect(AuditLog::count())->toBeGreaterThan($before);
    });

    it('returns 422 for duplicate email', function () {
        $existing = User::factory()->create();

        postJson('/api/admin/users', [
            'name' => 'Dup',
            'email' => $existing->email,
            'password' => 'secret123',
        ])->assertUnprocessable();
    });

    it('returns 422 for missing required fields', function () {
        postJson('/api/admin/users', [])->assertUnprocessable();
    });
});

describe('show', function () {
    beforeEach(function () {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        Sanctum::actingAs($user);
    });

    it('returns user resource', function () {
        $target = User::factory()->create();

        getJson("/api/admin/users/{$target->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $target->id);
    });

    it('returns 404 for non-existent user', function () {
        getJson('/api/admin/users/99999')->assertNotFound();
    });
});

describe('update', function () {
    beforeEach(function () {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        Sanctum::actingAs($user);
        Permission::firstOrCreate(['name' => 'admin.users.update', 'guard_name' => 'web']);
    });

    it('updates user and returns resource', function () {
        $target = User::factory()->create();

        putJson("/api/admin/users/{$target->id}", ['name' => 'Updated Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');
    });

    it('does not change password when field is omitted', function () {
        $target = User::factory()->create(['password' => 'original123']);

        putJson("/api/admin/users/{$target->id}", ['name' => 'New Name'])->assertOk();

        expect(password_verify('original123', $target->fresh()->password))->toBeTrue();
    });

    it('creates audit log on update', function () {
        $target = User::factory()->create();
        $before = AuditLog::count();

        putJson("/api/admin/users/{$target->id}", ['name' => 'Changed'])->assertOk();

        expect(AuditLog::count())->toBeGreaterThan($before);
    });
});

describe('destroy', function () {
    beforeEach(function () {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        Sanctum::actingAs($user);
        Permission::firstOrCreate(['name' => 'admin.users.destroy', 'guard_name' => 'web']);
    });

    it('soft deletes user and returns 204', function () {
        $target = User::factory()->create();

        deleteJson("/api/admin/users/{$target->id}")->assertNoContent();

        expect(User::withTrashed()->find($target->id)->deleted_at)->not->toBeNull();
    });

    it('returns 403 when deleting super-admin user', function () {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        deleteJson("/api/admin/users/{$superAdmin->id}")->assertForbidden();
    });

    it('creates audit log on destroy', function () {
        $target = User::factory()->create();
        $before = AuditLog::count();

        deleteJson("/api/admin/users/{$target->id}")->assertNoContent();

        expect(AuditLog::count())->toBeGreaterThan($before);
    });
});

describe('syncRoles', function () {
    beforeEach(function () {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        Sanctum::actingAs($user);
        Permission::firstOrCreate(['name' => 'admin.users.update', 'guard_name' => 'web']);
    });

    it('syncs roles on user', function () {
        Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
        $target = User::factory()->create();

        putJson("/api/admin/users/{$target->id}/roles", ['roles' => ['editor']])
            ->assertOk()
            ->assertJsonPath('data.roles.0', 'editor');
    });

    it('returns 403 when removing super-admin role', function () {
        Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        putJson("/api/admin/users/{$superAdmin->id}/roles", ['roles' => ['editor']])
            ->assertForbidden();
    });
});

describe('syncPermissions', function () {
    beforeEach(function () {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        Sanctum::actingAs($user);
        Permission::firstOrCreate(['name' => 'admin.users.update', 'guard_name' => 'web']);
    });

    it('syncs direct permissions on user', function () {
        Permission::firstOrCreate(['name' => 'some.permission', 'guard_name' => 'web']);
        $target = User::factory()->create();

        putJson("/api/admin/users/{$target->id}/permissions", ['permissions' => ['some.permission']])
            ->assertOk();

        expect($target->fresh()->hasDirectPermission('some.permission'))->toBeTrue();
    });
});
