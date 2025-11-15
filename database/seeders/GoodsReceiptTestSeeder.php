<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Procurement\GRNService;
use Illuminate\Database\Seeder;

class GoodsReceiptTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $grnService = app(GRNService::class);

        // Get or create test user for receiving goods
        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
            ]
        );

        // Ensure we're authenticated as this user for GRNService
        auth()->login($user);

        // Get suppliers
        $suppliers = Supplier::where('is_active', true)->get();
        if ($suppliers->isEmpty()) {
            $this->command?->warn('No active suppliers found. Please run SupplierSeeder first.');
            return;
        }

        // Get warehouses
        $warehouses = Warehouse::where('is_active', true)->get();
        if ($warehouses->isEmpty()) {
            $this->command?->warn('No active warehouses found. Please run WarehouseSeeder first.');
            return;
        }

        // Get products
        $products = Product::where('status', 'active')->take(15)->get();
        if ($products->isEmpty()) {
            $this->command?->warn('No active products found. Please run ProductSeeder first.');
            return;
        }

        // Create 5-8 Purchase Orders
        $purchases = [];
        $supplierIndex = 0;

        for ($i = 1; $i <= 8; $i++) {
            $supplier = $suppliers[$supplierIndex % $suppliers->count()];
            $supplierIndex++;

            // Create purchase order
            $purchase = Purchase::create([
                'supplier_id' => $supplier->id,
                'status' => 'draft',
                'subtotal' => 0,
                'discount' => 0,
                'tax' => 0,
                'grand_total' => 0,
                'notes' => "Test Purchase Order #{$i} - Auto generated for testing",
            ]);

            // Add 2-4 items to each purchase
            $itemCount = rand(2, 4);
            $selectedProducts = $products->random(min($itemCount, $products->count()));
            $subtotal = 0;

            foreach ($selectedProducts as $product) {
                $qty = rand(5, 20);
                $price = $product->price * (0.7 + (rand(0, 30) / 100)); // 70-100% of product price
                $discount = rand(0, 500); // Random discount 0-500
                $total = ($qty * $price) - $discount;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'price' => $price,
                    'discount' => $discount,
                    'total' => $total,
                ]);

                $subtotal += $total;
            }

            // Approve purchase
            $tax = $subtotal * 0.07; // 7% VAT
            $grandTotal = $subtotal + $tax;

            $purchase->update([
                'status' => 'approved',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'grand_total' => $grandTotal,
            ]);

            $purchases[] = $purchase;
        }

        $purchaseCount = count($purchases);
        $this->command?->info("Created {$purchaseCount} Purchase Orders");

        // Create Goods Receipts for 70-80% of purchases
        $receiptCount = (int) ceil($purchaseCount * 0.75);
        $purchasesToReceive = collect($purchases)->random(min($receiptCount, $purchaseCount));

        $grnCount = 0;
        foreach ($purchasesToReceive as $purchase) {
            try {
                // Select warehouse for receiving (random from active warehouses)
                $warehouse = $warehouses->random();

                // Create items for goods receipt based on purchase items
                $items = [];
                foreach ($purchase->items as $purchaseItem) {
                    // Sometimes receive less than ordered (90-100% of ordered qty)
                    $receivedQty = (int) ceil($purchaseItem->qty * (0.9 + (rand(0, 10) / 100)));

                    $items[] = [
                        'product_id' => $purchaseItem->product_id,
                        'warehouse_id' => $warehouse->id,
                        'qty' => $receivedQty,
                        'remarks' => "Received for Purchase #{$purchase->id}",
                    ];
                }

                // Create goods receipt using GRNService (will update stock automatically)
                $receivedAt = now()->subDays(rand(1, 30)); // Random date in last 30 days

                $grn = $grnService->receive($purchase, [
                    'received_at' => $receivedAt,
                    'received_by' => $user->id,
                    'items' => $items,
                    'notes' => "Goods receipt for Purchase Order #{$purchase->id} - Test data",
                ]);

                $grnCount++;
                $this->command?->info("Created Goods Receipt #{$grn->id} for Purchase #{$purchase->id}");
            } catch (\Exception $e) {
                $this->command?->error("Failed to create Goods Receipt for Purchase #{$purchase->id}: {$e->getMessage()}");
            }
        }

        $this->command?->info("Created {$grnCount} Goods Receipts with stock movements");

        // Logout after seeding
        auth()->logout();
    }
}

