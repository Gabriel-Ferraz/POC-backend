<?php

declare(strict_types=1);

use App\Models\Plan;
use App\Models\UserSubscription;
use Illuminate\Database\Eloquent\Factories\HasFactory;

describe('Plan model', function () {
    it('has correct fillable attributes', function () {
        $plan = new Plan();

        expect($plan->getFillable())->toBe([
            'name',
            'slug',
            'price',
            'description',
            'features',
            'is_active',
        ]);
    });

    it('casts price as decimal with 2 places', function () {
        $plan = new Plan();

        expect($plan->getCasts()['price'])->toBe('decimal:2');
    });

    it('casts features as array', function () {
        $plan = new Plan();

        expect($plan->getCasts()['features'])->toBe('array');
    });

    it('casts is_active as boolean', function () {
        $plan = new Plan();

        expect($plan->getCasts()['is_active'])->toBe('boolean');
    });

    it('uses HasFactory trait', function () {
        expect(in_array(HasFactory::class, class_uses_recursive(Plan::class)))->toBeTrue();
    });

    it('has subscriptions relationship (hasMany)', function () {
        $plan = new Plan();

        expect($plan->subscriptions())
            ->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasMany::class);
    });

    it('has users relationship (belongsToMany)', function () {
        $plan = new Plan();

        expect($plan->users())
            ->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    });

    it('is_active defaults to true', function () {
        $plan = Plan::factory()->create();

        expect($plan->is_active)->toBeTrue();
    });

    it('inactive factory state sets is_active to false', function () {
        $plan = Plan::factory()->inactive()->create();

        expect($plan->is_active)->toBeFalse();
    });

    it('starter factory creates plan with price 0.00', function () {
        $plan = Plan::factory()->starter()->create();

        expect($plan->slug)->toBe('starter')
            ->and($plan->price)->toBe('0.00')
            ->and($plan->features['tts_requests'])->toBe(100)
            ->and($plan->features['biometria_enabled'])->toBeFalse();
    });

    it('basic factory creates plan with correct features', function () {
        $plan = Plan::factory()->basic()->create();

        expect($plan->slug)->toBe('basic')
            ->and($plan->price)->toBe('29.90')
            ->and($plan->features['biometria_enabled'])->toBeTrue();
    });

    it('premium factory creates plan with all features enabled', function () {
        $plan = Plan::factory()->premium()->create();

        expect($plan->slug)->toBe('premium')
            ->and($plan->price)->toBe('99.90')
            ->and($plan->features['biometria_enabled'])->toBeTrue()
            ->and($plan->features['voice_clone_enabled'])->toBeTrue();
    });
});
