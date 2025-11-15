<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Stock Test Data Seeder
 *
 * This seeder creates comprehensive test data for stock management testing:
 * - Warehouses (if not exists)
 * - Products (if not exists)
 * - Initial stocks (if not exists)
 * - Purchase orders and Goods Receipts (with stock IN movements)
 * - Sales Returns and Purchase Returns (with stock movements)
 *
 * Usage:
 *   php artisan db:seed --class=StockTestDataSeeder
 *
 * Or add to DatabaseSeeder.php:
 *   $this->call([
 *       StockTestDataSeeder::class,
 *   ]);
 */
class StockTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command?->info('Starting Stock Test Data Seeding...');
        $this->command?->newLine();

        // Step 1: Ensure basic data exists
        $this->command?->info('Step 1: Checking basic data (Warehouses, Suppliers, Products)...');

        $hasWarehouses = \App\Models\Warehouse::count() > 0;
        $hasSuppliers = \App\Models\Supplier::count() > 0;
        $hasProducts = \App\Models\Product::count() > 0;
        $hasCustomers = \App\Models\Customer::count() > 0;

        if (! $hasWarehouses) {
            $this->command?->info('  → Seeding Warehouses...');
            $this->call(WarehouseSeeder::class);
        } else {
            $this->command?->info('  ✓ Warehouses already exist');
        }

        if (! $hasSuppliers) {
            $this->command?->info('  → Seeding Suppliers...');
            $this->call(SupplierSeeder::class);
        } else {
            $this->command?->info('  ✓ Suppliers already exist');
        }

        if (! $hasProducts) {
            $this->command?->info('  → Seeding Products...');
            $this->call(ProductSeeder::class);
        } else {
            $this->command?->info('  ✓ Products already exist');
        }

        if (! $hasCustomers) {
            $this->command?->info('  → Seeding Customers...');
            $this->call(CustomerSeeder::class);
        } else {
            $this->command?->info('  ✓ Customers already exist');
        }

        $this->command?->newLine();

        // Step 2: Seed initial stocks (optional - only if stocks don't exist)
        $hasStocks = \App\Models\Stock::count() > 0;

        if (! $hasStocks) {
            $this->command?->info('Step 2: Seeding initial stocks...');
            $this->call(StockSeeder::class);
            $this->command?->info('  ✓ Initial stocks created');
        } else {
            $this->command?->info('Step 2: Skipping initial stocks (already exist)');
        }

        $this->command?->newLine();

        // Step 3: Create Purchase Orders and Goods Receipts (Stock IN)
        $this->command?->info('Step 3: Creating Purchase Orders and Goods Receipts...');
        $this->command?->info('  → This will create stock movements (type: IN) from Goods Receipts');
        try {
            $this->call(GoodsReceiptTestSeeder::class);
            $this->command?->info('  ✓ Goods Receipts created successfully');
        } catch (\Exception $e) {
            $this->command?->error("  ✗ Error creating Goods Receipts: {$e->getMessage()}");
        }

        $this->command?->newLine();

        // Step 4: Create Returns (Stock movements from Returns)
        $this->command?->info('Step 4: Creating Sales Returns and Purchase Returns...');
        $this->command?->info('  → Sales Returns will create stock movements (type: IN)');
        $this->command?->info('  → Purchase Returns will create stock movements (type: OUT)');
        try {
            $this->call(ReturnsTestSeeder::class);
            $this->command?->info('  ✓ Returns created successfully');
        } catch (\Exception $e) {
            $this->command?->error("  ✗ Error creating Returns: {$e->getMessage()}");
        }

        $this->command?->newLine();

        // Summary
        $this->command?->info('=== Stock Test Data Seeding Summary ===');
        $this->command?->newLine();

        $warehouseCount = \App\Models\Warehouse::where('is_active', true)->count();
        $productCount = \App\Models\Product::where('status', 'active')->count();
        $stockCount = \App\Models\Stock::count();
        $stockMovementCount = \App\Models\StockMovement::count();
        $goodsReceiptCount = \App\Models\GoodsReceipt::count();
        $salesReturnCount = \App\Models\SalesReturn::where('status', 'approved')->count();
        $purchaseReturnCount = \App\Models\PurchaseReturn::where('status', 'approved')->count();

        $this->command?->table(
            ['Metric', 'Count'],
            [
                ['Active Warehouses', (string) $warehouseCount],
                ['Active Products', (string) $productCount],
                ['Stock Records', (string) $stockCount],
                ['Stock Movements', (string) $stockMovementCount],
                ['Goods Receipts', (string) $goodsReceiptCount],
                ['Approved Sales Returns', (string) $salesReturnCount],
                ['Approved Purchase Returns', (string) $purchaseReturnCount],
            ]
        );

        $this->command?->newLine();
        $this->command?->info('Stock Test Data Seeding completed! 🎉');
        $this->command?->info('');
        $this->command?->info('You can now test:');
        $this->command?->info('  - Stock movements query: GET /api/v1/stock-movements');
        $this->command?->info('  - Stock by warehouse: GET /api/v1/stocks?warehouse_id=1');
        $this->command?->info('  - Warehouse stocks: GET /api/v1/warehouses/{id}/stocks');
    }
}

