<?php

namespace App\Repositories;

use App\Models\ProjectProposal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectProposalRepository
{
    /**
     * Get paginated list with optional filters.
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ProjectProposal::query();
        $this->applyFilters($query, $filters);
        $query->orderByDesc('created_at');
        return $query->paginate($perPage);
    }

    /**
     * Apply search & filter criteria.
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['title'])) {
            $query->where('title', 'like', "%{$filters['title']}%");
        }
        if (!empty($filters['student_name'])) {
            $query->whereHas('students', function (Builder $q) use ($filters) {
                $q->where('name', 'like', "%{$filters['student_name']}%");
            });
        }
        if (!empty($filters['supervisor_name'])) {
            $query->whereHas('supervisor', function (Builder $q) use ($filters) {
                $q->where('name', 'like', "%{$filters['supervisor_name']}%");
            });
        }
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        if (!empty($filters['specialization_id'])) {
            $query->where('specialization_id', $filters['specialization_id']);
        }
        if (!empty($filters['academic_year'])) {
            $query->where('academic_year', $filters['academic_year']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
    }

    /**
     * Find a proposal by id.
     */
    public function find(int $id): ?ProjectProposal
    {
        return ProjectProposal::with(['department', 'specialization', 'supervisor', 'students', 'creator'])->find($id);
    }

    /**
     * Store a new proposal.
     */
    public function create(array $data): ProjectProposal
    {
        $students = $data['students'] ?? [];
        unset($data['students']);
        $proposal = ProjectProposal::create($data);
        if ($students) {
            $proposal->students()->sync($students);
        }
        return $proposal;
    }

    /**
     * Update an existing proposal.
     */
    public function update(ProjectProposal $proposal, array $data): ProjectProposal
    {
        $students = $data['students'] ?? null;
        unset($data['students']);
        $proposal->update($data);
        if (is_array($students)) {
            $proposal->students()->sync($students);
        }
        return $proposal;
    }

    /**
     * Delete a proposal.
     */
    public function delete(ProjectProposal $proposal): void
    {
        $proposal->students()->detach();
        $proposal->delete();
    }
}
?>
