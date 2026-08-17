<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\LifecycleTimelineService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly LifecycleTimelineService $timeline) {}

    public function index(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user    = $request->user();
        $student = $user->student;

        abort_unless($student, 403, 'لا يوجد سجل طالب مرتبط بهذا الحساب');

        $proposal = $student->currentProposal();
        $proposal?->load(['supervisor', 'students', 'project.currentStatus', 'project.documents']);

        return Inertia::render('Student/Dashboard', [
            'student'  => $student->load(['department', 'specialization', 'user']),
            'proposal' => $proposal,
            'timeline' => $proposal ? $this->timeline->build($proposal) : null,
        ]);
    }
}
