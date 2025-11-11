<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stockMatrix = [
            'ALPHA-SOFA-001' => [
                'WH-MAIN' => 14,
                'WH-BKK' => 6,
            ],
            'ALPHA-SOFA-002' => [
                'WH-MAIN' => 4,
            ],
            'ALPHA-TABLE-001' => [
                'WH-MAIN' => 12,
                'WH-CNX' => 5,
            ],
            'ALPHA-TV-001' => [
                'WH-MAIN' => 7,
            ],
            'BETA-BED-001' => [
                'WH-BKK' => 3,
                'WH-CNX' => 2,
            ],
            'BETA-WARD-001' => [
                'WH-MAIN' => 5,
                'WH-BKK' => 2,
            ],
            'CRAFT-DINE-001' => [
                'WH-MAIN' => 9,
                'WH-CNX' => 4,
            ],
            'CRAFT-BAR-001' => [
                'WH-OLD' => 2,
            ],
            'NORD-LIGHT-001' => [
                'WH-MAIN' => 15,
                'WH-BKK' => 8,
            ],
            'NORD-LAMP-001' => [
                'WH-MAIN' => 10,
            ],
            'LIGHT-FLOOR-001' => [
                'WH-MAIN' => 5,
                'WH-BKK' => 2,
            ],
            'URBN-PATIO-001' => [
                'WH-OLD' => 3,
            ],
            'URBN-GARDEN-001' => [
                'WH-MAIN' => 8,
                'WH-HKT' => 4,
            ],
            'DECOR-MIRROR-001' => [
                'WH-MAIN' => 11,
                'WH-BKK' => 5,
            ],
        ];

        foreach ($stockMatrix as $sku => $warehouseQuantities) {
            $product = Product::where('sku', $sku)->first();

            if (! $product) {
                $this->command?->warn(sprintf('Skip seeding stock: product with SKU %s not found', $sku));

                continue;
            }

            foreach ($warehouseQuantities as $warehouseCode => $quantity) {
                $warehouse = Warehouse::where('code', $warehouseCode)->first();

                if (! $warehouse) {
                    $this->command?->warn(sprintf('Skip seeding stock for %s: warehouse %s not found', $sku, $warehouseCode));

                    continue;
                }

                $stock = Stock::updateOrCreate(
                    [
                        'warehouse_id' => $warehouse->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'quantity' => $quantity,
                    ]
                );

                StockMovement::updateOrCreate(
                    [
                        'stock_id' => $stock->id,
                        'type' => 'in',
                        'reference_type' => 'SEEDER_INIT',
                        'reference_id' => $stock->id,
                    ],
                    [
                        'quantity' => $quantity,
                        'user_id' => null,
                    ]
                );
            }
        }
    }
}
