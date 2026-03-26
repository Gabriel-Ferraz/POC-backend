<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class ModulePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Admin — Users
            ['name' => 'admin.users', 'type' => 'page', 'module' => 'admin'],
            ['name' => 'admin.users.store', 'type' => 'action', 'module' => 'admin'],
            ['name' => 'admin.users.update', 'type' => 'action', 'module' => 'admin'],
            ['name' => 'admin.users.destroy', 'type' => 'action', 'module' => 'admin'],

            // Admin — Users > Roles
            ['name' => 'admin.users.roles', 'type' => 'page', 'module' => 'admin'],
            ['name' => 'admin.users.roles.update', 'type' => 'action', 'module' => 'admin'],

            // Admin — Users > Permissions
            ['name' => 'admin.users.permissions', 'type' => 'page', 'module' => 'admin'],
            ['name' => 'admin.users.permissions.update', 'type' => 'action', 'module' => 'admin'],

            // Admin — Roles
            ['name' => 'admin.roles', 'type' => 'page', 'module' => 'admin'],
            ['name' => 'admin.roles.store', 'type' => 'action', 'module' => 'admin'],
            ['name' => 'admin.roles.update', 'type' => 'action', 'module' => 'admin'],
            ['name' => 'admin.roles.destroy', 'type' => 'action', 'module' => 'admin'],

            // Admin — Roles > Permissions
            ['name' => 'admin.roles.permissions', 'type' => 'page', 'module' => 'admin'],
            ['name' => 'admin.roles.permissions.update', 'type' => 'action', 'module' => 'admin'],

            // Admin — Permissions
            ['name' => 'admin.permissions', 'type' => 'page', 'module' => 'admin'],

            // Admin — Audit Logs
            ['name' => 'admin.audit-logs', 'type' => 'page', 'module' => 'admin'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['type' => $permission['type'], 'module' => $permission['module']]
            );
        }
    }
}
