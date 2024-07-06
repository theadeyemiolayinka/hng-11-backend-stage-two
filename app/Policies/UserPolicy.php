<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
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
    public function view(User $user, User $ghost): bool
    {
        $user_orgs = array_map(function ($a) {
            return $a['orgId'];
        }, $user->organisations()->get()->toArray());
        $ghost_orgs = array_map(function ($a) {
            return $a['orgId'];
        }, $ghost->organisations()->get()->toArray());
        return count(array_intersect($user_orgs, $ghost_orgs)) > 0;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $ghost): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $ghost): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $ghost): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $ghost): bool
    {
        return false;
    }
}
