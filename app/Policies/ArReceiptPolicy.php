<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ArReceipt;
use App\Models\User;

class ArReceiptPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ArReceipt $arReceipt): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ArReceipt $arReceipt): bool
    {
        return $arReceipt->canBeUpdated();
    }

    /**
     * Determine whether the user can post the receipt.
     */
    public function post(User $user, ArReceipt $arReceipt): bool
    {
        return $arReceipt->canBePosted();
    }

    /**
     * Determine whether the user can cancel the receipt.
     */
    public function cancel(User $user, ArReceipt $arReceipt): bool
    {
        return $arReceipt->canBeCancelled();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ArReceipt $arReceipt): bool
    {
        return $arReceipt->status === 'draft';
    }
}

