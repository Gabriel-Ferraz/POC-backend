<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

describe('User model', function () {
    it('has correct fillable attributes', function () {
        $user = new User();

        expect($user->getFillable())->toBe([
            'name',
            'email',
            'password',
            'is_active',
            'last_login_at',
        ]);
    });

    it('hides password and remember_token', function () {
        $user = new User();

        expect($user->getHidden())->toContain('password')
            ->and($user->getHidden())->toContain('remember_token');
    });

    it('casts email_verified_at as datetime', function () {
        $user = new User();

        expect($user->getCasts()['email_verified_at'])->toBe('datetime');
    });

    it('casts password as hashed', function () {
        $user = new User();

        expect($user->getCasts()['password'])->toBe('hashed');
    });

    it('casts is_active as boolean', function () {
        $user = new User();

        expect($user->getCasts()['is_active'])->toBe('boolean');
    });

    it('casts last_login_at as datetime', function () {
        $user = new User();

        expect($user->getCasts()['last_login_at'])->toBe('datetime');
    });

    it('uses SoftDeletes trait', function () {
        expect(in_array(SoftDeletes::class, class_uses_recursive(User::class)))->toBeTrue();
    });

    it('uses HasRoles trait from Spatie', function () {
        expect(in_array(HasRoles::class, class_uses_recursive(User::class)))->toBeTrue();
    });

    it('uses HasApiTokens trait from Sanctum', function () {
        expect(in_array(HasApiTokens::class, class_uses_recursive(User::class)))->toBeTrue();
    });

    it('is_active defaults to true', function () {
        $user = User::factory()->create();

        expect($user->is_active)->toBeTrue();
    });

    it('inactive factory state sets is_active to false', function () {
        $user = User::factory()->inactive()->create();

        expect($user->is_active)->toBeFalse();
    });
});
