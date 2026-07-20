<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectFeedback;
use App\Models\ProjectFeedbackAuditLog;
use App\Models\ProjectFeedbackReply;
use App\Notifications\NewProjectFeedbackNotification;
use App\Repositories\ProjectFeedbackRepository;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class ProjectFeedbackService
{
    public function __construct(private readonly ProjectFeedbackRepository $repository) {}

    public function createFeedback(Project $project, array $data): ProjectFeedback
    {
        $studentNames = $project->students->pluck('full_name')->filter()->values()->all();

        $feedback = $this->repository->create([
            'project_id'      => $project->id,
            'department_id'   => $project->department_id,
            'visitor_name'    => $data['visitor_name'] ?? null,
            'visitor_email'   => $data['visitor_email'] ?? null,
            'feedback_type'   => $data['feedback_type'],
            'title'           => $data['title'],
            'message'         => $data['message'],
            'rating'          => $data['rating'] ?? null,
            'status'          => ProjectFeedback::STATUS_NEW,
            'project_title'   => $project->project_title,
            'department_name' => $project->department?->name ?? '',
            'student_names'   => $studentNames,
        ]);

        $this->logAudit($feedback, null, 'created', 'Feedback submitted by visitor');
        $this->sendNotifications($feedback);

        return $feedback;
    }

    public function markAsRead(ProjectFeedback $feedback, ?User $user = null): ProjectFeedback
    {
        if (! $feedback->is_read) {
            $feedback->is_read = true;
            $this->save($feedback);
            $this->logAudit($feedback, $user, 'marked_read');
        }

        return $feedback;
    }

    public function updateStatus(ProjectFeedback $feedback, string $status, ?User $user = null): ProjectFeedback
    {
        $feedback->status = $status;
        if ($status === ProjectFeedback::STATUS_REPLIED || $status === ProjectFeedback::STATUS_RESOLVED) {
            $feedback->responded_at = Carbon::now();
        }
        $this->save($feedback);
        $this->logAudit($feedback, $user, 'status_updated', "Status changed to {$status}");

        return $feedback;
    }

    public function reply(ProjectFeedback $feedback, User $user, string $message): ProjectFeedbackReply
    {
        $reply = $feedback->replies()->create([
            'user_id' => $user->id,
            'message' => $message,
        ]);

        $this->logAudit($feedback, $user, 'replied', 'Admin responded to feedback');
        $this->updateStatus($feedback, ProjectFeedback::STATUS_REPLIED, $user);

        return $reply;
    }

    public function deleteFeedback(ProjectFeedback $feedback, ?User $user = null): bool
    {
        $this->logAudit($feedback, $user, 'deleted');

        return $this->repository->delete($feedback);
    }

    public function save(ProjectFeedback $feedback): bool
    {
        return $this->repository->save($feedback);
    }

    public function findFeedback(int $id): ?ProjectFeedback
    {
        return $this->repository->find($id);
    }

    public function paginateFeedback(array $filters, int $perPage = 15)
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function getUnreadCountForUser(User $user): int
    {
        $query = ProjectFeedback::where('is_read', false);
        if ($user->hasRole('dept_manager')) {
            $query->where('department_id', $user->department_id);
        }

        return $query->count();
    }

    private function logAudit(ProjectFeedback $feedback, ?User $user, string $action, string $notes = null): void
    {
        $feedback->auditLogs()->create([
            'user_id' => $user?->id,
            'action'  => $action,
            'notes'   => $notes,
            'created_at' => Carbon::now(),
        ]);
    }

    private function sendNotifications(ProjectFeedback $feedback): void
    {
        $superAdmins = User::role('super_admin')->get();
        $deptManagers = User::role('dept_manager')
            ->where('department_id', $feedback->department_id)
            ->get();

        $recipients = $superAdmins->concat($deptManagers)->unique('id');

        Notification::send($recipients, new NewProjectFeedbackNotification($feedback));
    }
}
