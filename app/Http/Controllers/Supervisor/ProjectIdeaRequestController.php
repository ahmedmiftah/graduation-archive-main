<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\ProjectIdea;
use App\Models\ProjectIdeaRequest;
use App\Models\ProposalReservation;
use App\Notifications\ProjectIdeaRequestDecided;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class ProjectIdeaRequestController extends Controller
{
    /**
     * Every join request submitted for one specific idea — owning supervisor only.
     */
    public function index(ProjectIdea $idea): Response
    {
        $this->authorize('viewAny', [ProjectIdeaRequest::class, $idea]);

        $idea->load(['requests' => fn ($q) => $q->with(['student', 'reservation'])->orderByDesc('created_at')]);

        return Inertia::render('Supervisor/Ideas/Requests', [
            'idea'     => $idea,
            'requests' => $idea->requests,
        ]);
    }

    public function accept(ProjectIdeaRequest $ideaRequest): RedirectResponse
    {
        $this->authorize('decide', $ideaRequest);

        $idea = $ideaRequest->idea;

        $ideaRequest->update([
            'status'     => ProjectIdeaRequest::STATUS_ACCEPTED,
            'decided_at' => now(),
        ]);

        ProposalReservation::create([
            'project_idea_id'         => $idea->id,
            'student_id'              => $ideaRequest->student_id,
            'project_idea_request_id' => $ideaRequest->id,
            'status'                  => ProposalReservation::STATUS_RESERVED,
        ]);

        $idea->syncStatusFromReservations();

        if ($ideaRequest->student->user) {
            Notification::send($ideaRequest->student->user, new ProjectIdeaRequestDecided($ideaRequest));
        }

        return back()->with('success', 'تم قبول طلب الانضمام');
    }

    public function reject(ProjectIdeaRequest $ideaRequest): RedirectResponse
    {
        $this->authorize('decide', $ideaRequest);

        $ideaRequest->update([
            'status'     => ProjectIdeaRequest::STATUS_REJECTED,
            'decided_at' => now(),
        ]);

        if ($ideaRequest->student->user) {
            Notification::send($ideaRequest->student->user, new ProjectIdeaRequestDecided($ideaRequest));
        }

        return back()->with('success', 'تم رفض طلب الانضمام');
    }
}
