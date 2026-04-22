<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'slug' => fake()->unique()->slug(),
            'price' => fake()->randomFloat(2, 0, 999.99),
            'description' => fake()->sentence(),
            'features' => [
                'tts_requests' => fake()->numberBetween(100, 10000),
                'chat_requests' => fake()->numberBetween(50, 5000),
                'biometria_enabled' => fake()->boolean(),
                'voice_clone_enabled' => fake()->boolean(),
            ],
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the plan is the starter (free) plan.
     */
    public function starter(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Starter',
            'slug' => 'starter',
            'price' => 0.00,
            'description' => 'Plano gratuito para começar',
            'features' => [
                'tts_requests' => 100,
                'chat_requests' => 50,
                'biometria_enabled' => false,
                'voice_clone_enabled' => false,
            ],
        ]);
    }

    /**
     * Indicate that the plan is the basic plan.
     */
    public function basic(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Basic',
            'slug' => 'basic',
            'price' => 29.90,
            'description' => 'Plano básico com recursos essenciais',
            'features' => [
                'tts_requests' => 1000,
                'chat_requests' => 500,
                'biometria_enabled' => true,
                'voice_clone_enabled' => false,
            ],
        ]);
    }

    /**
     * Indicate that the plan is the premium plan.
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Premium',
            'slug' => 'premium',
            'price' => 99.90,
            'description' => 'Plano premium com todos os recursos',
            'features' => [
                'tts_requests' => 10000,
                'chat_requests' => 5000,
                'biometria_enabled' => true,
                'voice_clone_enabled' => true,
            ],
        ]);
    }

    /**
     * Indicate that the plan is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
