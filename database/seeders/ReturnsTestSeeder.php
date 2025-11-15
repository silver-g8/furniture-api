<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\Warehouse;
use App\Services\Returns\ReturnService;
use Illuminate\Database\Seeder;

class ReturnsTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $returnService = app(ReturnService::class);

        // Get warehouses
        $warehouses = Warehouse::where('is_active', true)->get();
        if ($warehouses->isEmpty()) {
            $this->command?->warn('No active warehouses found. Please run WarehouseSeeder first.');
            return;
        }

        // Get products
        $products = Product::where('status', 'active')->get();
        if ($products->isEmpty()) {
            $this->command?->warn('No active products found. Please run ProductSeeder first.');
            return;
        }

        // ============================================
        // PART 1: Create Sales Returns
        // ============================================

        // Get customers
        $customers = Customer::where('is_active', true)->take(10)->get();
        if ($customers->isEmpty()) {
            $this->command?->warn('No active customers found. Please run CustomerSeeder first.');
        }

        // Get orders with status 'delivered' or 'paid' that have items
        // If no orders exist, create some test orders first
        $deliveredOrders = Order::whereIn('status', ['delivered', 'paid'])
            ->has('items')
            ->get();

        if ($deliveredOrders->isEmpty() && ! $customers->isEmpty()) {
            $this->command?->info('No delivered orders found. Creating test orders first...');

            // Create 3-5 test orders with delivered/paid status
            for ($i = 1; $i <= 5; $i++) {
                $customer = $customers->random();
                $warehouse = $warehouses->random();

                $order = Order::create([
                    'customer_id' => $customer->id,
                    'status' => rand(0, 1) ? 'delivered' : 'paid',
                    'subtotal' => 0,
                    'discount' => 0,
                    'tax' => 0,
                    'grand_total' => 0,
                    'notes' => "Test Order #{$i} for Returns Testing",
                ]);

                // Add 2-3 items to order
                $itemCount = rand(2, 3);
                $selectedProducts = $products->random(min($itemCount, $products->count()));
                $subtotal = 0;

                foreach ($selectedProducts as $product) {
                    $qty = rand(1, 5);
                    $price = $product->price;
                    $discount = rand(0, 500);
                    $total = ($qty * $price) - $discount;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'qty' => $qty,
                        'price' => $price,
                        'discount' => $discount,
                        'total' => $total,
                    ]);

                    $subtotal += $total;
                }

                $tax = $subtotal * 0.07;
                $grandTotal = $subtotal + $tax;

                $order->update([
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'grand_total' => $grandTotal,
                ]);
            }

            $deliveredOrders = Order::whereIn('status', ['delivered', 'paid'])
                ->has('items')
                ->get();
        }

        // Create Sales Returns from delivered/paid orders
        $salesReturnCount = 0;
        $ordersForReturns = $deliveredOrders->random(min(3, $deliveredOrders->count()));

        foreach ($ordersForReturns as $order) {
            try {
                $warehouse = $warehouses->random();

                // Create sales return
                $salesReturn = SalesReturn::create([
                    'order_id' => $order->id,
                    'warehouse_id' => $warehouse->id,
                    'returned_at' => null,
                    'reason' => 'Product defect / Customer request / Wrong item',
                    'status' => 'draft',
                    'total' => 0,
                    'notes' => "Test Sales Return for Order #{$order->id}",
                ]);

                // Add return items (return 20-50% of order items)
                $orderItems = $order->items;
                $itemsToReturn = $orderItems->random(max(1, (int) ceil($orderItems->count() * 0.3)));

                $total = 0;
                foreach ($itemsToReturn as $orderItem) {
                    // Return 30-80% of ordered quantity
                    $returnQty = (int) max(1, ceil($orderItem->qty * (0.3 + (rand(0, 50) / 100))));
                    $price = $orderItem->price;

                    SalesReturnItem::create([
                        'sales_return_id' => $salesReturn->id,
                        'product_id' => $orderItem->product_id,
                        'quantity' => $returnQty,
                        'price' => $price,
                        'remark' => "Returning {$returnQty} units from Order #{$order->id}",
                    ]);

                    $total += $returnQty * $price;
                }

                $salesReturn->update(['total' => $total]);

                // Approve sales return (will create stock movements)
                $approvedReturn = $returnService->approveSalesReturn($salesReturn);

                $salesReturnCount++;
                $this->command?->info("Created and approved Sales Return #{$approvedReturn->id} for Order #{$order->id}");
            } catch (\Exception $e) {
                $this->command?->error("Failed to create Sales Return for Order #{$order->id}: {$e->getMessage()}");
            }
        }

        $this->command?->info("Created {$salesReturnCount} Sales Returns with stock movements (IN)");

        // ============================================
        // PART 2: Create Purchase Returns
        // ============================================

        // Get purchases that have goods receipts (already received)
        $purchasesWithGRN = Purchase::whereHas('goodsReceipt')
            ->has('items')
            ->get();

        if ($purchasesWithGRN->isEmpty()) {
            $this->command?->warn('No purchases with goods receipts found. Please run GoodsReceiptTestSeeder first.');
            return;
        }

        // Create Purchase Returns from purchases with GRN
        $purchaseReturnCount = 0;
        $purchasesForReturns = $purchasesWithGRN->random(min(2, $purchasesWithGRN->count()));

        foreach ($purchasesForReturns as $purchase) {
            try {
                // Get warehouse from goods receipt items
                $grnItems = $purchase->goodsReceipt->items;
                if ($grnItems->isEmpty()) {
                    continue;
                }

                $warehouse = $grnItems->first()->warehouse;

                // Check if we have stock to return
                $hasStock = false;
                foreach ($purchase->items as $purchaseItem) {
                    $stock = \App\Models\Stock::where('warehouse_id', $warehouse->id)
                        ->where('product_id', $purchaseItem->product_id)
                        ->first();

                    if ($stock && $stock->quantity > 0) {
                        $hasStock = true;
                        break;
                    }
                }

                if (! $hasStock) {
                    $this->command?->warn("Skipping Purchase Return for Purchase #{$purchase->id} - No stock available");
                    continue;
                }

                // Create purchase return
                $purchaseReturn = PurchaseReturn::create([
                    'purchase_id' => $purchase->id,
                    'warehouse_id' => $warehouse->id,
                    'returned_at' => null,
                    'reason' => 'Defective items / Wrong items / Supplier error',
                    'status' => 'draft',
                    'total' => 0,
                    'notes' => "Test Purchase Return for Purchase #{$purchase->id}",
                ]);

                // Add return items (return 10-30% of purchase items that have stock)
                $purchaseItems = $purchase->items;
                $itemsToReturn = collect([]);

                foreach ($purchaseItems as $purchaseItem) {
                    $stock = \App\Models\Stock::where('warehouse_id', $warehouse->id)
                        ->where('product_id', $purchaseItem->product_id)
                        ->first();

                    if ($stock && $stock->quantity > 0) {
                        // Return 10-30% of available stock, but not more than received quantity
                        $maxReturn = min($stock->quantity, $purchaseItem->qty);
                        $returnQty = (int) max(1, ceil($maxReturn * (0.1 + (rand(0, 20) / 100))));

                        if ($returnQty > 0 && rand(0, 1)) { // 50% chance to include this item
                            $itemsToReturn->push([
                                'purchaseItem' => $purchaseItem,
                                'quantity' => $returnQty,
                            ]);
                        }
                    }
                }

                if ($itemsToReturn->isEmpty()) {
                    $purchaseReturn->delete();
                    continue;
                }

                $total = 0;
                foreach ($itemsToReturn as $itemData) {
                    $purchaseItem = $itemData['purchaseItem'];
                    $returnQty = $itemData['quantity'];
                    $price = $purchaseItem->price;

                    PurchaseReturnItem::create([
                        'purchase_return_id' => $purchaseReturn->id,
                        'product_id' => $purchaseItem->product_id,
                        'quantity' => $returnQty,
                        'price' => $price,
                        'remark' => "Returning {$returnQty} units to supplier",
                    ]);

                    $total += $returnQty * $price;
                }

                $purchaseReturn->update(['total' => $total]);

                // Approve purchase return (will create stock movements)
                $approvedReturn = $returnService->approvePurchaseReturn($purchaseReturn);

                $purchaseReturnCount++;
                $this->command?->info("Created and approved Purchase Return #{$approvedReturn->id} for Purchase #{$purchase->id}");
            } catch (\Exception $e) {
                $this->command?->error("Failed to create Purchase Return for Purchase #{$purchase->id}: {$e->getMessage()}");
            }
        }

        $this->command?->info("Created {$purchaseReturnCount} Purchase Returns with stock movements (OUT)");

        $totalReturns = $salesReturnCount + $purchaseReturnCount;
        $this->command?->info("Total: {$totalReturns} Returns created successfully");
    }
}

