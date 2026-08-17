<?php

use App\Models\Department;
use App\Models\FacultyMember;
use App\Models\Project;
use App\Models\Specialization;
use App\Models\User;
use Database\Seeders\ProjectStatusSeeder;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);
    $this->seed(ProjectStatusSeeder::class);
    $this->seed(\Database\Seeders\SemesterSeeder::class);
});

// ── Helpers ───────────────────────────────────────────────────────────────────

/** Minimal FK chain: department + specialization + supervisor faculty member. */
function rvSetup(): array
{
    $dept       = Department::factory()->create();
    $spec       = Specialization::factory()->create(['department_id' => $dept->id]);
    $supervisor = FacultyMember::factory()->create();
    $supervisor->departments()->sync([$dept->id]);

    return compact('dept', 'spec', 'supervisor');
}

function staffInDept(int $deptId): User
{
    $u = User::factory()->create(['department_id' => $deptId]);
    $u->assignRole('dept_staff');
    return $u;
}

function managerInDept(int $deptId): User
{
    $u = User::factory()->create(['department_id' => $deptId]);
    $u->assignRole('dept_manager');
    return $u;
}

function pendingRvProject(int $deptId, int $specId, int $supervisorId): Project
{
    return Project::factory()->create([
        'department_id'     => $deptId,
        'specialization_id' => $specId,
        'supervisor_id'     => $supervisorId,
        'current_status_id' => 2, // proposal_submitted
        'is_deleted'        => false,
    ]);
}

function archivedRvProject(int $deptId, int $specId, int $supervisorId): Project
{
    return Project::factory()->create([
        'department_id'     => $deptId,
        'specialization_id' => $specId,
        'supervisor_id'     => $supervisorId,
        'current_status_id' => 1, // archived
        'is_deleted'        => false,
    ]);
}

function validProjectPayload(int $deptId, int $specId, int $supervisorId): array
{
    return [
        'project_title'     => 'نظام تجريبي لاختبار الصلاحيات',
        'description'       => 'وصف المشروع التجريبي لاختبار صلاحيات المستخدمين',
        'academic_year'     => '2025/2026',
        'semester'          => 'خريف',
        'degree_level'      => 'bachelor',
        'department_id'     => $deptId,
        'specialization_id' => $specId,
        'supervisor_id'     => $supervisorId,
        'students'          => [
            ['full_name' => 'أحمد محمد', 'registration_number' => '2025001'],
        ],
    ];
}

// ═══════════════════════════════════════════════════════════════════════════════
// super_admin — full access to everything
// ═══════════════════════════════════════════════════════════════════════════════

test('super_admin can access dashboard', function () {
    $this->actingAs(userWithRole('super_admin'))
        ->get(route('dashboard'))
        ->assertOk();
});

test('super_admin can access departments index', function () {
    $this->actingAs(userWithRole('super_admin'))
        ->get(route('departments.index'))
        ->assertOk();
});

test('super_admin can create department', function () {
    $this->actingAs(userWithRole('super_admin'))
        ->post(route('departments.store'), [
            'name' => 'قسم الاختبار', 'code' => 'TST', 'description' => 'قسم تجريبي',
        ])
        ->assertRedirect(route('departments.index'));

    $this->assertDatabaseHas('departments', ['code' => 'TST']);
});

test('super_admin can access projects index', function () {
    $this->actingAs(userWithRole('super_admin'))
        ->get(route('projects.index'))
        ->assertOk();
});

test('super_admin can access project create page', function () {
    $this->actingAs(userWithRole('super_admin'))
        ->get(route('projects.create'))
        ->assertOk();
});

test('super_admin can create project', function () {
    ['dept' => $dept, 'spec' => $spec, 'supervisor' => $sv] = rvSetup();

    $this->actingAs(userWithRole('super_admin'))
        ->post(route('projects.store'), validProjectPayload($dept->id, $spec->id, $sv->id))
        ->assertRedirect();

    $this->assertDatabaseHas('projects', ['project_title' => 'نظام تجريبي لاختبار الصلاحيات']);
});

test('super_admin can soft-delete a project', function () {
    ['dept' => $dept, 'spec' => $spec, 'supervisor' => $sv] = rvSetup();
    $project = archivedRvProject($dept->id, $spec->id, $sv->id);

    $this->actingAs(userWithRole('super_admin'))
        ->delete(route('projects.destroy', $project->id))
        ->assertRedirect(route('projects.index'));

    $this->assertDatabaseHas('projects', ['id' => $project->id, 'is_deleted' => true]);
});

test('super_admin can access import', function () {
    $this->actingAs(userWithRole('super_admin'))
        ->get(route('import.index'))
        ->assertOk();
});

test('super_admin can access department report', function () {
    $this->actingAs(userWithRole('super_admin'))
        ->get(route('reports.department'))
        ->assertOk();
});

test('super_admin can access supervisors report', function () {
    $this->actingAs(userWithRole('super_admin'))
        ->get(route('reports.supervisors'))
        ->assertOk();
});

