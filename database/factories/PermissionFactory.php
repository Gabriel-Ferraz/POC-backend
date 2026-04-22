<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Permission>
 */
class PermissionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Permission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word() . '.' . fake()->word(),
            'guard_name' => 'web',
            'type' => 'action',
            'module' => fake()->word(),
        ];
    }

    /**
     * Create an action permission for a specific resource and action.
     *
     * @param string $resource The resource name (e.g., 'users', 'roles')
     * @param string $action The action name (e.g., 'store', 'update', 'destroy')
     */
    public function action(string $resource, string $action): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => "admin.{$resource}.{$action}",
            'type' => 'action',
            'module' => $resource,
        ]);
    }

    /**
     * Create a menu permission for a specific menu name.
     *
     * @param string $menuName The menu name (e.g., 'users', 'settings')
     */
    public function menu(string $menuName): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => "menu.{$menuName}",
            'type' => 'menu',
            'module' => $menuName,
        ]);
    }
}
