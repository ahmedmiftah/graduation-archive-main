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
        return $user->hasAnyRole(['super_admin', 'dept_manager', 'dept_staff', 'student', 'supervisor']);
    }

    /**
     * Determine whether the user can view the proposal.
     */
    public function view(User $user, ProjectProposal $proposal): bool
    {
        if ($user->hasRole('student')) {
            return $this->isTeamMember($user, $proposal);
        }

        if ($user->hasRole('supervisor')) {
            return $this->isAssignedSupervisor($user, $proposal);
        }

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
        if ($user->hasRole('student')) {
            // One active (pending/needs_revision) proposal per student at a time.
            return $user->student !== null && ! $user->student->hasActiveProposal();
        }

        return $user->hasAnyRole(['super_admin', 'dept_manager', 'dept_staff']);
    }

    /**
     * Determine whether the user can update the proposal.
     */
    public function update(User $user, ProjectProposal $proposal): bool
    {
        if ($user->hasRole('student')) {
            // Students may only edit their own team's proposal, and only
            // while it's still awaiting or returned for revision.
            return $this->isTeamMember($user, $proposal)
                && in_array($proposal->status, [ProjectProposal::STATUS_PENDING, ProjectProposal::STATUS_NEEDS_REVISION], true);
        }

        if (!$user->hasAnyRole(['super_admin', 'dept_manager', 'dept_staff'])) {
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
        if (!$user->hasAnyRole(['super_admin', 'dept_manager'])) { // dept_staff and students cannot delete
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

    /**
     * Determine whether the user can write their own supervisor_note.
     * A narrower ability than changeStatus — the supervisor may leave a
     * note but cannot approve/reject/request revisions themselves.
     */
    public function updateNote(User $user, ProjectProposal $proposal): bool
    {
        return $user->hasRole('supervisor') && $this->isAssignedSupervisor($user, $proposal);
    }

    private function isTeamMember(User $user, ProjectProposal $proposal): bool
    {
        if (! $user->student) {
            return false;
        }

        return $proposal->students->contains('student_id', $user->student->id);
    }

    private function isAssignedSupervisor(User $user, ProjectProposal $proposal): bool
    {
        return $user->facultyMember !== null && $proposal->supervisor_id === $user->facultyMember->id;
    }
}
