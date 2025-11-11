<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Stock;
use App\Models\User;

class StockPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Allow admin, manager, and staff to view stocks
        return $user->roles()->whereIn('name', ['admin', 'manager', 'staff'])->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Stock $stock): bool
    {
        // Allow admin, manager, and staff to view stock details
        return $user->roles()->whereIn('name', ['admin', 'manager', 'staff'])->exists();
    }

    /**
     * Determine whether the user can create models (IN/OUT operations).
     */
    public function create(User $user): bool
    {
        // Allow admin, manager, and staff to perform stock movements
        return $user->roles()->whereIn('name', ['admin', 'manager', 'staff'])->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Stock $stock): bool
    {
        // Direct updates not allowed - use IN/OUT operations
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Stock $stock): bool
    {
        // Stock deletion not allowed - use OUT operation
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Stock $stock): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Stock $stock): bool
    {
        return false;
    }
}
