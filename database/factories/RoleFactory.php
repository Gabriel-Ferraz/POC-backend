<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Spatie\Permission\Models\Role>
 */
class RoleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'guard_name' => 'web',
        ];
    }

    /**
     * Indicate that the role is super-admin.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'super-admin',
        ]);
    }

    /**
     * Indicate that the role is editor.
     */
    public function editor(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'editor',
        ]);
    }

    /**
     * Indicate that the role is viewer.
     */
    public function viewer(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'viewer',
        ]);
    }
}