test('super_admin can access yearly report', function () {
    $this->actingAs(userWithRole('super_admin'))
        ->get(route('reports.yearly'))
        ->assertOk();
});

test('super_admin can access user management', function () {
    $this->actingAs(userWithRole('super_admin'))
        ->get(route('admin.users.index'))
        ->assertOk();
});

// ═══════════════════════════════════════════════════════════════════════════════
// dept_manager — manage own department; cannot import
// ═══════════════════════════════════════════════════════════════════════════════

test('dept_manager can access dashboard', function () {
    $this->actingAs(userWithRole('dept_manager'))
        ->get(route('dashboard'))
        ->assertOk();
});

test('dept_manager can access departments index', function () {
    $this->actingAs(userWithRole('dept_manager'))
        ->get(route('departments.index'))
        ->assertOk();
});

test('dept_manager can create project in their department', function () {
    ['dept' => $dept, 'spec' => $spec, 'supervisor' => $sv] = rvSetup();
    $mgr = managerInDept($dept->id);

    $this->actingAs($mgr)
        ->post(route('projects.store'), validProjectPayload($dept->id, $spec->id, $sv->id))
        ->assertRedirect();

    // every new project starts in progress (status 5), regardless of role
    $this->assertDatabaseHas('projects', [
        'project_title'     => 'نظام تجريبي لاختبار الصلاحيات',
        'current_status_id' => 5,
    ]);
});

test('dept_manager can soft-delete a project', function () {
    ['dept' => $dept, 'spec' => $spec, 'supervisor' => $sv] = rvSetup();
    $project = archivedRvProject($dept->id, $spec->id, $sv->id);
    $mgr     = managerInDept($dept->id);

    $this->actingAs($mgr)
        ->delete(route('projects.destroy', $project->id))
        ->assertRedirect(route('projects.index'));

    $this->assertDatabaseHas('projects', ['id' => $project->id, 'is_deleted' => true]);
});

test('dept_manager can access department report', function () {
    $this->actingAs(userWithRole('dept_manager'))
        ->get(route('reports.department'))
        ->assertOk();
});

test('dept_manager can assign examiner to project', function () {
    ['dept' => $dept, 'spec' => $spec, 'supervisor' => $sv] = rvSetup();
    $project  = archivedRvProject($dept->id, $spec->id, $sv->id);
    $examiner = FacultyMember::factory()->create();
    $examiner->departments()->sync([$dept->id]);
    $mgr = managerInDept($dept->id);

    $this->actingAs($mgr)
        ->post(route('projects.assign-faculty-member', $project->id), ['faculty_member_id' => $examiner->id])
        ->assertRedirect();

    $this->assertDatabaseHas('project_faculty_members', [
        'project_id'        => $project->id,
        'faculty_member_id' => $examiner->id,
    ]);
});

test('dept_manager can enter final score', function () {
    ['dept' => $dept, 'spec' => $spec, 'supervisor' => $sv] = rvSetup();
    $project = archivedRvProject($dept->id, $spec->id, $sv->id);
    $mgr     = managerInDept($dept->id);

    $this->actingAs($mgr)
        ->patch(route('projects.score', $project->id), ['final_score' => 85])
        ->assertRedirect();

    $this->assertDatabaseHas('projects', ['id' => $project->id, 'final_score' => 85]);
});

test('dept_manager cannot access import', function () {
    $this->actingAs(userWithRole('dept_manager'))
        ->get(route('import.index'))
        ->assertForbidden();
});

test('dept_manager cannot delete any department', function () {
    ['dept' => $dept] = rvSetup();
    $mgr = managerInDept($dept->id);

    $this->actingAs($mgr)
        ->delete(route('departments.destroy', $dept->id))
        ->assertForbidden();

    $this->assertDatabaseHas('departments', ['id' => $dept->id]);
});

