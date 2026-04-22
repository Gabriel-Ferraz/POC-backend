<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

trait AuthenticationHelper
{
    /**
     * Create and authenticate a user with super-admin role.
     *
     * @return \App\Models\User
     */
    protected function actingAsSuperAdmin(): User
    {
        $role = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        Sanctum::actingAs($user);

        return $user;
    }

    /**
     * Create and authenticate a user with a specific role.
     *
     * @param string $roleName The name of the role
     * @return \App\Models\User
     */
    protected function actingAsRole(string $roleName): User
    {
        $role = Role::firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        Sanctum::actingAs($user);

        return $user;
    }

    /**
     * Create and authenticate a user with specific permissions.
     *
     * @param array<string> $permissions Array of permission names
     * @return \App\Models\User
     */
    protected function actingWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
            $user->givePermissionTo($permission);
        }

        Sanctum::actingAs($user);

        return $user;
    }

    /**
     * Create and authenticate a simple user without roles or permissions.
     *
     * @return \App\Models\User
     */
    protected function actingAsUser(): User
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        return $user;
    }
}
