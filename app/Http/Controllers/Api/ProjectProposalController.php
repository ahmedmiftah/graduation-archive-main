<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectProposalResource;
use App\Http\Requests\StoreProjectProposalRequest;
use App\Http\Requests\UpdateProjectProposalRequest;
use App\Models\ProjectProposal;
use App\Repositories\ProjectProposalRepository;
use App\Services\ProjectProposalService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ProjectProposalController extends Controller
{
    protected $service;
    protected $repo;

    public function __construct(ProjectProposalService $service, ProjectProposalRepository $repo)
    {
        $this->service = $service;
        $this->repo = $repo;
        $this->authorizeResource(ProjectProposal::class, 'proposal');
    }

    /**
     * Display a paginated list of proposals with filters.
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'title', 'student_name', 'supervisor_name',
            'department_id', 'specialization_id', 'academic_year', 'status',
        ]);
        $perPage = $request->get('per_page', 15);
        $proposals = $this->repo->paginate($filters, $perPage);
        return ProjectProposalResource::collection($proposals);
    }

    /**
     * Store a newly created proposal.
     */
    public function store(StoreProjectProposalRequest $request)
    {
        $pdf = $request->file('pdf_file');
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        
        // Auto-assign department based on user's department
        if ($request->user()->department_id) {
            $data['department_id'] = $request->user()->department_id;
        }

        $proposal = $this->service->create($data, $pdf);
        return (new ProjectProposalResource($proposal))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Show a specific proposal.
     */
    public function show(ProjectProposal $proposal)
    {
        return new ProjectProposalResource($proposal);
    }

    /**
     * Update an existing proposal.
     */
    public function update(UpdateProjectProposalRequest $request, ProjectProposal $proposal)
    {
        $pdf = $request->file('pdf_file');
        $data = $request->validated();
        
        // Ensure department doesn't change unexpectedly, or set it if missing
        if (!isset($data['department_id']) && $request->user()->department_id) {
            $data['department_id'] = $request->user()->department_id;
        }

        $updated = $this->service->update($proposal, $data, $pdf);
        return new ProjectProposalResource($updated);
    }

    /**
     * Delete a proposal.
     */
    public function destroy(ProjectProposal $proposal)
    {
        $this->service->delete($proposal);
        return response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Change status / committee decision.
     */
    public function changeStatus(Request $request, ProjectProposal $proposal)
    {
        $this->authorize('changeStatus', $proposal);
        $request->validate([
            'status' => ['required', 'in:new,under_review,approved,rejected,archived'],
            'committee_decision' => ['nullable', 'in:accepted,accepted_with_modifications,rejected'],
            'committee_notes' => ['nullable', 'string'],
        ]);
        $updated = $this->service->changeStatus(
            $proposal,
            $request->input('status'),
            $request->input('committee_decision'),
            $request->input('committee_notes')
        );
        return new ProjectProposalResource($updated);
    }
}
?>
