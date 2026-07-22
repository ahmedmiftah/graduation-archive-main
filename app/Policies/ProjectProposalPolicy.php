<?php

namespace App\Policies;

use App\Models\ProjectProposal;
use App\Models\User;

class ProjectProposalPolicy
{
    /**
     * Determine whether the user can view any proposals.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'dept_manager', 'dept_staff', 'supervisor']);
    }

    /**
     * Determine whether the user can view the proposal.
     */
    public function view(User $user, ProjectProposal $proposal): bool
    {
        if (!$this->viewAny($user)) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->department_id === $proposal->department_id;
    }

    /**
     * Determine whether the user can create proposals.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'dept_manager', 'dept_staff', 'supervisor']);
    }

    /**
     * Determine whether the user can update the proposal.
     */
    public function update(User $user, ProjectProposal $proposal): bool
    {
        if (!$user->hasAnyRole(['super_admin', 'dept_manager', 'dept_staff', 'supervisor'])) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->department_id === $proposal->department_id;
    }

    /**
     * Determine whether the user can delete the proposal.
     */
    public function delete(User $user, ProjectProposal $proposal): bool
    {
        if (!$user->hasAnyRole(['super_admin', 'dept_manager'])) { // staff/supervisor cannot delete
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->department_id === $proposal->department_id;
    }

    /**
     * Determine whether the user can change status.
     */
    public function changeStatus(User $user, ProjectProposal $proposal): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Only department-head (رئيس القسم) can change status & committee decision
        if (!$user->hasRole('dept_manager')) {
            return false;
        }

        return $user->department_id === $proposal->department_id;
    }
}
?>
