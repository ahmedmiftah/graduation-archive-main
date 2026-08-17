<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectIdeaRequest;
use App\Http\Requests\UpdateProjectIdeaRequest;
use App\Models\ProjectIdea;
use App\Models\ProjectIdeaRequest as ProjectIdeaRequestModel;
use App\Models\Specialization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectIdeaController extends Controller
{
    /**
     * "أفكاري" — every idea this supervisor has published.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ProjectIdea::class);

        /** @var \App\Models\User $user */
        $user          = $request->user();
        $facultyMember = $user->facultyMember;

        abort_unless($facultyMember, 403, 'لا يوجد سجل عضو هيئة تدريس مرتبط بهذا الحساب');

        $ideas = ProjectIdea::where('faculty_member_id', $facultyMember->id)
            ->with('specialization')
            ->withCount(['requests as pending_requests_count' => fn ($q) => $q->where('status', ProjectIdeaRequestModel::STATUS_PENDING)])
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Supervisor/Ideas/Index', [
            'ideas'           => $ideas,
            'specializations' => Specialization::orderBy('name')->get(['id', 'name', 'department_id']),
        ]);
    }

    public function store(StoreProjectIdeaRequest $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        ProjectIdea::create(array_merge($request->validated(), [
            'faculty_member_id' => $user->facultyMember->id,
            'status'            => ProjectIdea::STATUS_AVAILABLE,
        ]));

        return back()->with('success', 'تم نشر فكرة المشروع بنجاح');
    }

    public function update(UpdateProjectIdeaRequest $request, ProjectIdea $idea): RedirectResponse
    {
        $idea->update($request->validated());

        return back()->with('success', 'تم تحديث فكرة المشروع بنجاح');
    }

    public function destroy(Request $request, ProjectIdea $idea): RedirectResponse
    {
        $this->authorize('delete', $idea);

        $idea->delete();

        return back()->with('success', 'تم حذف فكرة المشروع بنجاح');
    }
}
