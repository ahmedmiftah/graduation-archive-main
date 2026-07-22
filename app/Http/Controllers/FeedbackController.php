<?php

namespace App\Http\Controllers;

use App\Services\ProjectFeedbackService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeedbackController extends Controller
{
    public function __construct(private readonly ProjectFeedbackService $feedbackService) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', \App\Models\ProjectFeedback::class);

        $filters = $request->only([
            'search', 'department_id', 'feedback_type', 'status', 'rating', 'date_from', 'date_to', 'sort', 'direction',
        ]);

        if ($request->user()->hasRole('dept_manager')) {
            $filters['department_id'] = $request->user()->department_id;
        }

        $feedback = $this->feedbackService->paginateFeedback($filters, 15);

        return Inertia::render('Feedback/Index', [
            'feedback' => $feedback,
            'filters' => $request->only([
                'search', 'department_id', 'feedback_type', 'status', 'rating', 'date_from', 'date_to', 'sort', 'direction',
            ]),
        ]);
    }

    public function show(Request $request, int $feedbackId): Response
    {
        $feedback = $this->feedbackService->findFeedback($feedbackId);
        abort_unless($feedback && $this->authorize('view', $feedback), 403);

        $this->feedbackService->markAsRead($feedback, $request->user());

        if ($user = $request->user()) {
            $user->unreadNotifications()
                ->where('data->feedback_id', $feedbackId)
                ->update(['read_at' => now()]);
        }

        return Inertia::render('Feedback/Show', ['feedback' => $feedback]);
    }
}
