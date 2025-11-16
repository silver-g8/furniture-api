<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class E2ETestSeeder extends Seeder
{
    /**
     * Seed data specifically for E2E tests.
     * This seeder creates predictable test data that E2E tests can rely on.
     */
    public function run(): void
    {
        // Create admin user for E2E tests
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign admin role if roles exist
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
            if (! $admin->hasRole('admin')) {
                $admin->assignRole($adminRole);
            }
        }

        // Create test categories
        $furniture = Category::firstOrCreate(
            ['slug' => 'furniture'],
            [
                'name' => 'Furniture',
                'is_active' => true,
            ]
        );

        $livingRoom = Category::firstOrCreate(
            ['slug' => 'living-room'],
            [
                'name' => 'Living Room',
                'parent_id' => $furniture->id,
                'is_active' => true,
            ]
        );

        $bedroom = Category::firstOrCreate(
            ['slug' => 'bedroom'],
            [
                'name' => 'Bedroom',
                'parent_id' => $furniture->id,
                'is_active' => true,
            ]
        );

        $diningRoom = Category::firstOrCreate(
            ['slug' => 'dining-room'],
            [
                'name' => 'Dining Room',
                'parent_id' => $furniture->id,
                'is_active' => true,
            ]
        );

        // Create test products with predictable data
        $products = [
            [
                'category_id' => $livingRoom->id,
                'sku' => 'E2E-SOFA-001',
                'name' => 'E2E Test Sofa',
                'price_tagged' => 15000.00,
                'status' => 'active',
            ],
            [
                'category_id' => $livingRoom->id,
                'sku' => 'E2E-TABLE-001',
                'name' => 'E2E Test Coffee Table',
                'price_tagged' => 5000.00,
                'status' => 'active',
            ],
            [
                'category_id' => $bedroom->id,
                'sku' => 'E2E-BED-001',
                'name' => 'E2E Test Bed',
                'price_tagged' => 25000.00,
                'status' => 'active',
            ],
            [
                'category_id' => $bedroom->id,
                'sku' => 'E2E-WARD-001',
                'name' => 'E2E Test Wardrobe',
                'price_tagged' => 18000.00,
                'status' => 'active',
            ],
            [
                'category_id' => $diningRoom->id,
                'sku' => 'E2E-DINING-001',
                'name' => 'E2E Test Dining Set',
                'price_tagged' => 12000.00,
                'status' => 'active',
            ],
            // Draft product for filter testing
            [
                'category_id' => $livingRoom->id,
                'sku' => 'E2E-CHAIR-001',
                'name' => 'E2E Test Chair (Draft)',
                'price_tagged' => 3000.00,
                'status' => 'draft',
            ],
            // Additional products for pagination testing (15+ products)
            [
                'category_id' => $livingRoom->id,
                'sku' => 'E2E-PROD-007',
                'name' => 'E2E Test Product 7',
                'price_tagged' => 1000.00,
                'status' => 'active',
            ],
            [
                'category_id' => $livingRoom->id,
                'sku' => 'E2E-PROD-008',
                'name' => 'E2E Test Product 8',
                'price_tagged' => 1100.00,
                'status' => 'active',
            ],
            [
                'category_id' => $bedroom->id,
                'sku' => 'E2E-PROD-009',
                'name' => 'E2E Test Product 9',
                'price_tagged' => 1200.00,
                'status' => 'active',
            ],
            [
                'category_id' => $bedroom->id,
                'sku' => 'E2E-PROD-010',
                'name' => 'E2E Test Product 10',
                'price_tagged' => 1300.00,
                'status' => 'active',
            ],
            [
                'category_id' => $diningRoom->id,
                'sku' => 'E2E-PROD-011',
                'name' => 'E2E Test Product 11',
                'price_tagged' => 1400.00,
                'status' => 'active',
            ],
            [
                'category_id' => $diningRoom->id,
                'sku' => 'E2E-PROD-012',
                'name' => 'E2E Test Product 12',
                'price_tagged' => 1500.00,
                'status' => 'active',
            ],
            [
                'category_id' => $livingRoom->id,
                'sku' => 'E2E-PROD-013',
                'name' => 'E2E Test Product 13',
                'price_tagged' => 1600.00,
                'status' => 'active',
            ],
            [
                'category_id' => $livingRoom->id,
                'sku' => 'E2E-PROD-014',
                'name' => 'E2E Test Product 14',
                'price_tagged' => 1700.00,
                'status' => 'active',
            ],
            [
                'category_id' => $bedroom->id,
                'sku' => 'E2E-PROD-015',
                'name' => 'E2E Test Product 15',
                'price_tagged' => 1800.00,
                'status' => 'active',
            ],
            [
                'category_id' => $bedroom->id,
                'sku' => 'E2E-PROD-016',
                'name' => 'E2E Test Product 16',
                'price_tagged' => 1900.00,
                'status' => 'active',
            ],
        ];

        foreach ($products as $productData) {
            Product::firstOrCreate(
                ['sku' => $productData['sku']],
                $productData
            );
        }

        $this->command->info('E2E test data seeded successfully!');
        $this->command->info('Admin credentials: admin@example.com / password');
        $this->command->info('Total products created: '.count($products));
    }
}
