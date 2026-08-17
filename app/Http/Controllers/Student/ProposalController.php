<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectProposalRequest;
use App\Http\Requests\UpdateProjectProposalRequest;
use App\Models\FacultyMember;
use App\Models\ProjectProposal;
use App\Models\Specialization;
use App\Models\Student;
use App\Repositories\ProjectProposalRepository;
use App\Services\LifecycleTimelineService;
use App\Services\ProjectProposalService;
use App\Services\SearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProposalController extends Controller
{
    public function __construct(
        private readonly ProjectProposalService $service,
        private readonly ProjectProposalRepository $repo,
        private readonly LifecycleTimelineService $timeline,
        private readonly SearchService $search,
    ) {}

    public function create(Request $request): Response
    {
        $this->authorize('create', ProjectProposal::class);

        return Inertia::render('Student/Proposal/Create', [
            'formOptions' => $this->formOptions($this->student($request)),
            'defaults'    => $this->defaults($this->student($request)),
        ]);
    }

    public function store(StoreProjectProposalRequest $request): RedirectResponse
    {
        $this->authorize('create', ProjectProposal::class);

        $data                  = $request->validated();
        $data['created_by']    = $request->user()->id;
        $data['department_id'] = Specialization::findOrFail($data['specialization_id'])->department_id;

        $similar  = $this->search->detectSimilarity($data['title'], $data['description'] ?? '');
        $proposal = $this->service->create($data, $request->file('form_file'), $request->file('proposal_file'));

        $redirect = redirect()->route('student.proposal.show', $proposal)->with('success', 'تم تقديم المقترح بنجاح');

        if ($similar->isNotEmpty()) {
            $redirect->with('similarity_warning', $this->withUrls($similar));
        }

        return $redirect;
    }

    public function show(Request $request, ProjectProposal $proposal): Response
    {
        $this->authorize('view', $proposal);

        $proposal = $this->repo->find($proposal->id);

        return Inertia::render('Student/Proposal/Show', [
            'proposal' => $proposal,
            'timeline' => $this->timeline->build($proposal),
        ]);
    }

    public function edit(Request $request, ProjectProposal $proposal): Response
    {
        $this->authorize('update', $proposal);

        $proposal = $this->repo->find($proposal->id);

        return Inertia::render('Student/Proposal/Edit', [
            'proposal'    => $proposal,
            'formOptions' => $this->formOptions($this->student($request)),
        ]);
    }

    public function update(UpdateProjectProposalRequest $request, ProjectProposal $proposal): RedirectResponse
    {
        $this->authorize('update', $proposal);

        $data                  = $request->validated();
        $data['department_id'] = Specialization::findOrFail($data['specialization_id'])->department_id;

        $similar = $this->search->detectSimilarity($data['title'], $data['description'] ?? '', excludeProposalId: $proposal->id);

        $this->service->update($proposal, $data, $request->file('form_file'), $request->file('proposal_file'));

        $redirect = redirect()->route('student.proposal.show', $proposal)->with('success', 'تم تحديث المقترح بنجاح');

        if ($similar->isNotEmpty()) {
            $redirect->with('similarity_warning', $this->withUrls($similar));
        }

        return $redirect;
    }

    /**
     * Attach a viewer-safe 'url' to each similarity candidate. A similar
     * project is always safely viewable (projects.show has no role
     * restriction), but a similar proposal belonging to another team is not
     * — the student policy only grants view access to their own team's
     * proposal, so those candidates are shown as plain text, unlinked.
     */
    private function withUrls(\Illuminate\Support\Collection $similar): array
    {
        return $similar->map(fn (array $item) => [
            ...$item,
            'url' => $item['type'] === 'project' ? route('projects.show', $item['id']) : null,
        ])->all();
    }

    private function student(Request $request): Student
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        abort_unless($user->student, 403, 'لا يوجد سجل طالب مرتبط بهذا الحساب');

        return $user->student;
    }

    private function formOptions(Student $student): array
    {
        return [
            'specializations' => Specialization::where('department_id', $student->department_id)->orderBy('name')->get(['id', 'name', 'department_id']),
            'supervisors'      => FacultyMember::whereHas('departments', fn ($q) => $q->where('departments.id', $student->department_id))
                ->orderBy('full_name')->get(['id', 'full_name']),
        ];
    }

    private function defaults(Student $student): array
    {
        return [
            'specialization_id' => $student->specialization_id,
            'academic_year'     => $student->academic_year,
            'semester'          => $student->semester,
        ];
    }
}
