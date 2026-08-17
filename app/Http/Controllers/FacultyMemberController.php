<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFacultyMemberRequest;
use App\Http\Requests\UpdateFacultyMemberRequest;
use App\Models\AcademicDegree;
use App\Models\Department;
use App\Models\FacultyMember;
use App\Models\User;
use App\Notifications\SupervisorAccountCreated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class FacultyMemberController extends Controller
{
    public function index(Request $request): Response
    {
        $query = FacultyMember::with(['degree', 'departments'])->withCount('projects');

        if ($request->filled('department_id')) {
            $departmentId = $request->integer('department_id');
            $query->whereHas('departments', fn ($q) => $q->where('departments.id', $departmentId));
        }

        return Inertia::render('FacultyMembers/Index', [
            'facultyMembers' => $query->orderBy('full_name')->get(),
            'departments'    => Department::orderBy('name')->get(['id', 'name']),
            'degrees'        => AcademicDegree::orderBy('degree_name')->get(),
            'filters'        => $request->only('department_id'),
        ]);
    }

    public function store(StoreFacultyMemberRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $facultyMember = FacultyMember::create($data);
        $facultyMember->departments()->sync($data['department_ids']);

        return redirect()->route('faculty-members.index')
            ->with('success', 'تم إضافة عضو هيئة التدريس بنجاح');
    }

    public function update(UpdateFacultyMemberRequest $request, FacultyMember $facultyMember): RedirectResponse
    {
        $data = $request->validated();

        $facultyMember->update($data);
        $facultyMember->departments()->sync($data['department_ids']);

        return redirect()->route('faculty-members.index')
            ->with('success', 'تم تحديث بيانات عضو هيئة التدريس بنجاح');
    }

    /**
     * Give an existing faculty member a login account (role: supervisor).
     * Login is their real email; the temporary password is their registered
     * phone number, with a forced change on first login.
     */
    public function createAccount(FacultyMember $facultyMember): RedirectResponse
    {
        if ($facultyMember->user_id) {
            return back()->with('error', 'يوجد حساب دخول لعضو هيئة التدريس هذا بالفعل');
        }

        if (User::where('email', $facultyMember->email)->exists()) {
            return back()->with('error', 'يوجد مستخدم آخر مسجَّل بنفس البريد الإلكتروني بالفعل');
        }

        $user = User::create([
            'name'                  => $facultyMember->full_name,
            'email'                 => $facultyMember->email,
            'password'              => $facultyMember->phone_number,
            'is_active'             => true,
            'force_password_change' => true,
            'email_verified_at'     => now(),
        ]);
        $user->assignRole('supervisor');

        $facultyMember->update(['user_id' => $user->id]);

        Notification::send($user, new SupervisorAccountCreated($facultyMember));

        return back()->with('success', 'تم إنشاء حساب الدخول بنجاح');
    }

    public function destroy(FacultyMember $facultyMember): RedirectResponse
    {
        if ($facultyMember->projects()->exists()) {
            return back()->with('error', 'لا يمكن حذف عضو هيئة التدريس لارتباطه بمشاريع');
        }

        $facultyMember->delete();

        return redirect()->route('faculty-members.index')
            ->with('success', 'تم حذف عضو هيئة التدريس بنجاح');
    }
}
