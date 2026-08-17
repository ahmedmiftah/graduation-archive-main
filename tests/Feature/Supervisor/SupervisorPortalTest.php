<?php

use App\Models\Department;
use App\Models\FacultyMember;
use App\Models\Project;
use App\Models\ProjectProposal;
use App\Models\Specialization;
use App\Models\User;
use Database\Seeders\LifecycleStageSeeder;
use Database\Seeders\ProjectStatusSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SystemSettingSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);
    $this->seed(ProjectStatusSeeder::class);
    $this->seed(SystemSettingSeeder::class);
    $this->seed(LifecycleStageSeeder::class);
});

// ── Helpers ───────────────────────────────────────────────────────────────────

function makeLoggedInSupervisor(array $overrides = []): FacultyMember
{
    $facultyMember = FacultyMember::factory()->create($overrides);
    $user = User::factory()->create(['email' => $facultyMember->email]);
    $user->assignRole('supervisor');
    $facultyMember->update(['user_id' => $user->id]);

    return $facultyMember->fresh();
}

// ── Access control ───────────────────────────────────────────────────────────

test('supervisor can access their own dashboard', function () {
    $supervisor = makeLoggedInSupervisor();

    $this->actingAs($supervisor->user)
        ->get(route('supervisor.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Supervisor/Dashboard'));
});

test('dept_staff cannot access the supervisor portal', function () {
    $this->actingAs(userWithRole('dept_staff'))
        ->get(route('supervisor.dashboard'))
        ->assertForbidden();
});

test('a generic /dashboard visit redirects a supervisor to /supervisor/dashboard', function () {
    $supervisor = makeLoggedInSupervisor();

    $this->actingAs($supervisor->user)
        ->get(route('dashboard'))
        ->assertRedirect(route('supervisor.dashboard'));
});

// ── Dashboard stats ──────────────────────────────────────────────────────────

