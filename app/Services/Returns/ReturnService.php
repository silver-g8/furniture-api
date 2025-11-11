<?php

declare(strict_types=1);

namespace App\Services\Returns;

use App\Models\Order;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\SalesReturn;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    /**
     * Approve a sales return and adjust stock (IN).
     *
     * @throws \Exception
     */
    public function approveSalesReturn(SalesReturn $salesReturn): SalesReturn
    {
        if (! $salesReturn->canBeApproved()) {
            throw new \Exception('Sales return cannot be approved in current status');
        }

        // Validate order exists and status
        $order = $salesReturn->order;
        if (! $order) {
            throw new \Exception('Order not found');
        }

        if (! in_array($order->status, ['delivered', 'paid'])) {
            throw new \Exception('Can only return from delivered or paid orders');
        }

        DB::beginTransaction();

        try {
            // Capture before state for audit
            $beforeState = $salesReturn->snapshot(['id', 'status', 'total']);

            // Validate return quantities don't exceed order quantities
            foreach ($salesReturn->items as $item) {
                $orderItem = $order->items()->where('product_id', $item->product_id)->first();

                if (! $orderItem) {
                    throw new \Exception("Product {$item->product_id} not found in order");
                }

                $orderItemQty = (int) $orderItem->qty;

                // Calculate total returned quantity for this product
                $totalReturned = SalesReturn::where('order_id', $order->id)
                    ->where('status', 'approved')
                    ->whereHas('items', function ($query) use ($item) {
                        $query->where('product_id', $item->product_id);
                    })
                    ->get()
                    ->flatMap->items
                    ->where('product_id', $item->product_id)
                    ->sum('quantity');

                if (($totalReturned + $item->quantity) > $orderItemQty) {
                    throw new \Exception("Return quantity exceeds order quantity for product {$item->product_id}");
                }
            }

            // Approve the return
            $salesReturn->approve();

            // Log audit event
            $salesReturn->auditApproved(
                $beforeState,
                $salesReturn->snapshot(['id', 'status', 'total']),
                ['reason' => $salesReturn->reason]
            );

            // Adjust stock (IN) for each item
            foreach ($salesReturn->items as $item) {
                $this->adjustStockIn(
                    $salesReturn->warehouse_id,
                    $item->product_id,
                    $item->quantity,
                    SalesReturn::class,
                    $salesReturn->id
                );
            }

            DB::commit();

            $freshReturn = $salesReturn->fresh(['items.product', 'order', 'warehouse']);
            if (! $freshReturn) {
                throw new \Exception('Failed to refresh sales return');
            }

            return $freshReturn;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Approve a purchase return and adjust stock (OUT).
     *
     * @throws \Exception
     */
    public function approvePurchaseReturn(PurchaseReturn $purchaseReturn): PurchaseReturn
    {
        if (! $purchaseReturn->canBeApproved()) {
            throw new \Exception('Purchase return cannot be approved in current status');
        }

        // Validate purchase exists and has been received
        $purchase = $purchaseReturn->purchase;
        if (! $purchase) {
            throw new \Exception('Purchase not found');
        }

        if (! $purchase->goodsReceipt) {
            throw new \Exception('Can only return from received purchases');
        }

        DB::beginTransaction();

        try {
            // Capture before state for audit
            $beforeState = $purchaseReturn->snapshot(['id', 'status', 'total']);

            // Validate return quantities don't exceed received quantities
            foreach ($purchaseReturn->items as $item) {
                $purchaseItem = $purchase->items()->where('product_id', $item->product_id)->first();

                if (! $purchaseItem) {
                    throw new \Exception("Product {$item->product_id} not found in purchase");
                }

                $purchaseItemQty = (int) $purchaseItem->qty;

                // Calculate total returned quantity for this product
                $totalReturned = PurchaseReturn::where('purchase_id', $purchase->id)
                    ->where('status', 'approved')
                    ->whereHas('items', function ($query) use ($item) {
                        $query->where('product_id', $item->product_id);
                    })
                    ->get()
                    ->flatMap->items
                    ->where('product_id', $item->product_id)
                    ->sum('quantity');

                if (($totalReturned + $item->quantity) > $purchaseItemQty) {
                    throw new \Exception("Return quantity exceeds purchase quantity for product {$item->product_id}");
                }

                // Check if sufficient stock available
                $stock = Stock::where('warehouse_id', $purchaseReturn->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->first();

                if (! $stock || $stock->quantity < $item->quantity) {
                    throw new \Exception("Insufficient stock for product {$item->product_id}");
                }
            }

            // Approve the return
            $purchaseReturn->approve();

            // Log audit event
            $purchaseReturn->auditApproved(
                $beforeState,
                $purchaseReturn->snapshot(['id', 'status', 'total']),
                ['reason' => $purchaseReturn->reason]
            );

            // Adjust stock (OUT) for each item
            foreach ($purchaseReturn->items as $item) {
                $this->adjustStockOut(
                    $purchaseReturn->warehouse_id,
                    $item->product_id,
                    $item->quantity,
                    PurchaseReturn::class,
                    $purchaseReturn->id
                );
            }

            DB::commit();

            $freshReturn = $purchaseReturn->fresh(['items.product', 'purchase', 'warehouse']);
            if (! $freshReturn) {
                throw new \Exception('Failed to refresh purchase return');
            }

            return $freshReturn;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Adjust stock IN (increase quantity).
     */
    private function adjustStockIn(
        int $warehouseId,
        int $productId,
        int $quantity,
        string $referenceType,
        int $referenceId
    ): void {
        // Find or create stock record
        $stock = Stock::firstOrCreate(
            [
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
            ],
            ['quantity' => 0]
        );

        // Update stock quantity
        $stock->increment('quantity', $quantity);

        // Create movement record
        StockMovement::create([
            'stock_id' => $stock->id,
            'type' => 'in',
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Adjust stock OUT (decrease quantity).
     */
    private function adjustStockOut(
        int $warehouseId,
        int $productId,
        int $quantity,
        string $referenceType,
        int $referenceId
    ): void {
        // Find stock record
        $stock = Stock::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->firstOrFail();

        // Update stock quantity
        $stock->decrement('quantity', $quantity);

        // Create movement record
        StockMovement::create([
            'stock_id' => $stock->id,
            'type' => 'out',
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'user_id' => auth()->id(),
        ]);
    }
}
