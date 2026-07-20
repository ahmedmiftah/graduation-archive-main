<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectFeedbackRequest;
use App\Models\Project;
use App\Services\ProjectFeedbackService;
use Illuminate\Http\RedirectResponse;

class ProjectFeedbackController extends Controller
{
    public function __construct(private readonly ProjectFeedbackService $feedbackService) {}

    public function store(StoreProjectFeedbackRequest $request, int $projectId): RedirectResponse
    {
        $project = Project::where('id', $projectId)
            ->where('current_status_id', 1)
            ->where('is_deleted', false)
            ->with('students', 'department')
            ->firstOrFail();

        $this->feedbackService->createFeedback($project, $request->validated());

        return redirect()->back()->with('success', 'تم إرسال الملاحظة بنجاح. شكراً لتفاعلك.');
    }
}
