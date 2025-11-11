<?php

declare(strict_types=1);

namespace App\Services\Procurement;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    /**
     * Create a new purchase with items.
     *
     * @param  array<string, mixed>  $data
     */
    public function createPurchase(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $purchase = Purchase::create([
                'supplier_id' => $data['supplier_id'],
                'status' => 'draft',
                'discount' => $data['discount'] ?? 0,
                'tax' => $data['tax'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]);

            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $this->addItem($purchase, $item);
                }
            }

            $purchase->calculateTotals();

            /** @var Purchase */
            return $purchase->fresh(['items', 'supplier']);
        });
    }

    /**
     * Update an existing purchase.
     *
     * @param  array<string, mixed>  $data
     */
    public function updatePurchase(Purchase $purchase, array $data): Purchase
    {
        if (! $purchase->canBeEdited()) {
            throw new \RuntimeException('Cannot edit an approved purchase');
        }

        return DB::transaction(function () use ($purchase, $data) {
            $purchase->update([
                'supplier_id' => $data['supplier_id'] ?? $purchase->supplier_id,
                'discount' => $data['discount'] ?? $purchase->discount,
                'tax' => $data['tax'] ?? $purchase->tax,
                'notes' => $data['notes'] ?? $purchase->notes,
            ]);

            if (isset($data['items']) && is_array($data['items'])) {
                // Remove existing items
                $purchase->items()->delete();

                // Add new items
                foreach ($data['items'] as $item) {
                    $this->addItem($purchase, $item);
                }
            }

            $purchase->calculateTotals();

            /** @var Purchase */
            return $purchase->fresh(['items', 'supplier']);
        });
    }

    /**
     * Add an item to a purchase.
     *
     * @param  array<string, mixed>  $itemData
     */
    public function addItem(Purchase $purchase, array $itemData): PurchaseItem
    {
        return $purchase->items()->create([
            'product_id' => $itemData['product_id'],
            'qty' => $itemData['qty'],
            'price' => $itemData['price'],
            'discount' => $itemData['discount'] ?? 0,
        ]);
    }

    /**
     * Approve a purchase.
     */
    public function approve(Purchase $purchase): Purchase
    {
        if (! $purchase->canBeApproved()) {
            throw new \RuntimeException('Purchase cannot be approved');
        }

        $purchase->approve();

        /** @var Purchase */
        return $purchase->fresh(['items', 'supplier']);
    }

    /**
     * Calculate totals for a purchase.
     */
    public function calculateTotals(Purchase $purchase): void
    {
        $purchase->calculateTotals();
    }
}
