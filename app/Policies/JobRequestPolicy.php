<?php

namespace App\Policies;

use App\Models\JobRequest;
use App\Models\User;

class JobRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated roles (admin, manager, supervisor) can view the requests list.
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, JobRequest $request): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, JobRequest $request): bool
    {
        if ($request->status === 'closed') {
            return false;
        }

        return $user->isAdmin() || $request->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, JobRequest $request): bool
    {
        return $user->isAdmin() || $request->created_by === $user->id;
    }
}