<?php

namespace App\Repositories;

use App\Models\ProjectProposal;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectProposalRepository
{
    private const ACTIVE_STATUSES = [ProjectProposal::STATUS_PENDING, ProjectProposal::STATUS_NEEDS_REVISION];
    private const FINISHED_STATUSES = [
        ProjectProposal::STATUS_APPROVED,
        ProjectProposal::STATUS_REJECTED,
        ProjectProposal::STATUS_SUPERSEDED,
    ];

    /**
     * Get paginated list of active (pending / needs_revision) proposals.
     */
    public function paginateActive(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ProjectProposal::query()->whereIn('status', self::ACTIVE_STATUSES);
        $this->applyFilters($query, $filters);

        return $this->withEagerLoads($query)->orderByDesc('created_at')->paginate($perPage)->withQueryString();
    }

    /**
     * Get paginated list of finished (approved / rejected / superseded) proposals.
     */
    public function paginateFinished(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ProjectProposal::query()->whereIn('status', self::FINISHED_STATUSES);
        $this->applyFilters($query, $filters);

        return $this->withEagerLoads($query)->orderByDesc('updated_at')->paginate($perPage)->withQueryString();
    }

    private function withEagerLoads(Builder $query): Builder
    {
        return $query->with(['department', 'specialization', 'supervisor', 'creator'])->withCount('students');
    }

    /**
     * Apply search & filter criteria.
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['title'])) {
            $query->where('title', 'like', "%{$filters['title']}%");
        }
        if (! empty($filters['student_name'])) {
            $query->whereHas('students', function (Builder $q) use ($filters) {
                $q->where('full_name', 'like', "%{$filters['student_name']}%");
            });
        }
        if (! empty($filters['supervisor_name'])) {
            $query->whereHas('supervisor', function (Builder $q) use ($filters) {
                $q->where('full_name', 'like', "%{$filters['supervisor_name']}%");
            });
        }
        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        if (! empty($filters['specialization_id'])) {
            $query->where('specialization_id', $filters['specialization_id']);
        }
        if (! empty($filters['academic_year'])) {
            $query->where('academic_year', $filters['academic_year']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
    }

    /**
     * Find a proposal by id with all details needed for the show page.
     */
    public function find(int $id): ?ProjectProposal
    {
        return ProjectProposal::with([
            'department', 'specialization', 'supervisor', 'creator', 'students',
            'replaces:id,title,status', 'replacedBy:id,title,status,replaces_proposal_id',
            'project.currentStatus', 'project.documents',
        ])->find($id);
    }

    /**
     * Store a new proposal with its inline students.
     */
    public function create(array $data): ProjectProposal
    {
        $students = $data['students'] ?? [];
        unset($data['students']);

        $proposal = ProjectProposal::create($data);
        foreach ($students as $student) {
            $proposal->students()->create($this->withResolvedStudentId($student));
        }

        return $proposal;
    }

    /**
     * Auto-links a typed team member to their real Student account (if any)
     * by matching registration_number — lets a logged-in student's own
     * proposal team be resolved without requiring manual linking anywhere.
     */
    private function withResolvedStudentId(array $student): array
    {
        if (empty($student['registration_number'])) {
            return $student;
        }

        $student['student_id'] = Student::where('registration_number', $student['registration_number'])->value('id');

        return $student;
    }

    /**
     * Update an existing proposal, replacing its inline students.
     */
    public function update(ProjectProposal $proposal, array $data): ProjectProposal
    {
        $students = $data['students'] ?? null;
        unset($data['students']);

        $proposal->update($data);

        if (is_array($students)) {
            $proposal->students()->delete();
            foreach ($students as $student) {
                $proposal->students()->create($this->withResolvedStudentId($student));
            }
        }

        return $proposal;
    }

    /**
     * Delete a proposal and its students.
     */
    public function delete(ProjectProposal $proposal): void
    {
        $proposal->students()->delete();
        $proposal->delete();
    }
}
