<?php

namespace App\Policies;

use App\Models\ProjectIdea;
use App\Models\ProjectIdeaRequest;
use App\Models\User;

class ProjectIdeaRequestPolicy
{
    /**
     * A student may request to join an idea only while it's still open,
     * they have no active proposal or active reservation elsewhere (an
     * abandoned-then-released reservation no longer counts), and they
     * haven't already applied to this exact idea.
     */
    public function create(User $user, ProjectIdea $idea): bool
    {
        if (! $user->hasRole('student') || ! $user->student) {
            return false;
        }

        $student = $user->student;

        if ($idea->status !== ProjectIdea::STATUS_AVAILABLE) {
            return false;
        }

        if ($student->hasActiveProposal() || $student->hasActiveReservation()) {
            return false;
        }

        return ! $idea->requests()
            ->where('student_id', $student->id)
            ->whereIn('status', [ProjectIdeaRequest::STATUS_PENDING, ProjectIdeaRequest::STATUS_ACCEPTED])
            ->exists();
    }

    /**
     * Only the owning supervisor reviews requests for their own idea, and
     * only lists/views them through their idea's requests screen.
     */
    public function viewAny(User $user, ProjectIdea $idea): bool
    {
        return $user->hasRole('supervisor') && $user->facultyMember !== null && $idea->faculty_member_id === $user->facultyMember->id;
    }

    /**
     * Accept/reject — the owning supervisor, and only while still pending.
     */
    public function decide(User $user, ProjectIdeaRequest $request): bool
    {
        if ($request->status !== ProjectIdeaRequest::STATUS_PENDING) {
            return false;
        }

        return $user->hasRole('supervisor')
            && $user->facultyMember !== null
            && $request->idea->faculty_member_id === $user->facultyMember->id;
    }
}
