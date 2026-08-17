<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\ProjectProposal;
use App\Services\LifecycleTimelineService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ProposalController extends Controller
{
    public function __construct(private readonly LifecycleTimelineService $timeline) {}

    /**
     * "مشاريعي" — every proposal (any status) this supervisor has ever been
     * assigned to, each annotated with its current lifecycle stage.
     */
    public function index(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user          = $request->user();
        $facultyMember = $user->facultyMember;

        abort_unless($facultyMember, 403, 'لا يوجد سجل عضو هيئة تدريس مرتبط بهذا الحساب');

        $proposals = ProjectProposal::where('supervisor_id', $facultyMember->id)
            ->with(['department', 'specialization', 'students', 'project.currentStatus', 'project.documents'])
            ->orderByDesc('updated_at')
            ->get();

        $rows = $proposals->map(function (ProjectProposal $proposal) {
            $currentStage = Collection::make($this->timeline->build($proposal))->firstWhere('status', 'current');

            return [
                'id'             => $proposal->id,
                'title'          => $proposal->title,
                'status'         => $proposal->status,
                'students'       => $proposal->students->pluck('full_name'),
                'current_stage'  => $currentStage['name_ar'] ?? null,
                'needs_action'   => $currentStage['needs_student_action'] ?? false,
                'updated_at'     => $proposal->updated_at,
            ];
        });

        return Inertia::render('Supervisor/Proposals/Index', [
            'proposals' => $rows,
        ]);
    }

    public function show(Request $request, ProjectProposal $proposal): Response
    {
        $this->authorize('view', $proposal);

        $proposal->load(['department', 'specialization', 'students', 'project.currentStatus', 'project.documents']);

        return Inertia::render('Supervisor/Proposals/Show', [
            'proposal' => $proposal,
            'timeline' => $this->timeline->build($proposal),
            'canEditNote' => $request->user()->can('updateNote', $proposal),
        ]);
    }

    public function updateNote(Request $request, ProjectProposal $proposal): RedirectResponse
    {
        $this->authorize('updateNote', $proposal);

        $validated = $request->validate([
            'supervisor_note' => ['nullable', 'string'],
        ]);

        $proposal->update($validated);

        return back()->with('success', 'تم حفظ ملاحظتك بنجاح');
    }
}