test('dept_manager cannot create a department', function () {
    managerInDept(Department::factory()->create()->id);
    $mgr = managerInDept(Department::factory()->create()->id);

    $this->actingAs($mgr)
        ->post(route('departments.store'), [
            'name' => 'قسم جديد', 'code' => 'NEW', 'description' => null,
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('departments', ['code' => 'NEW']);
});

test('dept_manager can update their own department', function () {
    $dept = Department::factory()->create(['name' => 'القسم الأصلي', 'code' => 'ORI']);
    $mgr  = managerInDept($dept->id);

    $this->actingAs($mgr)
        ->put(route('departments.update', $dept->id), [
            'name' => 'القسم المحدث', 'code' => 'ORI', 'description' => null,
        ])
        ->assertRedirect(route('departments.index'));

    $this->assertDatabaseHas('departments', ['id' => $dept->id, 'name' => 'القسم المحدث']);
});

test('dept_manager cannot update another department', function () {
    $ownDept   = Department::factory()->create();
    $otherDept = Department::factory()->create(['name' => 'قسم آخر', 'code' => 'OTH']);
    $mgr       = managerInDept($ownDept->id);

    $this->actingAs($mgr)
        ->put(route('departments.update', $otherDept->id), [
            'name' => 'محاولة تعديل', 'code' => 'OTH', 'description' => null,
        ])
        ->assertForbidden();

    $this->assertDatabaseHas('departments', ['id' => $otherDept->id, 'name' => 'قسم آخر']);
});

test('super_admin can delete department without linked projects', function () {
    $dept = Department::factory()->create();

    $this->actingAs(userWithRole('super_admin'))
        ->delete(route('departments.destroy', $dept->id))
        ->assertRedirect(route('departments.index'));

    $this->assertDatabaseMissing('departments', ['id' => $dept->id]);
});

test('super_admin cannot delete department that has linked projects', function () {
    ['dept' => $dept, 'spec' => $spec, 'supervisor' => $sv] = rvSetup();
    archivedRvProject($dept->id, $spec->id, $sv->id);

    $this->actingAs(userWithRole('super_admin'))
        ->delete(route('departments.destroy', $dept->id))
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->assertDatabaseHas('departments', ['id' => $dept->id]);
});

// ═══════════════════════════════════════════════════════════════════════════════
// dept_staff — add pending projects in own dept; edit pending own-dept only
// ═══════════════════════════════════════════════════════════════════════════════

test('dept_staff can access dashboard', function () {
    $this->actingAs(userWithRole('dept_staff'))
        ->get(route('dashboard'))
        ->assertOk();
});

test('dept_staff can view all projects', function () {
    $this->actingAs(userWithRole('dept_staff'))
        ->get(route('projects.index'))
        ->assertOk();
});

test('dept_staff can access project create page', function () {
    $this->actingAs(userWithRole('dept_staff'))
        ->get(route('projects.create'))
        ->assertOk();
});

test('dept_staff can create project in their own department', function () {
    ['dept' => $dept, 'spec' => $spec, 'supervisor' => $sv] = rvSetup();
    $staff = staffInDept($dept->id);

    $this->actingAs($staff)
        ->post(route('projects.store'), validProjectPayload($dept->id, $spec->id, $sv->id))
        ->assertRedirect();

    // every new project starts in progress (status 5), regardless of role
    $this->assertDatabaseHas('projects', [
        'project_title'     => 'نظام تجريبي لاختبار الصلاحيات',
        'current_status_id' => 5,
    ]);
});

test('dept_staff can edit their own pending project in same department', function () {
    ['dept' => $dept, 'spec' => $spec, 'supervisor' => $sv] = rvSetup();
    $project = pendingRvProject($dept->id, $spec->id, $sv->id);
    $staff   = staffInDept($dept->id);

    $this->actingAs($staff)
        ->get(route('projects.edit', $project->id))
        ->assertOk();
});

test('dept_staff cannot create project in another department', function () {
    ['dept' => $dept, 'spec' => $spec, 'supervisor' => $sv] = rvSetup();
    $otherDept = Department::factory()->create();
    $staff     = staffInDept($otherDept->id); // staff belongs to otherDept, not $dept

    $this->actingAs($staff)
        ->post(route('projects.store'), validProjectPayload($dept->id, $spec->id, $sv->id))
        ->assertForbidden();
});

test('dept_staff cannot edit pending project from a different department', function () {
    ['dept' => $dept, 'spec' => $spec, 'supervisor' => $sv] = rvSetup();
    $project   = pendingRvProject($dept->id, $spec->id, $sv->id);
    $otherDept = Department::factory()->create();
    $staff     = staffInDept($otherDept->id); // staff is in otherDept, project is in $dept

    $this->actingAs($staff)
        ->get(route('projects.edit', $project->id))
        ->assertForbidden();
});

test('dept_staff cannot edit approved (archived) project even in same department', function () {
    ['dept' => $dept, 'spec' => $spec, 'supervisor' => $sv] = rvSetup();
    $project = archivedRvProject($dept->id, $spec->id, $sv->id); // status = 1
    $staff   = staffInDept($dept->id);

    $this->actingAs($staff)
        ->get(route('projects.edit', $project->id))
        ->assertForbidden();
});

test('dept_staff cannot delete a project', function () {
    ['dept' => $dept, 'spec' => $spec, 'supervisor' => $sv] = rvSetup();
    $project = archivedRvProject($dept->id, $spec->id, $sv->id);
    $staff   = staffInDept($dept->id);

    $this->actingAs($staff)
        ->delete(route('projects.destroy', $project->id))
        ->assertForbidden();
});

test('dept_staff cannot access import', function () {
    $this->actingAs(userWithRole('dept_staff'))
        ->get(route('import.index'))
        ->assertForbidden();
});

test('dept_staff cannot access department report', function () {
    $this->actingAs(userWithRole('dept_staff'))
        ->get(route('reports.department'))
        ->assertForbidden();
});

test('dept_staff cannot access departments management', function () {
    $this->actingAs(userWithRole('dept_staff'))
        ->get(route('departments.index'))
        ->assertForbidden();
});
