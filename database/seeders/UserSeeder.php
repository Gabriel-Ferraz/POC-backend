<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@hugyc.io'],
            [
                'name' => 'Admin',
                'password' => 'password',
                'is_active' => true,
            ]
        );
    }
}
