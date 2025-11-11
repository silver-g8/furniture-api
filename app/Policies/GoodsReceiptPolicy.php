<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GoodsReceipt;
use App\Models\User;

class GoodsReceiptPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Allow admin, manager, and staff to view goods receipts
        return $user->roles()->whereIn('name', ['admin', 'manager', 'staff'])->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, GoodsReceipt $goodsReceipt): bool
    {
        // Allow admin, manager, and staff to view goods receipt details
        return $user->roles()->whereIn('name', ['admin', 'manager', 'staff'])->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only admin, manager, and staff can receive goods
        return $user->roles()->whereIn('name', ['admin', 'manager', 'staff'])->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, GoodsReceipt $goodsReceipt): bool
    {
        // Goods receipts cannot be updated once created
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, GoodsReceipt $goodsReceipt): bool
    {
        // Only admin can delete goods receipts
        return $user->roles()->where('name', 'admin')->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, GoodsReceipt $goodsReceipt): bool
    {
        // Only admin can restore goods receipts
        return $user->roles()->where('name', 'admin')->exists();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, GoodsReceipt $goodsReceipt): bool
    {
        // Only admin can force delete goods receipts
        return $user->roles()->where('name', 'admin')->exists();
    }
}
