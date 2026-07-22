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
        return $user->hasAnyRole(['super_admin', 'dept_manager', 'dept_staff']);
    }

    /**
     * Determine whether the user can view the proposal.
     */
    public function view(User $user, ProjectProposal $proposal): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create proposals.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'dept_manager', 'dept_staff']);
    }

    /**
     * Determine whether the user can update the proposal.
     */
    public function update(User $user, ProjectProposal $proposal): bool
    {
        return $user->hasAnyRole(['super_admin', 'dept_manager', 'dept_staff']);
    }

    /**
     * Determine whether the user can delete the proposal.
     */
    public function delete(User $user, ProjectProposal $proposal): bool
    {
        return $user->hasAnyRole(['super_admin', 'dept_manager']); // staff cannot delete
    }

    /**
     * Determine whether the user can change status.
     */
    public function changeStatus(User $user, ProjectProposal $proposal): bool
    {
        // Only department-head (رئيس القسم) can change status & committee decision
        return $user->hasRole('dept_manager');
    }
}
?>
