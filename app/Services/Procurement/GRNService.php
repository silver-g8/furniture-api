<?php

declare(strict_types=1);

namespace App\Services\Procurement;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Purchase;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class GRNService
{
    /**
     * Create a goods receipt and update stock.
     *
     * @param  array<string, mixed>  $data
     */
    public function receive(Purchase $purchase, array $data): GoodsReceipt
    {
        if (! $purchase->isApproved()) {
            throw new \RuntimeException('Cannot receive goods for non-approved purchase');
        }

        if ($purchase->goodsReceipt()->exists()) {
            throw new \RuntimeException('Goods already received for this purchase');
        }

        return DB::transaction(function () use ($purchase, $data) {
            // Create goods receipt
            $grn = GoodsReceipt::create([
                'purchase_id' => $purchase->id,
                'received_at' => $data['received_at'] ?? now(),
                'received_by' => auth()->id(),
                'notes' => $data['notes'] ?? null,
            ]);

            // Process each item
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $this->receiveItem($grn, $item);
                }
            }

            /** @var GoodsReceipt */
            return $grn->fresh(['items.product', 'items.warehouse', 'purchase']);
        });
    }

    /**
     * Receive a single item and update stock.
     *
     * @param  array<string, mixed>  $itemData
     */
    protected function receiveItem(GoodsReceipt $grn, array $itemData): GoodsReceiptItem
    {
        // Create GRN item
        $grnItem = $grn->items()->create([
            'product_id' => $itemData['product_id'],
            'warehouse_id' => $itemData['warehouse_id'],
            'qty' => $itemData['qty'],
            'remarks' => $itemData['remarks'] ?? null,
        ]);

        // Update stock - Find or create stock record
        $stock = Stock::firstOrCreate(
            [
                'warehouse_id' => $itemData['warehouse_id'],
                'product_id' => $itemData['product_id'],
            ],
            ['quantity' => 0]
        );

        // Increment stock quantity
        $stock->increment('quantity', $itemData['qty']);

        // Create stock movement record
        StockMovement::create([
            'stock_id' => $stock->id,
            'type' => 'in',
            'quantity' => $itemData['qty'],
            'reference_type' => GoodsReceipt::class,
            'reference_id' => $grn->id,
            'user_id' => auth()->id(),
        ]);

        return $grnItem;
    }

    /**
     * Get goods receipt with full details.
     */
    public function getGoodsReceipt(int $id): ?GoodsReceipt
    {
        return GoodsReceipt::with([
            'purchase.supplier',
            'purchase.items.product',
            'items.product',
            'items.warehouse',
            'receivedBy',
        ])->find($id);
    }
}
