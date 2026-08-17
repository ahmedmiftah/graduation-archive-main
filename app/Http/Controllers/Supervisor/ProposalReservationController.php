<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\ProposalReservation;
use Illuminate\Http\RedirectResponse;

class ProposalReservationController extends Controller
{
    public function approve(ProposalReservation $reservation): RedirectResponse
    {
        $this->authorize('approve', $reservation);

        $reservation->update(['status' => ProposalReservation::STATUS_APPROVED]);

        return back()->with('success', 'تم اعتماد الحجز');
    }

    public function release(ProposalReservation $reservation): RedirectResponse
    {
        $this->authorize('release', $reservation);

        $reservation->update(['status' => ProposalReservation::STATUS_RELEASED]);
        $reservation->idea->syncStatusFromReservations();

        return back()->with('success', 'تم تحرير الحجز — أصبحت الفكرة متاحة للانضمام مجددًا');
    }

    public function finish(ProposalReservation $reservation): RedirectResponse
    {
        $this->authorize('finish', $reservation);

        $reservation->update(['status' => ProposalReservation::STATUS_FINISHED]);

        return back()->with('success', 'تم إنهاء الحجز');
    }
}
