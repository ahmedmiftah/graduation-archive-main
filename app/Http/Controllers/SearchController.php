<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function __construct(private readonly SearchService $search) {}

    public function index(Request $request): Response
    {
        $filters = $request->only([
            'search', 'department_id', 'specialization_id',
            'academic_year', 'supervisor_id', 'status', 'sort',
        ]);

        $projects = $this->search->searchProjects($filters);
        $projects->getCollection()->load([
            'students' => fn ($q) => $q->select('id', 'full_name', 'project_id'),
        ]);

        return Inertia::render('Search/Index', [
            'projects'      => $projects,
            'filterOptions' => $this->search->getFilterOptions(),
            'filters'       => $filters,
        ]);
    }

    public function suggestions(Request $request): JsonResponse
    {
        $q_val = trim($request->query('q', ''));

        if (mb_strlen($q_val) < 2) {
            return response()->json([]);
        }

        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        $query = Project::where('is_deleted', false);

        if ($user && !$user->hasRole('super_admin') && $user->department_id) {
            $query->where('department_id', $user->department_id);
        }

        $suggestions = $query->where(function ($q) use ($q_val) {
                $q->where('project_title', 'like', "%{$q_val}%")
                  ->orWhereHas('supervisor', function ($query) use ($q_val) {
                      $query->where('name', 'like', "%{$q_val}%");
                  })
                  ->orWhereHas('students', function ($query) use ($q_val) {
                      $query->where('full_name', 'like', "%{$q_val}%");
                  })
                  ->orWhereHas('examiners', function ($query) use ($q_val) {
                      $query->where('full_name', 'like', "%{$q_val}%");
                  });
            })
            ->orderByDesc('visit_count')
            ->limit(5)
            ->pluck('project_title');

        return response()->json($suggestions);
    }
}