test('dashboard stats correctly count projects and pending proposals', function () {
    $supervisor = makeLoggedInSupervisor();
    $dept = Department::factory()->create();
    $spec = Specialization::factory()->create(['department_id' => $dept->id]);

    Project::factory()->create([
        'department_id' => $dept->id, 'specialization_id' => $spec->id,
        'supervisor_id' => $supervisor->id, 'current_status_id' => 5, 'is_deleted' => false,
    ]);
    Project::factory()->create([
        'department_id' => $dept->id, 'specialization_id' => $spec->id,
        'supervisor_id' => $supervisor->id, 'current_status_id' => 1, 'is_deleted' => false,
    ]);
    ProjectProposal::factory()->create([
        'department_id' => $dept->id, 'specialization_id' => $spec->id,
        'supervisor_id' => $supervisor->id, 'status' => 'pending', 'supervisor_note' => null,
    ]);

    $this->actingAs($supervisor->user)
        ->get(route('supervisor.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('Supervisor/Dashboard')
            ->where('stats.total_projects', 2)
            ->where('stats.in_progress_count', 1)
            ->where('stats.completed_count', 1)
            ->where('stats.needs_action_count', 1)
        );
});

test('a pending proposal with a supervisor_note already set does not count as needing action', function () {
    $supervisor = makeLoggedInSupervisor();
    $dept = Department::factory()->create();
    $spec = Specialization::factory()->create(['department_id' => $dept->id]);

    ProjectProposal::factory()->create([
        'department_id' => $dept->id, 'specialization_id' => $spec->id,
        'supervisor_id' => $supervisor->id, 'status' => 'pending', 'supervisor_note' => 'راجعتها بالفعل',
    ]);

    $this->actingAs($supervisor->user)
        ->get(route('supervisor.dashboard'))
        ->assertInertia(fn ($page) => $page->where('stats.needs_action_count', 0));
});

// ── "مشاريعي" scoping ────────────────────────────────────────────────────────

test('supervisor only sees proposals they are assigned to in their list', function () {
    $supervisorA = makeLoggedInSupervisor(['email' => 'a@college.edu']);
    $supervisorB = FacultyMember::factory()->create(['email' => 'b@college.edu']);
    $dept = Department::factory()->create();
    $spec = Specialization::factory()->create(['department_id' => $dept->id]);

    ProjectProposal::factory()->create([
        'department_id' => $dept->id, 'specialization_id' => $spec->id,
        'supervisor_id' => $supervisorA->id, 'title' => 'مشروع أ',
    ]);
    ProjectProposal::factory()->create([
        'department_id' => $dept->id, 'specialization_id' => $spec->id,
        'supervisor_id' => $supervisorB->id, 'title' => 'مشروع ب',
    ]);

    $this->actingAs($supervisorA->user)
        ->get(route('supervisor.proposals.index'))
        ->assertInertia(fn ($page) => $page
            ->component('Supervisor/Proposals/Index')
            ->has('proposals', 1)
            ->where('proposals.0.title', 'مشروع أ')
        );
});

test('supervisor cannot view a proposal they are not assigned to', function () {
    $supervisor = makeLoggedInSupervisor();
    $dept = Department::factory()->create();
    $spec = Specialization::factory()->create(['department_id' => $dept->id]);
    $otherSupervisor = FacultyMember::factory()->create();

    $proposal = ProjectProposal::factory()->create([
        'department_id' => $dept->id, 'specialization_id' => $spec->id,
        'supervisor_id' => $otherSupervisor->id,
    ]);

    $this->actingAs($supervisor->user)
        ->get(route('supervisor.proposals.show', $proposal->id))
        ->assertForbidden();
});

// ── Supervisor note ──────────────────────────────────────────────────────────

test('supervisor can write their own supervisor_note', function () {
    $supervisor = makeLoggedInSupervisor();
    $dept = Department::factory()->create();
    $spec = Specialization::factory()->create(['department_id' => $dept->id]);

    $proposal = ProjectProposal::factory()->create([
        'department_id' => $dept->id, 'specialization_id' => $spec->id,
        'supervisor_id' => $supervisor->id, 'status' => 'pending',
    ]);

    $this->actingAs($supervisor->user)
        ->patch(route('supervisor.proposals.update-note', $proposal->id), [
            'supervisor_note' => 'يرجى تقوية الفصل الخاص بالنتائج',
        ])
        ->assertRedirect();

    expect($proposal->fresh()->supervisor_note)->toBe('يرجى تقوية الفصل الخاص بالنتائج');
});

test('supervisor cannot write a note on a proposal they are not assigned to', function () {
    $supervisor = makeLoggedInSupervisor();
    $dept = Department::factory()->create();
    $spec = Specialization::factory()->create(['department_id' => $dept->id]);
    $otherSupervisor = FacultyMember::factory()->create();

    $proposal = ProjectProposal::factory()->create([
        'department_id' => $dept->id, 'specialization_id' => $spec->id,
        'supervisor_id' => $otherSupervisor->id,
    ]);

    $this->actingAs($supervisor->user)
        ->patch(route('supervisor.proposals.update-note', $proposal->id), ['supervisor_note' => 'محاولة تعديل'])
        ->assertForbidden();

    expect($proposal->fresh()->supervisor_note)->toBeNull();
});

test('dept_manager retains the ability to write both notes through the existing review action', function () {
    $supervisor = makeLoggedInSupervisor();
    $dept = Department::factory()->create();
    $spec = Specialization::factory()->create(['department_id' => $dept->id]);
    $manager = User::factory()->create(['department_id' => $dept->id]);
    $manager->assignRole('dept_manager');

    $proposal = ProjectProposal::factory()->create([
        'department_id' => $dept->id, 'specialization_id' => $spec->id,
        'supervisor_id' => $supervisor->id, 'status' => 'pending',
    ]);

    $this->actingAs($manager)
        ->post(route('proposals.change-status', $proposal->id), [
            'action'          => 'request_revision',
            'supervisor_note' => 'ملاحظة من القسم نيابة عن المشرف',
        ])
        ->assertRedirect();

    expect($proposal->fresh()->supervisor_note)->toBe('ملاحظة من القسم نيابة عن المشرف');
});
