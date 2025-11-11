<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\InstallationOrder;
use App\Models\User;

class InstallationOrderPolicy
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
    public function view(User $user, InstallationOrder $installationOrder): bool
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
    public function update(User $user, InstallationOrder $installationOrder): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InstallationOrder $installationOrder): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the status.
     */
    public function updateStatus(User $user, InstallationOrder $installationOrder): bool
    {
        return true;
    }

    /**
     * Determine whether the user can assign teams.
     */
    public function assignTeam(User $user, InstallationOrder $installationOrder): bool
    {
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, InstallationOrder $installationOrder): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, InstallationOrder $installationOrder): bool
    {
        return true;
    }
}
