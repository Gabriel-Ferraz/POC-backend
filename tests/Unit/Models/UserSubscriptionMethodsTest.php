<?php

declare(strict_types=1);

use App\Models\Plan;
use App\Models\User;
use App\Models\UserSubscription;

describe('User subscription methods', function () {
    it('hasActiveSubscription returns true when user has active subscription', function () {
        $user = User::factory()->create();
        UserSubscription::factory()->active()->create(['user_id' => $user->id]);

        expect($user->hasActiveSubscription())->toBeTrue();
    });

    it('hasActiveSubscription returns false when user has no subscription', function () {
        $user = User::factory()->create();

        expect($user->hasActiveSubscription())->toBeFalse();
    });

    it('hasActiveSubscription returns false when subscription is expired', function () {
        $user = User::factory()->create();
        UserSubscription::factory()->expired()->create(['user_id' => $user->id]);

        expect($user->hasActiveSubscription())->toBeFalse();
    });

    it('currentPlan returns Plan when user has active subscription', function () {
        $user = User::factory()->create();
        $plan = Plan::factory()->premium()->create();
        UserSubscription::factory()->active()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
        ]);

        $currentPlan = $user->currentPlan();

        expect($currentPlan)->toBeInstanceOf(Plan::class)
            ->and($currentPlan->id)->toBe($plan->id)
            ->and($currentPlan->slug)->toBe('premium');
    });

    it('currentPlan returns null when user has no subscription', function () {
        $user = User::factory()->create();

        expect($user->currentPlan())->toBeNull();
    });

    it('activeSubscription returns latest active subscription', function () {
        $user = User::factory()->create();
        $plan = Plan::factory()->starter()->create();

        // Criar subscription mais antiga
        $oldSubscription = UserSubscription::factory()->active()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'created_at' => now()->subDays(30),
        ]);

        // Aguardar 1 segundo para garantir diferença no timestamp
        sleep(1);

        // Criar subscription mais recente
        $latestSubscription = UserSubscription::factory()->active()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'created_at' => now(),
        ]);

        $active = $user->activeSubscription()->first();

        expect($active->id)->toBe($latestSubscription->id)
            ->and($active->id)->not->toBe($oldSubscription->id);
    });
});
