<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SalesReturn;
use App\Models\User;

class SalesReturnPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Allow admin, manager, and staff to view sales returns
        return $user->roles()->whereIn('name', ['admin', 'manager', 'staff'])->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SalesReturn $salesReturn): bool
    {
        // Allow admin, manager, and staff to view sales return details
        return $user->roles()->whereIn('name', ['admin', 'manager', 'staff'])->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only admin and manager can create sales returns
        return $user->roles()->whereIn('name', ['admin', 'manager'])->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SalesReturn $salesReturn): bool
    {
        // Only admin and manager can update/approve sales returns
        return $user->roles()->whereIn('name', ['admin', 'manager'])->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SalesReturn $salesReturn): bool
    {
        // Only admin can delete sales returns
        return $user->roles()->where('name', 'admin')->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SalesReturn $salesReturn): bool
    {
        // Only admin can restore sales returns
        return $user->roles()->where('name', 'admin')->exists();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SalesReturn $salesReturn): bool
    {
        // Only admin can force delete sales returns
        return $user->roles()->where('name', 'admin')->exists();
    }
}
