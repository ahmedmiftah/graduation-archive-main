<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignFacultyMemberRequest;
use App\Models\FacultyMember;
use App\Models\Project;
use App\Models\SystemSetting;
use App\Notifications\ExaminerAssigned;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class ProjectFacultyMemberController extends Controller
{
    public function assign(int $projectId, AssignFacultyMemberRequest $request): RedirectResponse
    {
        $project = Project::where('is_deleted', false)->findOrFail($projectId);

        if ($project->facultyMembers()->count() >= SystemSetting::current()->examiners_per_project) {
            return back()->with('error', 'لا يمكن إضافة أكثر من ممتحنين لكل مشروع');
        }

        $facultyMemberId = $request->integer('faculty_member_id');

        if ($project->facultyMembers()->where('faculty_members.id', $facultyMemberId)->exists()) {
            return back()->with('error', 'عضو هيئة التدريس مرتبط بالمشروع بالفعل');
        }

        $project->facultyMembers()->attach($facultyMemberId, ['assigned_by' => Auth::id()]);

        $facultyMember = FacultyMember::find($facultyMemberId);
        if ($facultyMember?->user) {
            Notification::send($facultyMember->user, new ExaminerAssigned($project));
        }

        return back()->with('success', 'تم تعيين عضو هيئة التدريس بنجاح');
    }

    public function remove(int $projectId, int $facultyMemberId): RedirectResponse
    {
        $project = Project::where('is_deleted', false)->findOrFail($projectId);

        $project->facultyMembers()->detach($facultyMemberId);

        return back()->with('success', 'تم إزالة عضو هيئة التدريس بنجاح');
    }
}
