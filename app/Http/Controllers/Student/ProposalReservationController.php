<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ProposalReservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProposalReservationController extends Controller
{
    /**
     * "حجزي" — this student's single active (or most recent) reservation.
     */
    public function show(Request $request): Response
    {
        $student = $request->user()->student;

        $reservation = $student->reservations()
            ->with(['idea.specialization', 'idea.facultyMember'])
            ->latest()
            ->first();

        return Inertia::render('Student/Ideas/Reservation', [
            'reservation' => $reservation,
        ]);
    }

    public function markUnderReview(ProposalReservation $reservation): RedirectResponse
    {
        $this->authorize('markUnderReview', $reservation);

        $reservation->update(['status' => ProposalReservation::STATUS_UNDER_REVIEW]);

        return back()->with('success', 'تم تحديث حالة الحجز إلى قيد المراجعة');
    }

    public function abandon(ProposalReservation $reservation): RedirectResponse
    {
        $this->authorize('abandon', $reservation);

        $reservation->update(['status' => ProposalReservation::STATUS_ABANDONED]);

        return back()->with('success', 'تم التخلي عن الحجز');
    }
}
