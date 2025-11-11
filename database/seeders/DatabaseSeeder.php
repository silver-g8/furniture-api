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
        // Create test user
        User::factory()->create([
            'name' => 'Test amin',
            'email' => 'admin@example.com',
        ]);

        $this->call([
            RolesTableSeeder::class,
            PermissionsTableSeeder::class,
            CategorySeeder::class,
            BrandSeeder::class,
            WarehouseSeeder::class,
            ProductSeeder::class,
            StockSeeder::class,
            E2ETestSeeder::class, // Add E2E test data
        ]);
    }
}
