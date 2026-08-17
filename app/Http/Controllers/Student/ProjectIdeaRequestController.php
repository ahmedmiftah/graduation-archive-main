<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ProjectIdea;
use App\Models\ProjectIdeaRequest;
use App\Notifications\ProjectIdeaRequestSubmitted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class ProjectIdeaRequestController extends Controller
{
    /**
     * "طلباتي" — every idea-join request this student has ever submitted.
     */
    public function index(Request $request): Response
    {
        $student = $request->user()->student;

        $requests = $student->ideaRequests()
            ->with(['idea.specialization', 'idea.facultyMember'])
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Student/Ideas/MyRequests', [
            'requests' => $requests,
        ]);
    }

    public function store(Request $request, ProjectIdea $idea): RedirectResponse
    {
        $this->authorize('create', [ProjectIdeaRequest::class, $idea]);

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $ideaRequest = $idea->requests()->create([
            'student_id' => $request->user()->student->id,
            'message'    => $validated['message'] ?? null,
            'status'     => ProjectIdeaRequest::STATUS_PENDING,
        ]);

        $facultyMemberUser = $idea->facultyMember?->user;
        if ($facultyMemberUser) {
            Notification::send($facultyMemberUser, new ProjectIdeaRequestSubmitted($ideaRequest));
        }

        return back()->with('success', 'تم إرسال طلب الانضمام بنجاح');
    }
}
