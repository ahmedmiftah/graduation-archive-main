<?php

namespace App\Policies;

use App\Models\ProposalReservation;
use App\Models\User;

class ProposalReservationPolicy
{
    public function view(User $user, ProposalReservation $reservation): bool
    {
        return $this->isOwningStudent($user, $reservation) || $this->isOwningSupervisor($user, $reservation);
    }

    /**
     * Student declares they're actively working on this reservation.
     */
    public function markUnderReview(User $user, ProposalReservation $reservation): bool
    {
        return $this->isOwningStudent($user, $reservation) && $reservation->status === ProposalReservation::STATUS_RESERVED;
    }

    /**
     * Student gives up the reservation — allowed any time before approval.
     */
    public function abandon(User $user, ProposalReservation $reservation): bool
    {
        return $this->isOwningStudent($user, $reservation) && in_array($reservation->status, [
            ProposalReservation::STATUS_RESERVED,
            ProposalReservation::STATUS_UNDER_REVIEW,
        ], true);
    }

    /**
     * Supervisor formally confirms a reservation that's under review.
     */
    public function approve(User $user, ProposalReservation $reservation): bool
    {
        return $this->isOwningSupervisor($user, $reservation) && $reservation->status === ProposalReservation::STATUS_UNDER_REVIEW;
    }

    /**
     * Supervisor frees up the idea's slot after the student abandoned it.
     */
    public function release(User $user, ProposalReservation $reservation): bool
    {
        return $this->isOwningSupervisor($user, $reservation) && $reservation->status === ProposalReservation::STATUS_ABANDONED;
    }

    /**
     * Supervisor marks a fully-approved reservation as finished.
     */
    public function finish(User $user, ProposalReservation $reservation): bool
    {
        return $this->isOwningSupervisor($user, $reservation) && $reservation->status === ProposalReservation::STATUS_APPROVED;
    }

    private function isOwningStudent(User $user, ProposalReservation $reservation): bool
    {
        return $user->hasRole('student') && $user->student !== null && $reservation->student_id === $user->student->id;
    }

    private function isOwningSupervisor(User $user, ProposalReservation $reservation): bool
    {
        return $user->hasRole('supervisor') && $user->facultyMember !== null && $reservation->idea->faculty_member_id === $user->facultyMember->id;
    }
}
