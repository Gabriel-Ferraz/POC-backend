<?php

declare(strict_types=1);

use App\Models\Plan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Database\Eloquent\Factories\HasFactory;

describe('UserSubscription model', function () {
    it('has correct fillable attributes', function () {
        $subscription = new UserSubscription();

        expect($subscription->getFillable())->toBe([
            'user_id',
            'plan_id',
            'status',
            'started_at',
            'expires_at',
        ]);
    });

    it('casts started_at as datetime', function () {
        $subscription = new UserSubscription();

        expect($subscription->getCasts()['started_at'])->toBe('datetime');
    });

    it('casts expires_at as datetime', function () {
        $subscription = new UserSubscription();

        expect($subscription->getCasts()['expires_at'])->toBe('datetime');
    });

    it('uses HasFactory trait', function () {
        expect(in_array(HasFactory::class, class_uses_recursive(UserSubscription::class)))->toBeTrue();
    });

    it('has user relationship (belongsTo)', function () {
        $subscription = new UserSubscription();

        expect($subscription->user())
            ->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    });

    it('has plan relationship (belongsTo)', function () {
        $subscription = new UserSubscription();

        expect($subscription->plan())
            ->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    });

    it('isActive returns true when status is active and not expired', function () {
        $subscription = UserSubscription::factory()->active()->create();

        expect($subscription->isActive())->toBeTrue();
    });

    it('isActive returns true when status is active and expires_at is null (lifetime)', function () {
        $subscription = UserSubscription::factory()->lifetime()->create();

        expect($subscription->isActive())->toBeTrue();
    });

    it('isActive returns false when status is not active', function () {
        $subscription = UserSubscription::factory()->create(['status' => 'inactive']);

        expect($subscription->isActive())->toBeFalse();
    });

    it('isActive returns false when subscription is expired', function () {
        $subscription = UserSubscription::factory()->expired()->create();

        expect($subscription->isActive())->toBeFalse();
    });

    it('isActive returns false when status is cancelled', function () {
        $subscription = UserSubscription::factory()->cancelled()->create();

        expect($subscription->isActive())->toBeFalse();
    });

    it('isActive returns false when status is inactive', function () {
        $subscription = UserSubscription::factory()->inactive()->create();

        expect($subscription->isActive())->toBeFalse();
    });

    it('active factory state creates active subscription', function () {
        $subscription = UserSubscription::factory()->active()->create();

        expect($subscription->status)->toBe('active')
            ->and($subscription->expires_at)->not->toBeNull()
            ->and($subscription->expires_at->isFuture())->toBeTrue();
    });

    it('expired factory state creates expired subscription', function () {
        $subscription = UserSubscription::factory()->expired()->create();

        expect($subscription->status)->toBe('active')
            ->and($subscription->expires_at)->not->toBeNull()
            ->and($subscription->expires_at->isPast())->toBeTrue();
    });

    it('lifetime factory state creates subscription without expiration', function () {
        $subscription = UserSubscription::factory()->lifetime()->create();

        expect($subscription->status)->toBe('active')
            ->and($subscription->expires_at)->toBeNull();
    });
});
