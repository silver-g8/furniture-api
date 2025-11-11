<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PurchaseReturn;
use App\Models\User;

class PurchaseReturnPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Allow admin, manager, and staff to view purchase returns
        return $user->roles()->whereIn('name', ['admin', 'manager', 'staff'])->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PurchaseReturn $purchaseReturn): bool
    {
        // Allow admin, manager, and staff to view purchase return details
        return $user->roles()->whereIn('name', ['admin', 'manager', 'staff'])->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only admin and manager can create purchase returns
        return $user->roles()->whereIn('name', ['admin', 'manager'])->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PurchaseReturn $purchaseReturn): bool
    {
        // Only admin and manager can update/approve purchase returns
        return $user->roles()->whereIn('name', ['admin', 'manager'])->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PurchaseReturn $purchaseReturn): bool
    {
        // Only admin can delete purchase returns
        return $user->roles()->where('name', 'admin')->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PurchaseReturn $purchaseReturn): bool
    {
        // Only admin can restore purchase returns
        return $user->roles()->where('name', 'admin')->exists();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PurchaseReturn $purchaseReturn): bool
    {
        // Only admin can force delete purchase returns
        return $user->roles()->where('name', 'admin')->exists();
    }
}
