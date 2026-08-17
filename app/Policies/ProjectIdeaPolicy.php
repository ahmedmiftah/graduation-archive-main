<?php

namespace App\Policies;

use App\Models\ProjectIdea;
use App\Models\User;

class ProjectIdeaPolicy
{
    /**
     * Supervisors manage their own idea list; students browse available ideas.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['supervisor', 'student']);
    }

    public function view(User $user, ProjectIdea $idea): bool
    {
        if ($user->hasRole('supervisor')) {
            return $this->isOwner($user, $idea);
        }

        if ($user->hasRole('student')) {
            if ($idea->status === ProjectIdea::STATUS_AVAILABLE) {
                return true;
            }

            // A student who already applied (pending/accepted/rejected) keeps
            // access to the idea even after it leaves the "available" state.
            return $user->student !== null
                && $idea->requests()->where('student_id', $user->student->id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('supervisor') && $user->facultyMember !== null;
    }

    public function update(User $user, ProjectIdea $idea): bool
    {
        return $user->hasRole('supervisor') && $this->isOwner($user, $idea);
    }

    public function delete(User $user, ProjectIdea $idea): bool
    {
        return $user->hasRole('supervisor') && $this->isOwner($user, $idea);
    }

    private function isOwner(User $user, ProjectIdea $idea): bool
    {
        return $user->facultyMember !== null && $idea->faculty_member_id === $user->facultyMember->id;
    }
}
