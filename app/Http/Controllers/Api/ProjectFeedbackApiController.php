<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReplyProjectFeedbackRequest;
use App\Http\Requests\UpdateProjectFeedbackStatusRequest;
use App\Models\ProjectFeedback;
use App\Services\ProjectFeedbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectFeedbackApiController extends Controller
{
    public function __construct(private readonly ProjectFeedbackService $feedbackService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ProjectFeedback::class);

        $filters = $request->only([
            'search', 'department_id', 'feedback_type', 'status', 'rating', 'date_from', 'date_to', 'sort', 'direction',
        ]);

        if ($request->user()->hasRole('dept_manager')) {
            $filters['department_id'] = $request->user()->department_id;
        }

        $feedback = $this->feedbackService->paginateFeedback($filters, 15);

        return response()->json($feedback);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $feedback = $this->feedbackService->findFeedback($id);
        abort_if(! $feedback, 404);
        $this->authorize('view', $feedback);

        return response()->json($feedback);
    }

    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $feedback = $this->feedbackService->findFeedback($id);
        abort_if(! $feedback, 404);
        $this->authorize('view', $feedback);

        $this->feedbackService->markAsRead($feedback, $request->user());

        return response()->json(['success' => true]);
    }

    public function reply(ReplyProjectFeedbackRequest $request, int $id): JsonResponse
    {
        $feedback = $this->feedbackService->findFeedback($id);
        abort_if(! $feedback, 404);
        $this->authorize('reply', $feedback);

        $reply = $this->feedbackService->reply($feedback, $request->user(), $request->validated()['message']);

        return response()->json($reply);
    }

    public function updateStatus(UpdateProjectFeedbackStatusRequest $request, int $id): JsonResponse
    {
        $feedback = $this->feedbackService->findFeedback($id);
        abort_if(! $feedback, 404);
        $this->authorize('updateStatus', $feedback);

        $this->feedbackService->updateStatus($feedback, $request->validated()['status'], $request->user());

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $feedback = $this->feedbackService->findFeedback($id);
        abort_if(! $feedback, 404);
        $this->authorize('delete', $feedback);

        $this->feedbackService->deleteFeedback($feedback, $request->user());

        return response()->json(['success' => true]);
    }
}
