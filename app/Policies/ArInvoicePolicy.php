<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ArInvoice;
use App\Models\User;

class ArInvoicePolicy
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
    public function view(User $user, ArInvoice $arInvoice): bool
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
    public function update(User $user, ArInvoice $arInvoice): bool
    {
        return $arInvoice->canBeUpdated();
    }

    /**
     * Determine whether the user can issue the invoice.
     */
    public function issue(User $user, ArInvoice $arInvoice): bool
    {
        return $arInvoice->canBeIssued();
    }

    /**
     * Determine whether the user can cancel the invoice.
     */
    public function cancel(User $user, ArInvoice $arInvoice): bool
    {
        return $arInvoice->canBeCancelled();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ArInvoice $arInvoice): bool
    {
        return $arInvoice->status === 'draft';
    }
}

