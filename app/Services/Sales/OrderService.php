<?php

declare(strict_types=1);

namespace App\Services\Sales;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Calculate order totals from items.
     */
    public function calculateTotals(Order $order): void
    {
        $subtotal = 0;

        foreach ($order->items as $item) {
            $subtotal += $item->total;
        }

        $order->subtotal = $subtotal;
        $order->grand_total = $subtotal - $order->discount + $order->tax;
        $order->save();
    }

    /**
     * Recalculate item total and order totals.
     */
    public function recalculateItemTotal(OrderItem $item): void
    {
        $item->total = $item->calculateTotal();
        $item->save();

        $order = $item->order;
        if ($order) {
            $this->calculateTotals($order);
        }
    }

    /**
     * Confirm an order (lock it from further modifications).
     *
     * @throws \Exception
     */
    public function confirm(Order $order): Order
    {
        if (! $order->canBeConfirmed()) {
            throw new \Exception('Order cannot be confirmed. It must be in draft status and have at least one item.');
        }

        $order->status = 'confirmed';
        $order->save();

        return $order;
    }

    /**
     * Deliver an order and deduct stock.
     *
     * @throws \Exception
     */
    public function deliver(Order $order, int $warehouseId): Order
    {
        if (! $order->canBeDelivered()) {
            throw new \Exception('Order cannot be delivered. It must be in confirmed status.');
        }

        DB::beginTransaction();

        try {
            // Deduct stock for each item
            foreach ($order->items as $item) {
                $this->deductStock($item, $warehouseId);
            }

            $order->status = 'delivered';
            $order->save();

            DB::commit();

            return $order;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Deduct stock for an order item.
     *
     * @throws \Exception
     */
    protected function deductStock(OrderItem $item, int $warehouseId): void
    {
        $stock = Stock::where('product_id', $item->product_id)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->first();

        if (! $stock) {
            throw new \Exception("Stock not found for product ID {$item->product_id} in warehouse ID {$warehouseId}");
        }

        if ($stock->quantity < $item->qty) {
            throw new \Exception("Insufficient stock for product ID {$item->product_id}. Available: {$stock->quantity}, Required: {$item->qty}");
        }

        // Update stock quantity using decrement method for consistency
        $stock->decrement('quantity', $item->qty);

        // Create stock movement record
        $stock->movements()->create([
            'type' => 'out',
            'quantity' => $item->qty,
            'reference_type' => 'order',
            'reference_id' => $item->order_id,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Record a payment for an order.
     *
     * @param  array<string, mixed>  $paymentData
     *
     * @throws \Exception
     */
    public function recordPayment(Order $order, array $paymentData): Payment
    {
        if (! $order->canBePaid()) {
            throw new \Exception('Order cannot be paid. It must be in confirmed or delivered status.');
        }

        DB::beginTransaction();

        try {
            $payment = $order->payments()->create($paymentData);

            // Check if order is fully paid
            if ($order->isFullyPaid()) {
                $order->status = 'paid';
                $order->save();
            }

            DB::commit();

            return $payment;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate order items before confirmation.
     *
     * @throws \Exception
     */
    public function validateOrderItems(Order $order): void
    {
        if ($order->items()->count() === 0) {
            throw new \Exception('Order must have at least one item.');
        }

        foreach ($order->items as $item) {
            // Check if product exists and is active
            $product = Product::find($item->product_id);

            if (! $product) {
                throw new \Exception("Product ID {$item->product_id} not found.");
            }

            if ($product->status !== 'active') {
                throw new \Exception("Product '{$product->name}' is not active.");
            }

            // Validate quantities and prices
            if ($item->qty <= 0) {
                throw new \Exception("Quantity must be greater than 0 for product '{$product->name}'.");
            }

            if ($item->price < 0) {
                throw new \Exception("Price cannot be negative for product '{$product->name}'.");
            }

            if ($item->discount < 0) {
                throw new \Exception("Discount cannot be negative for product '{$product->name}'.");
            }
        }
    }
}
