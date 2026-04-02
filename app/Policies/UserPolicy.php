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
        return $auth->hasRole('superadmin');
    }

    /**
     * Determine if the authenticated user can view the target user.
     */
    public function view(User $auth, User $target): bool
    {
        return $auth->hasRole('superadmin');
    }

    /**
     * Determine if the authenticated user can create users.
     */
    public function create(User $auth): bool
    {
        return $auth->hasRole('superadmin');
    }

    /**
     * Determine if the authenticated user can update the target user.
     */
    public function update(User $auth, User $target): bool
    {
        return $auth->hasRole('superadmin');
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

        return $auth->hasRole('superadmin');
    }
}
