<?php

namespace App\Services;

use App\Models\ProjectProposal;
use App\Repositories\ProjectProposalRepository;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use App\Notifications\ProjectProposalStatusChanged;
use Illuminate\Support\Facades\Notification;

class ProjectProposalService
{
    protected $repo;

    public function __construct(ProjectProposalRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Create a new proposal with PDF handling.
     */
    public function create(array $data, ?UploadedFile $pdf = null): ProjectProposal
    {
        // Resolve student registration numbers to IDs
        if (!empty($data['students'])) {
            // Filter out empty values then look up matching users
            $regNumbers = array_filter($data['students'], fn($r) => !empty(trim((string)$r)));
            $studentIds = \App\Models\User::whereIn('registration_number', $regNumbers)->pluck('id')->toArray();
            $data['students'] = $studentIds;
        } else {
            $data['students'] = [];
        }
        if ($pdf) {
            $path = $pdf->store('proposals', 'public');
            $data['pdf_file'] = $path;
        }
        $proposal = $this->repo->create($data);
        return $proposal;
    }

    /**
     * Update an existing proposal, optionally replacing PDF.
     */
    public function update(ProjectProposal $proposal, array $data, ?UploadedFile $pdf = null): ProjectProposal
    {
        // Resolve student registration numbers to IDs (if provided)
        if (!empty($data['students'])) {
            // Filter out empty values then look up matching users
            $regNumbers = array_filter($data['students'], fn($r) => !empty(trim((string)$r)));
            $studentIds = \App\Models\User::whereIn('registration_number', $regNumbers)->pluck('id')->toArray();
            $data['students'] = $studentIds;
        } else {
            $data['students'] = [];
        }
        if ($pdf) {
            // Delete old file if exists
            if ($proposal->pdf_file && \Illuminate\Support\Facades\Storage::disk('public')->exists($proposal->pdf_file)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($proposal->pdf_file);
            }
            $data['pdf_file'] = $pdf->store('proposals', 'public');
        }
        $updated = $this->repo->update($proposal, $data);
        return $updated;
    }

    /**
     * Change status and fire notification + audit log.
     */
    public function changeStatus(ProjectProposal $proposal, string $status, ?string $committeeDecision = null, ?string $committeeNotes = null): ProjectProposal
    {
        $proposal->status = $status;
        if ($committeeDecision) {
            $proposal->committee_decision = $committeeDecision;
        }
        if ($committeeNotes) {
            $proposal->committee_notes = $committeeNotes;
        }
        $proposal->save();

        // Notify creator (and optionally supervisor)
        Notification::send([$proposal->creator], new ProjectProposalStatusChanged($proposal));
        if ($proposal->supervisor) {
            Notification::send([$proposal->supervisor], new ProjectProposalStatusChanged($proposal));
        }
        // Audit log can be created here (omitted for brevity)
        return $proposal;
    }

    /**
     * Delete a proposal and its file.
     */
    public function delete(ProjectProposal $proposal): void
    {
        if ($proposal->pdf_file && Storage::disk('public')->exists($proposal->pdf_file)) {
            Storage::disk('public')->delete($proposal->pdf_file);
        }
        $this->repo->delete($proposal);
    }
}
?>
