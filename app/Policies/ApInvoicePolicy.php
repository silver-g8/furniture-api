<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ApInvoice;
use App\Models\User;

class ApInvoicePolicy
{
    /**
     * Determine whether the user can view any AP invoices.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the AP invoice.
     */
    public function view(User $user, ApInvoice $apInvoice): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create AP invoices.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the AP invoice.
     */
    public function update(User $user, ApInvoice $apInvoice): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the AP invoice.
     */
    public function delete(User $user, ApInvoice $apInvoice): bool
    {
        // Can only delete draft invoices
        return $apInvoice->status === 'draft';
    }
}
