<?php

declare(strict_types=1);

use App\Http\Resources\Admin\PermissionResource;
use App\Models\Permission;
use Illuminate\Http\Request;

describe('PermissionResource', function () {
    it('returns all expected keys', function () {
        $permission = Permission::firstOrCreate([
            'name' => 'resource.test',
            'guard_name' => 'web',
        ]);

        $array = (new PermissionResource($permission))->toArray(new Request());

        expect($array)->toHaveKeys([
            'id',
            'name',
            'guard_name',
            'type',
            'module',
            'created_at',
            'updated_at',
        ]);
    });

    it('maps name correctly', function () {
        $permission = Permission::firstOrCreate([
            'name' => 'admin.action',
            'guard_name' => 'web',
        ]);

        $array = (new PermissionResource($permission))->toArray(new Request());

        expect($array['name'])->toBe('admin.action');
    });

    it('maps type correctly', function () {
        $permission = Permission::create([
            'name' => 'menu.item',
            'guard_name' => 'web',
            'type' => 'menu',
        ]);

        $array = (new PermissionResource($permission))->toArray(new Request());

        expect($array['type'])->toBe('menu');
    });

    it('maps module correctly', function () {
        $permission = Permission::create([
            'name' => 'admin.feature',
            'guard_name' => 'web',
            'module' => 'admin',
        ]);

        $array = (new PermissionResource($permission))->toArray(new Request());

        expect($array['module'])->toBe('admin');
    });

    it('maps guard_name correctly', function () {
        $permission = Permission::firstOrCreate([
            'name' => 'guard.test',
            'guard_name' => 'web',
        ]);

        $array = (new PermissionResource($permission))->toArray(new Request());

        expect($array['guard_name'])->toBe('web');
    });
});
