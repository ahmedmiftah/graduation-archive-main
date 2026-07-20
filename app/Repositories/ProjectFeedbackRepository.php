<?php

namespace App\Repositories;

use App\Models\ProjectFeedback;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProjectFeedbackRepository
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = ProjectFeedback::with(['project.department', 'department', 'replies.user'])
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc');

        if (! empty($filters['search'])) {
            $query->where(function ($query) use ($filters) {
                $search = '%' . $filters['search'] . '%';
                $query->where('project_title', 'like', $search)
                    ->orWhere('department_name', 'like', $search)
                    ->orWhere('visitor_name', 'like', $search)
                    ->orWhere('title', 'like', $search);
            });
        }

        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (! empty($filters['feedback_type'])) {
            $query->where('feedback_type', $filters['feedback_type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function find(int $id): ?ProjectFeedback
    {
        return ProjectFeedback::with(['project.department', 'department', 'replies.user', 'auditLogs.user'])->find($id);
    }

    public function create(array $data): ProjectFeedback
    {
        return ProjectFeedback::create($data);
    }

    public function save(ProjectFeedback $feedback): bool
    {
        return $feedback->save();
    }

    public function delete(ProjectFeedback $feedback): bool
    {
        return $feedback->delete();
    }
}
