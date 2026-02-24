<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine if the authenticated user can view any users.
     */
    public function viewAny(User $auth): bool
    {
        return $auth->hasAnyRole(['superadmin', 'admin', 'admin_satker']);
    }

    /**
     * Determine if the authenticated user can view the target user.
     */
    public function view(User $auth, User $target): bool
    {
        // Superadmin & Admin can view any user
        if ($auth->hasAnyRole(['superadmin', 'admin'])) {
            return true;
        }

        // Admin Satker can only view users in same satker
        if ($auth->hasRole('admin_satker')) {
            return $target->satker_id === $auth->satker_id;
        }

        // User can view own profile
        return $auth->id === $target->id;
    }

    /**
     * Determine if the authenticated user can create users.
     */
    public function create(User $auth): bool
    {
        return $auth->hasAnyRole(['superadmin', 'admin']);
    }

    /**
     * Determine if the authenticated user can update the target user.
     */
    public function update(User $auth, User $target): bool
    {
        // Superadmin can edit anyone
        if ($auth->hasRole('superadmin')) {
            return true;
        }

        // Admin can edit non-superadmin users
        if ($auth->hasRole('admin')) {
            return !$target->hasRole('superadmin');
        }

        // Admin Satker can edit users in same satker (non-admin/superadmin)
        if ($auth->hasRole('admin_satker')) {
            return $target->satker_id === $auth->satker_id
                && !$target->hasAnyRole(['superadmin', 'admin']);
        }

        // User can edit own profile only
        return $auth->id === $target->id;
    }

    /**
     * Determine if the authenticated user can delete the target user.
     */
    public function delete(User $auth, User $target): bool
    {
        // Cannot delete yourself
        if ($auth->id === $target->id) {
            return false;
        }

        // Superadmin can delete anyone else
        if ($auth->hasRole('superadmin')) {
            return true;
        }

        // Admin can delete non-superadmin users
        if ($auth->hasRole('admin')) {
            return !$target->hasRole('superadmin');
        }

        return false;
    }
}
