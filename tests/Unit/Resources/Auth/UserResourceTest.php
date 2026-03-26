<?php

declare(strict_types=1);

use App\Http\Resources\Auth\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

describe('UserResource', function () {
    it('returns all expected keys', function () {
        $user = User::factory()->create();
        $user->load('roles', 'permissions');

        $array = (new UserResource($user))->toArray(new Request());

        expect($array)->toHaveKeys([
            'id',
            'name',
            'email',
            'is_active',
            'last_login_at',
            'roles',
            'permissions',
            'created_at',
            'updated_at',
        ]);
    });

    it('maps id correctly', function () {
        $user = User::factory()->create();

        $array = (new UserResource($user))->toArray(new Request());

        expect($array['id'])->toBe($user->id);
    });

    it('maps name correctly', function () {
        $user = User::factory()->create(['name' => 'Jane Doe']);

        $array = (new UserResource($user))->toArray(new Request());

        expect($array['name'])->toBe('Jane Doe');
    });

    it('maps email correctly', function () {
        $user = User::factory()->create(['email' => 'jane@example.com']);

        $array = (new UserResource($user))->toArray(new Request());

        expect($array['email'])->toBe('jane@example.com');
    });

    it('maps is_active as boolean', function () {
        $user = User::factory()->create(['is_active' => true]);

        $array = (new UserResource($user))->toArray(new Request());

        expect($array['is_active'])->toBeTrue();
    });

    it('roles is a collection from getRoleNames', function () {
        $user = User::factory()->create();

        $array = (new UserResource($user))->toArray(new Request());

        expect($array['roles'])->toBeInstanceOf(\Illuminate\Support\Collection::class);
    });

    it('permissions is a collection', function () {
        $user = User::factory()->create();

        $array = (new UserResource($user))->toArray(new Request());

        expect($array['permissions'])->toBeInstanceOf(\Illuminate\Support\Collection::class);
    });
});
