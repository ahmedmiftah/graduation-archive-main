<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangeProposalStatusRequest;
use App\Http\Requests\StoreProjectProposalRequest;
use App\Http\Requests\UpdateProjectProposalRequest;
use App\Models\Department;
use App\Models\FacultyMember;
use App\Models\ProjectProposal;
use App\Models\Specialization;
use App\Models\User;
use App\Repositories\ProjectProposalRepository;
use App\Services\ProjectProposalService;
use App\Services\SearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProjectProposalWebController extends Controller
{
    public function __construct(
        private readonly ProjectProposalService $service,
        private readonly ProjectProposalRepository $repo,
        private readonly SearchService $search,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ProjectProposal::class);

        $filters = $this->scopedFilters($request);

        return Inertia::render('Proposals/Index', [
            'proposals'     => $this->repo->paginateActive($filters),
            'filterOptions' => $this->formOptions($request->user()),
            'filters'       => $filters,
        ]);
    }

    public function archived(Request $request): Response
    {
        $this->authorize('viewAny', ProjectProposal::class);

        $filters = $this->scopedFilters($request);

        return Inertia::render('Proposals/Archived/Index', [
            'proposals'     => $this->repo->paginateFinished($filters),
            'filterOptions' => $this->formOptions($request->user()),
            'filters'       => $filters,
        ]);
    }

    public function show(int $id): Response
    {
        $proposal = $this->repo->find($id);
        abort_unless($proposal, 404);
        $this->authorize('view', $proposal);

        if ($user = Auth::user()) {
            $user->unreadNotifications()
                ->where('data->proposal_id', $proposal->id)
                ->update(['read_at' => now()]);
        }

        return Inertia::render('Proposals/Show', array_merge([
            'proposal' => $proposal,
        ], $this->formOptions(Auth::user())));
    }

    public function store(StoreProjectProposalRequest $request): RedirectResponse
    {
        $this->authorize('create', ProjectProposal::class);

        $data                   = $request->validated();
        $data['created_by']     = $request->user()->id;
        $data['department_id']  = Specialization::findOrFail($data['specialization_id'])->department_id;

        $similar  = $this->search->detectSimilarity($data['title'], $data['description'] ?? '');
        $proposal = $this->service->create($data, $request->file('form_file'), $request->file('proposal_file'));

        $redirect = redirect()->route('proposals.show', $proposal)->with('success', 'تم إضافة المقترح بنجاح');

        if ($similar->isNotEmpty()) {
            $redirect->with('similarity_warning', $this->withUrls($similar));
        }

        return $redirect;
    }

    public function update(UpdateProjectProposalRequest $request, ProjectProposal $proposal): RedirectResponse
    {
        $this->authorize('update', $proposal);

        $data                  = $request->validated();
        $data['department_id'] = Specialization::findOrFail($data['specialization_id'])->department_id;

        $similar = $this->search->detectSimilarity($data['title'], $data['description'] ?? '', excludeProposalId: $proposal->id);

        $this->service->update($proposal, $data, $request->file('form_file'), $request->file('proposal_file'));

        $redirect = redirect()->route('proposals.show', $proposal)->with('success', 'تم تحديث المقترح بنجاح');

        if ($similar->isNotEmpty()) {
            $redirect->with('similarity_warning', $this->withUrls($similar));
        }

        return $redirect;
    }

    /**
     * Attach a viewer-safe 'url' to each similarity candidate. Both target
     * routes are reachable by every role that can create/edit a proposal
     * here (dept_staff/dept_manager/super_admin), so linking is always safe.
     */
    private function withUrls(\Illuminate\Support\Collection $similar): array
    {
        return $similar->map(fn (array $item) => [
            ...$item,
            'url' => route($item['type'] === 'project' ? 'projects.show' : 'proposals.show', $item['id']),
        ])->all();
    }

    public function destroy(ProjectProposal $proposal): RedirectResponse
    {
        $this->authorize('delete', $proposal);

        $this->service->delete($proposal);

        return redirect()->route('proposals.index')->with('success', 'تم حذف المقترح بنجاح');
    }

    public function changeStatus(ChangeProposalStatusRequest $request, ProjectProposal $proposal): RedirectResponse
    {
        $this->authorize('changeStatus', $proposal);

        $this->service->changeStatus(
            $proposal,
            $request->validated('action'),
            $request->validated('rejection_reason'),
            $request->validated('supervisor_note'),
            $request->validated('department_note'),
        );

        return back()->with('success', 'تم تحديث حالة المقترح بنجاح');
    }

    private function scopedFilters(Request $request): array
    {
        $filters = $request->only([
            'title', 'student_name', 'supervisor_name',
            'department_id', 'specialization_id', 'academic_year', 'status',
        ]);

        $user = $request->user();
        if (! $user->hasRole('super_admin') && $user->department_id) {
            $filters['department_id'] = $user->department_id;
        }

        return $filters;
    }

    /**
     * Departments/specializations/supervisors available to the given user,
     * scoped to their own department (super_admin sees everything). The
     * supervisor list in particular must only show faculty members from the
     * user's own department.
     */
    private function formOptions(?User $user): array
    {
        $departmentId = ($user && ! $user->hasRole('super_admin')) ? $user->department_id : null;

        $departmentsQuery = Department::orderBy('name');
        if ($departmentId) {
            $departmentsQuery->where('id', $departmentId);
        }

        $specializationsQuery = Specialization::orderBy('name');
        if ($departmentId) {
            $specializationsQuery->where('department_id', $departmentId);
        }

        $supervisorsQuery = FacultyMember::orderBy('full_name');
        if ($departmentId) {
            $supervisorsQuery->whereHas('departments', fn ($q) => $q->where('departments.id', $departmentId));
        }

        return [
            'departments'     => $departmentsQuery->get(['id', 'name']),
            'specializations' => $specializationsQuery->get(['id', 'name', 'department_id']),
            'supervisors'     => $supervisorsQuery->get(['id', 'full_name']),
        ];
    }
}
