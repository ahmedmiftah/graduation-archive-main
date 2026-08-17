<?php

namespace App\Services;

use App\Models\LifecycleStage;
use App\Models\Project;
use App\Models\ProjectProposal;

/**
 * Derives the 17-stage graduation lifecycle timeline for a proposal/project
 * purely from existing status fields (no separate progress table to keep in
 * sync). Stages with no equivalent signal in the current system (supervisor
 * approval of documentation, field training, clearance, graduation
 * completion) simply stay "upcoming" until those systems exist.
 */
class LifecycleTimelineService
{
    public function build(ProjectProposal $proposal): array
    {
        $project = $proposal->relationLoaded('project') ? $proposal->project : $proposal->load('project')->project;
        $reached = $this->reachedKeys($proposal, $project);

        $stages = LifecycleStage::orderBy('sort_order')->get();
        $currentSortOrder = $stages->whereIn('key', $reached)->max('sort_order') ?? 0;

        return $stages->map(function (LifecycleStage $stage) use ($reached, $currentSortOrder) {
            $isReached = in_array($stage->key, $reached, true);
            $status = match (true) {
                $isReached && $stage->sort_order < $currentSortOrder => 'completed',
                $isReached && $stage->sort_order === $currentSortOrder => 'current',
                default => 'upcoming',
            };

            return [
                'key'                   => $stage->key,
                'name_ar'               => $stage->name_ar,
                'sort_order'            => $stage->sort_order,
                'status'                => $status,
                'needs_student_action'  => $stage->requires_student_action && $status === 'current',
            ];
        })->values()->all();
    }

    /**
     * @return string[] lifecycle_stages.key values considered reached.
     */
    private function reachedKeys(ProjectProposal $proposal, ?Project $project): array
    {
        $keys = ['proposal_submission'];

        if ($proposal->supervisor_id) {
            $keys[] = 'supervisor_selection';
        }

        if ($proposal->status !== ProjectProposal::STATUS_PENDING) {
            // Department is the only reviewer the current system supports;
            // supervisor review is recorded through the same action for now.
            $keys[] = 'supervisor_review';
            $keys[] = 'department_review';
        }

        if ($proposal->status === ProjectProposal::STATUS_APPROVED) {
            $keys[] = 'proposal_approval';
        }

        if ($project) {
            $keys[] = 'project_start';

            if ($project->documents()->exists()) {
                $keys[] = 'documentation_upload';
            }

            if ($project->documents()->where('is_final', true)->exists()) {
                $keys[] = 'final_version';
            }

            $sortOrder = $project->currentStatus?->sort_order;

            // Only sort_order 1–8 represent forward progress; 9 (rejected)
            // and 10 (cancelled) are terminal-negative, not later stages.
            if ($sortOrder !== null && $sortOrder <= 8) {
                if ($sortOrder >= 5) {
                    $keys[] = 'defense_readiness';
                }
                if ($sortOrder >= 6) {
                    $keys[] = 'practical_defense';
                    $keys[] = 'final_defense';
                }
                if ($sortOrder === 7) {
                    $keys[] = 'revisions';
                }
                if ($sortOrder === 8) {
                    $keys[] = 'project_completion';
                }
            }
        }

        return $keys;
    }
}
