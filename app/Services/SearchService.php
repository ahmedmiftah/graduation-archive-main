<?php

namespace App\Services;

use App\Models\Department;
use App\Models\FacultyMember;
use App\Models\Project;
use App\Models\ProjectProposal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

class SearchService
{
    private const STATUS_ARCHIVED = 1;

    public function searchProjects(array $filters): LengthAwarePaginator
    {
        $query = $this->buildFilteredQuery($filters);

        $sort = $filters['sort'] ?? 'created_at';
        match ($sort) {
            'title'       => $query->orderBy('project_title'),
            'visit_count' => $query->orderByDesc('visit_count'),
            default       => $query->latest(),
        };

        return $query->paginate(15)->withQueryString();
    }

    /**
     * Same filters as searchProjects() but unpaginated, for exports.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Project>
     */
    public function searchProjectsForExport(array $filters): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->buildFilteredQuery($filters);

        return $query->orderBy('project_title')->get();
    }

    private function buildFilteredQuery(array &$filters)
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        // Enforce departmental isolation
        if ($user && !$user->hasRole('super_admin') && $user->department_id) {
            $filters['department_id'] = $user->department_id;
        }

        $query = Project::with(['department', 'specialization', 'supervisor', 'currentStatus'])
            ->withCount('students')
            ->where('is_deleted', false);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('project_title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('department', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('specialization', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('supervisor', function ($q) use ($search) {
                      $q->where('full_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('students', function ($q) use ($search) {
                      $q->where('full_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('facultyMembers', function ($q) use ($search) {
                      $q->where('full_name', 'like', "%{$search}%");
                  });
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

        if (! empty($filters['semester'])) {
            $query->where('semester', $filters['semester']);
        }

        if (! empty($filters['degree_level'])) {
            $query->where('degree_level', $filters['degree_level']);
        }

        if (! empty($filters['supervisor_id'])) {
            $query->where('supervisor_id', $filters['supervisor_id']);
        }

        // 'active' restricts to published (archived) projects only;
        // 'exclude_archived' hides them (used by the general projects list,
        // since archived/"منجز" projects only belong in the archive page).
        if (! empty($filters['status']) && $filters['status'] === 'active') {
            $query->where('current_status_id', self::STATUS_ARCHIVED);
        } elseif (! empty($filters['status']) && $filters['status'] === 'exclude_archived') {
            $query->where('current_status_id', '!=', self::STATUS_ARCHIVED);
        }

        return $query;
    }

    /**
     * Minimum similarity percentage for a candidate to surface as a warning.
     */
    private const SIMILARITY_THRESHOLD = 30;

    /**
     * Find archived projects and live proposals with a similar title/description,
     * scored by word-overlap percentage rather than a plain substring match.
     * A non-blocking warning only — the department always has final say.
     *
     * @return SupportCollection<int, array{type: string, id: int, title: string, academic_year: ?string, department: ?string, similarity_percent: int}>
     */
    public function detectSimilarity(
        string $title,
        string $description = '',
        ?int $excludeProjectId = null,
        ?int $excludeProposalId = null,
    ): SupportCollection {
        $words = $this->significantWords($title);

        $projects = Project::where('is_deleted', false)
            ->where(function ($q) use ($title, $words) {
                $q->where('project_title', 'like', '%' . $title . '%');
                foreach ($words as $word) {
                    $q->orWhere('project_title', 'like', '%' . $word . '%');
                }
            })
            ->when($excludeProjectId, fn ($q) => $q->where('id', '!=', $excludeProjectId))
            ->with('department:id,name')
            ->limit(20)
            ->get(['id', 'project_title', 'description', 'academic_year', 'department_id']);

        $proposals = ProjectProposal::where('status', '!=', ProjectProposal::STATUS_SUPERSEDED)
            ->where(function ($q) use ($title, $words) {
                $q->where('title', 'like', '%' . $title . '%');
                foreach ($words as $word) {
                    $q->orWhere('title', 'like', '%' . $word . '%');
                }
            })
            ->when($excludeProposalId, fn ($q) => $q->where('id', '!=', $excludeProposalId))
            ->with('department:id,name')
            ->limit(20)
            ->get(['id', 'title', 'description', 'academic_year', 'department_id']);

        $scored = $projects
            ->map(fn (Project $p) => [
                'type'               => 'project',
                'id'                 => $p->id,
                'title'              => $p->project_title,
                'academic_year'      => $p->academic_year,
                'department'         => $p->department?->name,
                'similarity_percent' => $this->similarityPercent($title, $description, $p->project_title, $p->description),
            ])
            ->concat($proposals->map(fn (ProjectProposal $p) => [
                'type'               => 'proposal',
                'id'                 => $p->id,
                'title'              => $p->title,
                'academic_year'      => $p->academic_year,
                'department'         => $p->department?->name,
                'similarity_percent' => $this->similarityPercent($title, $description, $p->title, $p->description),
            ]));

        return $scored
            ->filter(fn (array $item) => $item['similarity_percent'] >= self::SIMILARITY_THRESHOLD)
            ->sortByDesc('similarity_percent')
            ->take(5)
            ->values();
    }

    /**
     * Weighted word-overlap score (title 70%, description 30% when both
     * sides have one) expressed as a 0–100 percentage.
     */
    private function similarityPercent(string $titleA, string $descriptionA, string $titleB, ?string $descriptionB): int
    {
        $titleScore = $this->jaccard($titleA, $titleB);

        if ($descriptionA === '' || empty($descriptionB)) {
            return (int) round($titleScore * 100);
        }

        $descriptionScore = $this->jaccard($descriptionA, $descriptionB);

        return (int) round(($titleScore * 0.7 + $descriptionScore * 0.3) * 100);
    }

    /**
     * Jaccard index (intersection / union) of the significant words in two texts.
     */
    private function jaccard(string $a, string $b): float
    {
        $wordsA = $this->significantWords($a);
        $wordsB = $this->significantWords($b);

        if (empty($wordsA) || empty($wordsB)) {
            return 0.0;
        }

        $intersection = count(array_intersect($wordsA, $wordsB));
        $union        = count(array_unique(array_merge($wordsA, $wordsB)));

        return $union > 0 ? $intersection / $union : 0.0;
    }

    /**
     * Lowercased, de-duplicated word tokens (2+ characters), Unicode-aware
     * so Arabic titles/descriptions are tokenized correctly too.
     *
     * @return array<int, string>
     */
    private function significantWords(string $text): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_filter($words, fn ($w) => mb_strlen($w) >= 2)));
    }

    /**
     * Return all data needed to populate the filter dropdowns.
     */
    public function getFilterOptions(): array
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        $departmentId = ($user && !$user->hasRole('super_admin') && $user->department_id) 
            ? $user->department_id 
            : null;

        $departmentsQuery = Department::with('specializations:id,name,department_id')
            ->orderBy('name');
        if ($departmentId) {
            $departmentsQuery->where('id', $departmentId);
        }

        $supervisorsQuery = FacultyMember::orderBy('full_name');

        return [
            'departments'    => $departmentsQuery->get(['id', 'name']),
            'academic_years' => Project::where('is_deleted', false)
                                        ->distinct()
                                        ->orderByDesc('academic_year')
                                        ->pluck('academic_year'),
            'supervisors'    => $supervisorsQuery->get(['id', 'full_name']),
        ];
    }
}
