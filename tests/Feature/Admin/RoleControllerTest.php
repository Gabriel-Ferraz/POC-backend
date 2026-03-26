<?php

declare(strict_types=1);

use App\Models\{AuditLog, User};
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\{Permission, Role};

use function Pest\Laravel\{deleteJson, getJson, postJson, putJson};

it('returns 401 on roles index when unauthenticated', function () {
    getJson('/api/admin/roles')->assertUnauthorized();
});

describe('index', function () {
    beforeEach(function () {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        Sanctum::actingAs($user);
    });

    it('returns list of roles', function () {
        Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);

        getJson('/api/admin/roles')->assertOk();
    });
});

describe('store', function () {
    beforeEach(function () {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        Sanctum::actingAs($user);
        Permission::firstOrCreate(['name' => 'admin.roles.store', 'guard_name' => 'web']);
    });

    it('creates role and returns 201', function () {
        postJson('/api/admin/roles', ['name' => 'moderator'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'moderator');

        expect(Role::where('name', 'moderator')->exists())->toBeTrue();
    });

    it('creates audit log on store', function () {
        $before = AuditLog::count();

        postJson('/api/admin/roles', ['name' => 'reporter'])->assertCreated();

        expect(AuditLog::count())->toBeGreaterThan($before);
    });

    it('returns 422 for duplicate role name', function () {
        Role::firstOrCreate(['name' => 'existing-role', 'guard_name' => 'web']);

        postJson('/api/admin/roles', ['name' => 'existing-role'])->assertUnprocessable();
    });
});

describe('show', function () {
    beforeEach(function () {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        Sanctum::actingAs($user);
    });

    it('returns role resource', function () {
        $role = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);

        getJson("/api/admin/roles/{$role->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'viewer');
    });

    it('returns 404 for non-existent role', function () {
        getJson('/api/admin/roles/99999')->assertNotFound();
    });
});

describe('update', function () {
    beforeEach(function () {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        Sanctum::actingAs($user);
        Permission::firstOrCreate(['name' => 'admin.roles.update', 'guard_name' => 'web']);
    });

    it('updates role name', function () {
        $role = Role::firstOrCreate(['name' => 'old-name', 'guard_name' => 'web']);

        putJson("/api/admin/roles/{$role->id}", ['name' => 'new-name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'new-name');
    });

    it('returns 403 when updating super-admin role', function () {
        $superAdmin = Role::where('name', 'super-admin')->first();

        putJson("/api/admin/roles/{$superAdmin->id}", ['name' => 'hacked'])
            ->assertForbidden();
    });

    it('creates audit log on update', function () {
        $role = Role::firstOrCreate(['name' => 'updatable', 'guard_name' => 'web']);
        $before = AuditLog::count();

        putJson("/api/admin/roles/{$role->id}", ['name' => 'updated'])->assertOk();

        expect(AuditLog::count())->toBeGreaterThan($before);
    });
});

describe('destroy', function () {
    beforeEach(function () {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        Sanctum::actingAs($user);
        Permission::firstOrCreate(['name' => 'admin.roles.destroy', 'guard_name' => 'web']);
    });

    it('deletes role and returns 204', function () {
        $role = Role::firstOrCreate(['name' => 'deletable', 'guard_name' => 'web']);

        deleteJson("/api/admin/roles/{$role->id}")->assertNoContent();

        expect(Role::where('name', 'deletable')->exists())->toBeFalse();
    });

    it('returns 403 when deleting super-admin role', function () {
        $superAdmin = Role::where('name', 'super-admin')->first();

        deleteJson("/api/admin/roles/{$superAdmin->id}")->assertForbidden();
    });

    it('creates audit log on destroy', function () {
        $role = Role::firstOrCreate(['name' => 'to-delete', 'guard_name' => 'web']);
        $before = AuditLog::count();

        deleteJson("/api/admin/roles/{$role->id}")->assertNoContent();

        expect(AuditLog::count())->toBeGreaterThan($before);
    });
});

describe('syncPermissions', function () {
    beforeEach(function () {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        Sanctum::actingAs($user);
        Permission::firstOrCreate(['name' => 'admin.roles.update', 'guard_name' => 'web']);
    });

    it('syncs permissions on role', function () {
        $role = Role::firstOrCreate(['name' => 'sync-target', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'some.feature', 'guard_name' => 'web']);

        putJson("/api/admin/roles/{$role->id}/permissions", ['permissions' => ['some.feature']])
            ->assertOk();

        expect($role->fresh()->hasPermissionTo('some.feature'))->toBeTrue();
    });

    it('returns 403 for super-admin role', function () {
        $superAdmin = Role::where('name', 'super-admin')->first();
        Permission::firstOrCreate(['name' => 'extra.perm', 'guard_name' => 'web']);

        putJson("/api/admin/roles/{$superAdmin->id}/permissions", ['permissions' => ['extra.perm']])
            ->assertForbidden();
    });

    it('creates audit log on syncPermissions', function () {
        $role = Role::firstOrCreate(['name' => 'perm-sync', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'audit.perm', 'guard_name' => 'web']);
        $before = AuditLog::count();

        putJson("/api/admin/roles/{$role->id}/permissions", ['permissions' => ['audit.perm']])->assertOk();

        expect(AuditLog::count())->toBeGreaterThan($before);
    });
});
