<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

/**
 * AuditLogPolicy
 *
 * Authorization policy for audit log access.
 * Audit logs are read-only and restricted to authorized roles.
 */
class AuditLogPolicy
{
    /**
     * Determine whether the user can view any audit logs.
     */
    public function viewAny(User $user): bool
    {
        // Admin, manager, and auditor roles can view audit logs
        return $user->hasRole('admin')
            || $user->hasRole('manager');
    }

    /**
     * Determine whether the user can view the audit log.
     */
    public function view(User $user, AuditLog $auditLog): bool
    {
        // Same as viewAny - if you can view any, you can view specific ones
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create audit logs.
     *
     * Audit logs are created automatically by the system, not manually.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the audit log.
     *
     * Audit logs are immutable.
     */
    public function update(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the audit log.
     *
     * Audit logs are immutable and should never be deleted.
     */
    public function delete(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the audit log.
     */
    public function restore(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the audit log.
     */
    public function forceDelete(User $user, AuditLog $auditLog): bool
    {
        return false;
    }
}
