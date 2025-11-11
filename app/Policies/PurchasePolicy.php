<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;

class PurchasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Allow admin, manager, and staff to view purchases
        return $user->roles()->whereIn('name', ['admin', 'manager', 'staff'])->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Purchase $purchase): bool
    {
        // Allow admin, manager, and staff to view purchase details
        return $user->roles()->whereIn('name', ['admin', 'manager', 'staff'])->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only admin and manager can create purchases
        return $user->roles()->whereIn('name', ['admin', 'manager'])->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Purchase $purchase): bool
    {
        // Only admin and manager can update purchases
        // Additional check: only draft purchases can be updated (handled in controller)
        return $user->roles()->whereIn('name', ['admin', 'manager'])->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Purchase $purchase): bool
    {
        // Only admin can delete purchases
        return $user->roles()->where('name', 'admin')->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Purchase $purchase): bool
    {
        // Only admin can restore purchases
        return $user->roles()->where('name', 'admin')->exists();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Purchase $purchase): bool
    {
        // Only admin can force delete purchases
        return $user->roles()->where('name', 'admin')->exists();
    }
}
