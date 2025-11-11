<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Warehouse;

class WarehousePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Allow admin, manager, and staff to view warehouses
        return $user->roles()->whereIn('name', ['admin', 'manager', 'staff'])->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Warehouse $warehouse): bool
    {
        // Allow admin, manager, and staff to view warehouse details
        return $user->roles()->whereIn('name', ['admin', 'manager', 'staff'])->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only admin and manager can create warehouses
        return $user->roles()->whereIn('name', ['admin', 'manager'])->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Warehouse $warehouse): bool
    {
        // Only admin and manager can update warehouses
        return $user->roles()->whereIn('name', ['admin', 'manager'])->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Warehouse $warehouse): bool
    {
        // Only admin can delete warehouses
        return $user->roles()->where('name', 'admin')->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Warehouse $warehouse): bool
    {
        // Only admin can restore warehouses
        return $user->roles()->where('name', 'admin')->exists();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Warehouse $warehouse): bool
    {
        // Only admin can force delete warehouses
        return $user->roles()->where('name', 'admin')->exists();
    }
}
