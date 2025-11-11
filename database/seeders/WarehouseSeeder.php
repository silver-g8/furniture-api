<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = [
            [
                'code' => 'WH-MAIN',
                'name' => 'Main Warehouse',
                'is_active' => true,
            ],
            [
                'code' => 'WH-BKK',
                'name' => 'Bangkok Warehouse',
                'is_active' => true,
            ],
            [
                'code' => 'WH-CNX',
                'name' => 'Chiang Mai Warehouse',
                'is_active' => true,
            ],
            [
                'code' => 'WH-HKT',
                'name' => 'Phuket Warehouse',
                'is_active' => true,
            ],
            [
                'code' => 'WH-OLD',
                'name' => 'Old Warehouse (Inactive)',
                'is_active' => false,
            ],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::updateOrCreate(
                ['code' => $warehouse['code']],
                [
                    'name' => $warehouse['name'],
                    'is_active' => $warehouse['is_active'],
                ]
            );
        }
    }
}
