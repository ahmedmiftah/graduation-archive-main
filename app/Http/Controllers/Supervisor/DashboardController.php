<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\FacultyMember;
use App\Models\ProjectDocument;
use App\Models\ProjectProposal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const STATUS_ARCHIVED         = 1;
    private const STATUS_IN_PROGRESS      = 5;
    private const STATUS_READY_FOR_DEFENSE = 6;

    public function index(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user          = $request->user();
        $facultyMember = $user->facultyMember;

        abort_unless($facultyMember, 403, 'لا يوجد سجل عضو هيئة تدريس مرتبط بهذا الحساب');

        $supervisedProjectsQuery = $facultyMember->supervisedProjects()->where('is_deleted', false);

        $supervisedProjectIds = (clone $supervisedProjectsQuery)->pluck('id');

        $stats = [
            'total_projects'                    => (clone $supervisedProjectsQuery)->count(),
            'in_progress_count'                 => (clone $supervisedProjectsQuery)->where('current_status_id', self::STATUS_IN_PROGRESS)->count(),
            'completed_count'                   => (clone $supervisedProjectsQuery)->where('current_status_id', self::STATUS_ARCHIVED)->count(),
            'ready_for_defense_count'            => (clone $supervisedProjectsQuery)->where('current_status_id', self::STATUS_READY_FOR_DEFENSE)->count(),
            'needs_action_count'                 => ProjectProposal::where('supervisor_id', $facultyMember->id)
                ->where('status', ProjectProposal::STATUS_PENDING)
                ->whereNull('supervisor_note')
                ->count(),
            'pending_documentation_review_count' => ProjectDocument::whereIn('project_id', $supervisedProjectIds)
                ->whereNull('approved_at')
                ->count(),
            'examiner_assignments_count'          => $facultyMember->projects()->count(),
        ];

        return Inertia::render('Supervisor/Dashboard', [
            'facultyMember' => $facultyMember,
            'stats'         => $stats,
        ]);
    }
}
