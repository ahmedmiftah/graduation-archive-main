<?php

namespace App\Policies;

use App\Models\ProjectFeedback;
use App\Models\User;

class ProjectFeedbackPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'dept_manager']);
    }

    public function view(User $user, ProjectFeedback $feedback): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->hasRole('dept_manager') && $feedback->department_id === $user->department_id;
    }

    public function reply(User $user, ProjectFeedback $feedback): bool
    {
        return $this->view($user, $feedback);
    }

    public function updateStatus(User $user, ProjectFeedback $feedback): bool
    {
        return $this->view($user, $feedback);
    }

    public function archive(User $user, ProjectFeedback $feedback): bool
    {
        return $this->view($user, $feedback);
    }

    public function delete(User $user, ProjectFeedback $feedback): bool
    {
        return $this->view($user, $feedback);
    }
}
