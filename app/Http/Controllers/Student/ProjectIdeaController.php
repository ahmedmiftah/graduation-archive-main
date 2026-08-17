<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ProjectIdea;
use App\Models\Specialization;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectIdeaController extends Controller
{
    /**
     * Browse — available ideas only, optionally filtered by specialization.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ProjectIdea::class);

        $ideas = ProjectIdea::where('status', ProjectIdea::STATUS_AVAILABLE)
            ->when($request->filled('specialization_id'), fn ($q) => $q->where('specialization_id', $request->integer('specialization_id')))
            ->with(['specialization', 'facultyMember'])
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Student/Ideas/Index', [
            'ideas'           => $ideas,
            'specializations' => Specialization::orderBy('name')->get(['id', 'name', 'department_id']),
            'filters'         => $request->only('specialization_id'),
        ]);
    }

    public function show(Request $request, ProjectIdea $idea): Response
    {
        $this->authorize('view', $idea);

        $idea->load(['specialization.department', 'facultyMember']);

        $student = $request->user()->student;
        $myRequest = $student
            ? $idea->requests()->where('student_id', $student->id)->latest()->first()
            : null;

        return Inertia::render('Student/Ideas/Show', [
            'idea'      => $idea,
            'myRequest' => $myRequest,
            'canApply'  => $request->user()->can('create', [\App\Models\ProjectIdeaRequest::class, $idea]),
        ]);
    }
}
