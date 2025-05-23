<?php

namespace App\Policies;

use App\Models\User;
use App\Models\League;
use Illuminate\Auth\Access\Response;

class LeaguePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, League $league): bool
    {
        return false;
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
    public function update(User $user, League $league): bool
    {
        return $user->id === $league->club->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, League $league): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, League $league): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, League $league): bool
    {
        return false;
    }
}
