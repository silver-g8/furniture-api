<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ApPayment;
use App\Models\User;

class ApPaymentPolicy
{
    /**
     * Determine whether the user can view any AP payments.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the AP payment.
     */
    public function view(User $user, ApPayment $apPayment): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create AP payments.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the AP payment.
     */
    public function update(User $user, ApPayment $apPayment): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the AP payment.
     */
    public function delete(User $user, ApPayment $apPayment): bool
    {
        // Can only delete draft payments
        return $apPayment->status === 'draft';
    }
}
