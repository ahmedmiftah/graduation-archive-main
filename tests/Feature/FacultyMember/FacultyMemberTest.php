<?php

use App\Models\AcademicDegree;
use App\Models\Department;
use App\Models\FacultyMember;
use App\Models\Project;
use App\Models\Specialization;
use Database\Seeders\AcademicDegreeSeeder;
use Database\Seeders\ProjectStatusSeeder;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);
    $this->seed(ProjectStatusSeeder::class);
    $this->seed(AcademicDegreeSeeder::class);
});

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Creates a dept, spec, supervisor, project, and one faculty member (examiner) in that dept.
 */
function makeFacultyMemberProject(): array
{
    $dept       = Department::factory()->create();
    $spec       = Specialization::factory()->create(['department_id' => $dept->id]);
    $supervisor = FacultyMember::factory()->create();
    $project    = Project::factory()->create([
        'department_id'     => $dept->id,
        'specialization_id' => $spec->id,
        'supervisor_id'     => $supervisor->id,
        'current_status_id' => 1,
        'is_deleted'        => false,
    ]);
    $examiner = FacultyMember::factory()->create();
    $examiner->departments()->sync([$dept->id]);

    return compact('dept', 'spec', 'supervisor', 'project', 'examiner');
}

// ── FacultyMemberController ────────────────────────────────────────────────────

test('dept_manager can create faculty member', function () {
    $dept   = Department::factory()->create();
    $degree = AcademicDegree::where('degree_code', 'Ph.D')->first();

    $this->actingAs(userWithRole('dept_manager'))
        ->post(route('faculty-members.store'), [
            'full_name'      => 'Dr. Ahmed Al-Rashid',
            'phone_number'   => '0512345678',
            'email'          => 'ahmed.rashid@college.com',
            'degree_id'      => $degree->id,
            'department_ids' => [$dept->id],
        ])
        ->assertRedirect(route('faculty-members.index'));

    $this->assertDatabaseHas('faculty_members', [
        'full_name' => 'Dr. Ahmed Al-Rashid',
        'email'     => 'ahmed.rashid@college.com',
    ]);
});

test('dept_staff cannot create faculty member', function () {
    $dept   = Department::factory()->create();
    $degree = AcademicDegree::first();

    $this->actingAs(userWithRole('dept_staff'))
        ->post(route('faculty-members.store'), [
            'full_name'      => 'Dr. Test',
            'phone_number'   => '0512345678',
            'email'          => 'test@college.com',
            'degree_id'      => $degree->id,
            'department_ids' => [$dept->id],
        ])
        ->assertForbidden();
});

test('faculty member requires full_name, phone_number, email, degree_id and department_ids', function () {
    $this->actingAs(userWithRole('dept_manager'))
        ->post(route('faculty-members.store'), [
            'full_name'      => '',
            'phone_number'   => '',
            'email'          => '',
            'degree_id'      => '',
            'department_ids' => [],
        ])
        ->assertSessionHasErrors(['full_name', 'phone_number', 'email', 'degree_id', 'department_ids']);
});

test('cannot delete faculty member linked to projects', function () {
    $data    = makeFacultyMemberProject();
    $manager = userWithRole('dept_manager');

    $data['project']->facultyMembers()->attach($data['examiner']->id, ['assigned_by' => $manager->id]);

    $this->actingAs($manager)
        ->delete(route('faculty-members.destroy', $data['examiner']->id))
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->assertDatabaseHas('faculty_members', ['id' => $data['examiner']->id]);
});

