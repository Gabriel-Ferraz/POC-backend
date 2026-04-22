<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserSubscription>
 */
class UserSubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'plan_id' => Plan::factory(),
            'status' => fake()->randomElement(['active', 'inactive', 'cancelled', 'expired']),
            'started_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'expires_at' => now()->addDays(fake()->numberBetween(1, 365)),
        ];
    }

    /**
     * Indicate that the subscription is active and not expired.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'started_at' => now()->subDays(10),
            'expires_at' => now()->addDays(20),
        ]);
    }

    /**
     * Indicate that the subscription is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'started_at' => now()->subDays(40),
            'expires_at' => now()->subDays(5),
        ]);
    }

    /**
     * Indicate that the subscription is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'started_at' => now()->subDays(30),
            'expires_at' => now()->subDays(1),
        ]);
    }

    /**
     * Indicate that the subscription is lifetime (never expires).
     */
    public function lifetime(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'started_at' => now()->subDays(100),
            'expires_at' => null,
        ]);
    }

    /**
     * Indicate that the subscription is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
            'started_at' => now()->subDays(30),
            'expires_at' => now()->addDays(30),
        ]);
    }
}
