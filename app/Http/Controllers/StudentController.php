<?php

namespace App\Http\Controllers;

use App\Models\Specialization;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var \App\Models\User $authUser */
        $authUser = $request->user();

        $query = Student::with(['department', 'specialization', 'user'])
            ->when($authUser->hasRole('dept_manager'), fn ($q) => $q->where('department_id', $authUser->department_id))
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('registration_number', 'like', "%{$search}%");
                });
            })
            ->when($request->input('specialization_id'), fn ($q, $id) => $q->where('specialization_id', $id))
            ->when($request->input('semester'), fn ($q, $semester) => $q->where('semester', $semester))
            ->when($request->input('academic_year'), fn ($q, $year) => $q->where('academic_year', $year))
            ->when($request->filled('account_status'), function ($q) use ($request) {
                $active = $request->input('account_status') === 'active';
                $q->whereHas('user', fn ($q) => $q->where('is_active', $active));
            });

        $paginated = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $specializationQuery = Specialization::orderBy('name');
        if ($authUser->hasRole('dept_manager')) {
            $specializationQuery->where('department_id', $authUser->department_id);
        }

        return Inertia::render('Students/Index', [
            'students' => [
                'data'  => $paginated->through(fn (Student $student) => [
                    'id'                   => $student->id,
                    'full_name'            => $student->full_name,
                    'registration_number'  => $student->registration_number,
                    'masked_national_id'   => $student->maskedNationalId(),
                    'department'           => $student->department->name,
                    'specialization'       => $student->specialization->name,
                    'semester'             => $student->semester,
                    'academic_year'        => $student->academic_year,
                    'account_active'       => (bool) $student->user?->is_active,
                    'account_created_at'   => $student->user?->created_at,
                ])->items(),
                'links' => $paginated->linkCollection()->toArray(),
                'meta'  => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'total'        => $paginated->total(),
                    'per_page'     => $paginated->perPage(),
                ],
            ],
            'specializations' => $specializationQuery->get(['id', 'name']),
            'filters'         => $request->only(['search', 'specialization_id', 'semester', 'academic_year', 'account_status']),
        ]);
    }

    public function show(Request $request, Student $student): Response
    {
        $this->authorizeDeptScope($request, $student);

        return Inertia::render('Students/Show', [
            'student' => $student->load(['department', 'specialization', 'user']),
        ]);
    }

    public function toggleActive(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeDeptScope($request, $student);

        if (! $student->user) {
            return back()->with('error', 'لا يوجد حساب دخول لهذا الطالب');
        }

        $newActive = ! $student->user->is_active;
        $student->user->update(['is_active' => $newActive]);

        return back()->with('success', $newActive ? 'تم تفعيل حساب الطالب' : 'تم إيقاف حساب الطالب');
    }

    public function resetPassword(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeDeptScope($request, $student);

        if (! $student->user) {
            return back()->with('error', 'لا يوجد حساب دخول لهذا الطالب');
        }

        $newPassword = Str::password(10, symbols: false);

        $student->user->update([
            'password'               => $newPassword,
            'force_password_change'  => true,
        ]);

        return back()
            ->with('success', 'تم إعادة تعيين كلمة المرور')
            ->with('reset_password_value', $newPassword);
    }

    private function authorizeDeptScope(Request $request, Student $student): void
    {
        /** @var \App\Models\User $authUser */
        $authUser = $request->user();

        if ($authUser->hasRole('dept_manager') && $authUser->department_id !== $student->department_id) {
            abort(403);
        }
    }
}
