<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create roles first
        $this->call([
            RolesTableSeeder::class,
            PermissionsTableSeeder::class,
        ]);

        // Create test user and assign admin role
        $user = User::factory()->create([
            'name' => 'Test amin',
            'email' => 'admin@example.com',
        ]);

        // Attach admin role to the user
        $adminRole = \App\Models\Role::where('name', 'admin')->first();
        if ($adminRole) {
            $user->roles()->attach($adminRole->id);
        }

        $this->call([
            CategorySeeder::class,
            BrandSeeder::class,
            WarehouseSeeder::class,
            ProductSeeder::class,
            StockSeeder::class,
            E2ETestSeeder::class, // Add E2E test data
        ]);
    }
}
