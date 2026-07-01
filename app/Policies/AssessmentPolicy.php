<?php

namespace App\Policies;

use App\Models\Assessment;
use App\Models\User;

class AssessmentPolicy
{
    /**
     * Admins and managers can run assessments.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * All authenticated roles can view assessment results.
     */
    public function view(User $user, Assessment $assessment): bool
    {
        return true;
    }
}