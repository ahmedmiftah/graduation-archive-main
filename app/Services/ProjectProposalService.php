<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectProposal;
use App\Models\User;
use App\Notifications\ProjectProposalStatusChanged;
use App\Notifications\ProposalSubmitted;
use App\Repositories\ProjectProposalRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class ProjectProposalService
{
    // New projects created from an approved proposal always start "in progress"
    // — matches ProjectController::STATUS_IN_PROGRESS.
    private const PROJECT_STATUS_IN_PROGRESS = 5;

    public function __construct(private readonly ProjectProposalRepository $repo) {}

    /**
     * Create a new proposal, optionally as a replacement for an existing one.
     */
    public function create(array $data, ?UploadedFile $formFile = null, ?UploadedFile $proposalFile = null): ProjectProposal
    {
        if ($formFile) {
            $data['form_file_path'] = $formFile->store('proposals', 'public');
        }
        if ($proposalFile) {
            $data['proposal_file_path'] = $proposalFile->store('proposals', 'public');
        }

        $data['status'] = ProjectProposal::STATUS_PENDING;

        $isResubmission = ! empty($data['replaces_proposal_id']);

        $proposal = DB::transaction(function () use ($data, $isResubmission) {
            $proposal = $this->repo->create($data);

            if ($isResubmission) {
                ProjectProposal::whereKey($data['replaces_proposal_id'])
                    ->update(['status' => ProjectProposal::STATUS_SUPERSEDED]);
            }

            return $proposal;
        });

        $this->notifyDepartment($proposal, $isResubmission);

        return $proposal;
    }

    /**
     * Notify the department's managers that a proposal needs review.
     */
    private function notifyDepartment(ProjectProposal $proposal, bool $isResubmission): void
    {
        $managers = User::role('dept_manager')
            ->where('department_id', $proposal->department_id)
            ->get();

        if ($managers->isNotEmpty()) {
            Notification::send($managers, new ProposalSubmitted($proposal, $isResubmission));
        }
    }

    /**
     * Update an existing proposal, optionally replacing its files.
     */
    public function update(ProjectProposal $proposal, array $data, ?UploadedFile $formFile = null, ?UploadedFile $proposalFile = null): ProjectProposal
    {
        if ($formFile) {
            $this->deleteFile($proposal->form_file_path);
            $data['form_file_path'] = $formFile->store('proposals', 'public');
        }
        if ($proposalFile) {
            $this->deleteFile($proposal->proposal_file_path);
            $data['proposal_file_path'] = $proposalFile->store('proposals', 'public');
        }

        return $this->repo->update($proposal, $data);
    }

    /**
     * Reject / request revision / approve a proposal. Supervisor/department
     * notes may be attached regardless of action — the department currently
     * records both on the system's behalf until the supervisor portal exists.
     */
    public function changeStatus(
        ProjectProposal $proposal,
        string $action,
        ?string $rejectionReason = null,
        ?string $supervisorNote = null,
        ?string $departmentNote = null,
    ): ProjectProposal {
        $proposal = DB::transaction(function () use ($proposal, $action, $rejectionReason, $supervisorNote, $departmentNote) {
            if ($supervisorNote !== null || $departmentNote !== null) {
                $proposal->update(array_filter([
                    'supervisor_note' => $supervisorNote,
                    'department_note' => $departmentNote,
                ], fn ($v) => $v !== null));
            }

            match ($action) {
                'reject' => $proposal->update([
                    'status'           => ProjectProposal::STATUS_REJECTED,
                    'rejection_reason' => $rejectionReason,
                ]),
                'request_revision' => $proposal->update(['status' => ProjectProposal::STATUS_NEEDS_REVISION]),
                'approve' => $this->approve($proposal),
            };

            return $proposal->fresh();
        });

        $this->notify($proposal);

        return $proposal;
    }

    private function approve(ProjectProposal $proposal): void
    {
        $proposal->update(['status' => ProjectProposal::STATUS_APPROVED]);
        $this->convertProposalToProject($proposal);
    }

    /**
     * Create the real Project record once a proposal is approved, copying its
     * data and students (same snapshot pattern ProjectController::archiveStore
     * already uses) and linking back via proposal_id for traceability.
     */
    private function convertProposalToProject(ProjectProposal $proposal): Project
    {
        $project = Project::create([
            'proposal_id'        => $proposal->id,
            'project_title'      => $proposal->title,
            'description'        => $proposal->description,
            'academic_year'      => $proposal->academic_year,
            'semester'           => $proposal->semester,
            'department_id'      => $proposal->department_id,
            'specialization_id'  => $proposal->specialization_id,
            'supervisor_id'      => $proposal->supervisor_id,
            'degree_level'       => 'bachelor',
            'current_status_id'  => self::PROJECT_STATUS_IN_PROGRESS,
            'draft_file_path'    => $proposal->proposal_file_path,
            'is_deleted'         => false,
        ]);

        foreach ($proposal->students as $student) {
            $project->students()->create([
                'full_name'           => $student->full_name,
                'registration_number' => $student->registration_number,
                'status'              => 'active',
            ]);
        }

        if ($proposal->proposal_file_path) {
            $project->documents()->create([
                'document_type' => 'proposal',
                'file_path'     => $proposal->proposal_file_path,
                'is_final'      => false,
            ]);
        }

        return $project;
    }

    /**
     * Notify the whole team (every linked student account), the creator
     * (who may be staff acting on the team's behalf), and the supervisor —
     * via their real account if one exists, or a bare mail route otherwise.
     */
    private function notify(ProjectProposal $proposal): void
    {
        $recipients = $proposal->students
            ->map(fn ($s) => $s->student?->user)
            ->push($proposal->creator)
            ->filter()
            ->unique('id');

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new ProjectProposalStatusChanged($proposal));
        }

        if ($proposal->supervisor?->user) {
            Notification::send($proposal->supervisor->user, new ProjectProposalStatusChanged($proposal));
        } elseif ($proposal->supervisor?->email) {
            // No login account yet — route the mail channel directly
            // (Notification::send() requires the Notifiable trait).
            Notification::route('mail', $proposal->supervisor->email)
                ->notify(new ProjectProposalStatusChanged($proposal));
        }
    }

    /**
     * Delete a proposal and its files.
     */
    public function delete(ProjectProposal $proposal): void
    {
        $this->deleteFile($proposal->form_file_path);
        $this->deleteFile($proposal->proposal_file_path);
        $this->repo->delete($proposal);
    }

    private function deleteFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
