<?php

declare(strict_types=1);

use App\Models\Permission;
use Spatie\Permission\Models\Permission as SpatiePermission;

describe('Permission model', function () {
    it('extends Spatie Permission model', function () {
        expect(new Permission())->toBeInstanceOf(SpatiePermission::class);
    });

    it('has correct fillable attributes', function () {
        $permission = new Permission();

        expect($permission->getFillable())->toContain('name')
            ->and($permission->getFillable())->toContain('guard_name')
            ->and($permission->getFillable())->toContain('type')
            ->and($permission->getFillable())->toContain('module');
    });

    it('defaults type to action', function () {
        $permission = new Permission();

        expect($permission->type)->toBe('action');
    });

    it('persists type and module attributes', function () {
        $permission = Permission::create([
            'name' => 'test.permission',
            'guard_name' => 'web',
            'type' => 'menu',
            'module' => 'admin',
        ]);

        expect($permission->type)->toBe('menu')
            ->and($permission->module)->toBe('admin');
    });
});
