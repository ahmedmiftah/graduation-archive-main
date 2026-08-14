<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var \App\Models\User $authUser */
        $authUser = auth()->user();

        $query = User::with(['roles', 'department'])
            ->when($authUser && $authUser->hasRole('dept_manager'), function ($q) use ($authUser) {
                $q->where('department_id', $authUser->department_id);
            })
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('registration_number', 'like', "%{$search}%");
                });
            })
            ->when($request->input('role'), fn ($q, $role) => $q->role($role))
            ->when($request->input('department_id'), fn ($q, $id) => $q->where('department_id', $id))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')));

        $paginated = $query->orderBy('name')->paginate(15)->withQueryString();

        $departmentQuery = Department::orderBy('name');
        if ($authUser && $authUser->hasRole('dept_manager')) {
            $departmentQuery->where('id', $authUser->department_id);
        }
        $departmentList = $departmentQuery->get(['id', 'name']);

        return Inertia::render('Admin/Users/Index', [
            'users' => [
                'data'  => $paginated->items(),
                'links' => $paginated->linkCollection()->toArray(),
                'meta'  => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'total'        => $paginated->total(),
                    'per_page'     => $paginated->perPage(),
                ],
            ],
            'roles'       => $authUser && $authUser->hasRole('dept_manager')
                ? Role::orderBy('name')->whereIn('name', ['dept_staff', 'supervisor', 'viewer'])->pluck('name')
                : Role::orderBy('name')->pluck('name'),
            'departments' => $departmentList,
            'filters'     => $request->only(['search', 'role', 'department_id', 'is_active']),
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        /** @var \App\Models\User $authUser */
        $authUser = auth()->user();

        if ($authUser && $authUser->hasRole('dept_manager')) {
            $departmentId = $request->input('department_id');
            if ((int) $departmentId !== (int) $authUser->department_id) {
                abort(403, 'لا يمكنك إضافة مستخدم إلى قسم آخر.');
            }

            $allowedRoles = ['dept_staff', 'supervisor', 'viewer'];
            if (!in_array($request->input('role'), $allowedRoles, true)) {
                abort(403, 'لا يمكنك إنشاء هذا الدور داخل القسم الخاص بك.');
            }
        }

        $validated = $request->validated();
        $role = $validated['role'];

        $user = User::create([
            'name'                => $validated['name'],
            'email'               => $validated['email'],
            'password'            => $validated['password'],
            'registration_number' => $validated['registration_number'] ?? null,
            'department_id'       => $validated['department_id'] ?? null,
            'is_active'           => $validated['is_active'] ?? true,
        ]);

        $user->assignRole($role);

        return redirect()->route('admin.users.index')
            ->with('success', 'تم إنشاء المستخدم بنجاح');
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        /** @var \App\Models\User $authUser */
        $authUser = auth()->user();

        if ($authUser && $authUser->hasRole('dept_manager')) {
            if ($user->department_id !== $authUser->department_id) {
                abort(403, 'لا يمكنك إدارة مستخدمين من أقسام أخرى.');
            }

            $requestedDepartment = (int) ($request->input('department_id') ?? $user->department_id);
            if ($requestedDepartment !== (int) $authUser->department_id) {
                abort(403, 'لا يمكنك نقل مستخدم إلى قسم آخر.');
            }

            $allowedRoles = ['dept_staff', 'supervisor', 'viewer'];
            if (!in_array($request->input('role'), $allowedRoles, true)) {
                abort(403, 'لا يمكنك تعيين هذا الدور داخل القسم الخاص بك.');
            }
        }

        $validated = $request->validated();

        $updateData = [
            'name'                => $validated['name'],
            'email'               => $validated['email'],
            'registration_number' => $validated['registration_number'] ?? null,
            'department_id'       => $validated['department_id'] ?? null,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = $validated['password'];
        }

        $user->update($updateData);
        $user->syncRoles([$validated['role']]);

        return redirect()->route('admin.users.index')
            ->with('success', 'تم تحديث بيانات المستخدم بنجاح');
    }

    public function toggleActive(User $user)
    {
        /** @var \App\Models\User $authUser */
        $authUser = auth()->user();

        if ($authUser && $authUser->hasRole('dept_manager')) {
            if ($user->department_id !== $authUser->department_id) {
                abort(403, 'لا يمكنك تعديل مستخدمين من أقسام أخرى.');
            }
        }

        $newActive = !$user->is_active;
        $user->update(['is_active' => $newActive]);

        return redirect()->back()
            ->with('success', $newActive ? 'تم تفعيل المستخدم' : 'تم إيقاف المستخدم');
    }

    public function destroy(User $user)
    {
        /** @var \App\Models\User $authUser */
        $authUser = auth()->user();

        if ($authUser && $authUser->hasRole('dept_manager')) {
            if ($user->department_id !== $authUser->department_id) {
                abort(403, 'لا يمكنك حذف مستخدمين من أقسام أخرى.');
            }
        }

        if ($user->hasRole('super_admin') && User::role('super_admin')->count() === 1) {
            return redirect()->route('admin.users.index')
                ->with('error', 'لا يمكن حذف المسؤول الأخير في النظام');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'تم حذف المستخدم بنجاح');
    }
}
