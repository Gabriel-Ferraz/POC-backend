<?php

declare(strict_types=1);

use App\Http\Resources\Admin\RoleResource;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

describe('RoleResource', function () {
    it('returns all expected keys', function () {
        $role = Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);

        $array = (new RoleResource($role))->toArray(new Request());

        expect($array)->toHaveKeys([
            'id',
            'name',
            'guard_name',
            'permissions',
            'created_at',
            'updated_at',
        ]);
    });

    it('maps name correctly', function () {
        $role = Role::firstOrCreate(['name' => 'tester', 'guard_name' => 'web']);

        $array = (new RoleResource($role))->toArray(new Request());

        expect($array['name'])->toBe('tester');
    });

    it('maps guard_name correctly', function () {
        $role = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);

        $array = (new RoleResource($role))->toArray(new Request());

        expect($array['guard_name'])->toBe('web');
    });

    it('permissions is a collection of permission names', function () {
        $role = Role::firstOrCreate(['name' => 'with-perms', 'guard_name' => 'web']);

        $array = (new RoleResource($role))->toArray(new Request());

        expect($array['permissions'])->toBeInstanceOf(\Illuminate\Support\Collection::class);
    });
});