test('faculty members filtered by department correctly', function () {
    $deptA = Department::factory()->create();
    $deptB = Department::factory()->create();

    FacultyMember::factory()->count(3)->create()->each(fn ($m) => $m->departments()->sync([$deptA->id]));
    FacultyMember::factory()->count(2)->create()->each(fn ($m) => $m->departments()->sync([$deptB->id]));

    $this->actingAs(userWithRole('dept_manager'))
        ->get(route('faculty-members.index', ['department_id' => $deptA->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('FacultyMembers/Index')
            ->has('facultyMembers', 3)
        );
});

// ── ProjectFacultyMemberController ───────────────────────────────────────────

test('dept_manager can assign faculty member to project', function () {
    $data    = makeFacultyMemberProject();
    $manager = userWithRole('dept_manager');

    $this->actingAs($manager)
        ->post(route('projects.assign-faculty-member', $data['project']->id), [
            'faculty_member_id' => $data['examiner']->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('project_faculty_members', [
        'project_id'        => $data['project']->id,
        'faculty_member_id' => $data['examiner']->id,
    ]);
});

test('cannot assign more than 2 faculty members to same project', function () {
    $data      = makeFacultyMemberProject();
    $manager   = userWithRole('dept_manager');
    $examiner2 = FacultyMember::factory()->create();
    $examiner3 = FacultyMember::factory()->create();

    // Fill the 2-examiner cap via ORM
    $data['project']->facultyMembers()->attach($data['examiner']->id, ['assigned_by' => $manager->id]);
    $data['project']->facultyMembers()->attach($examiner2->id, ['assigned_by' => $manager->id]);

    // Third assignment must be rejected
    $this->actingAs($manager)
        ->post(route('projects.assign-faculty-member', $data['project']->id), [
            'faculty_member_id' => $examiner3->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->assertDatabaseMissing('project_faculty_members', [
        'project_id'        => $data['project']->id,
        'faculty_member_id' => $examiner3->id,
    ]);
});

test('cannot assign same faculty member twice to same project', function () {
    $data    = makeFacultyMemberProject();
    $manager = userWithRole('dept_manager');

    // First assignment via ORM
    $data['project']->facultyMembers()->attach($data['examiner']->id, ['assigned_by' => $manager->id]);

    // Second assignment of same faculty member must be rejected
    $this->actingAs($manager)
        ->post(route('projects.assign-faculty-member', $data['project']->id), [
            'faculty_member_id' => $data['examiner']->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->assertDatabaseCount('project_faculty_members', 1);
});

test('dept_manager can remove faculty member from project', function () {
    $data    = makeFacultyMemberProject();
    $manager = userWithRole('dept_manager');

    $data['project']->facultyMembers()->attach($data['examiner']->id, ['assigned_by' => $manager->id]);

    $this->actingAs($manager)
        ->delete(route('projects.remove-faculty-member', [$data['project']->id, $data['examiner']->id]))
        ->assertRedirect();

    $this->assertDatabaseMissing('project_faculty_members', [
        'project_id'        => $data['project']->id,
        'faculty_member_id' => $data['examiner']->id,
    ]);
});

// ── EvaluationController ───────────────────────────────────────────────────────

test('dept_manager can add evaluation notes', function () {
    $data    = makeFacultyMemberProject();
    $manager = userWithRole('dept_manager');

    $data['project']->facultyMembers()->attach($data['examiner']->id, ['assigned_by' => $manager->id]);

    $this->actingAs($manager)
        ->post(route('projects.evaluation', $data['project']->id), [
            'faculty_member_id' => $data['examiner']->id,
            'notes'             => 'مشروع متميز يستحق التقدير',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('evaluations', [
        'project_id'        => $data['project']->id,
        'faculty_member_id' => $data['examiner']->id,
        'notes'             => 'مشروع متميز يستحق التقدير',
    ]);
});

test('dept_manager can set final score', function () {
    $data = makeFacultyMemberProject();

    $this->actingAs(userWithRole('dept_manager'))
        ->patch(route('projects.score', $data['project']->id), ['final_score' => 85])
        ->assertRedirect();

    $this->assertDatabaseHas('projects', [
        'id'          => $data['project']->id,
        'final_score' => 85,
    ]);
});

test('dept_staff cannot set final score', function () {
    $data = makeFacultyMemberProject();

    $this->actingAs(userWithRole('dept_staff'))
        ->patch(route('projects.score', $data['project']->id), ['final_score' => 75])
        ->assertForbidden();
});

test('score must be between 0 and 100', function () {
    $data    = makeFacultyMemberProject();
    $manager = userWithRole('dept_manager');

    $this->actingAs($manager)
        ->patch(route('projects.score', $data['project']->id), ['final_score' => -1])
        ->assertSessionHasErrors('final_score');

    $this->actingAs($manager)
        ->patch(route('projects.score', $data['project']->id), ['final_score' => 101])
        ->assertSessionHasErrors('final_score');
});
