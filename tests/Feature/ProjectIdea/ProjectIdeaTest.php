<?php

use App\Models\Department;
use App\Models\ProjectIdea;
use App\Models\Specialization;
use Database\Seeders\ProjectStatusSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SystemSettingSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);
    $this->seed(ProjectStatusSeeder::class);
    $this->seed(SystemSettingSeeder::class);
});

// ── Helpers ───────────────────────────────────────────────────────────────────

function ideaDeps(): array
{
    $dept = Department::factory()->create();
    $spec = Specialization::factory()->create(['department_id' => $dept->id]);

    return compact('dept', 'spec');
}

function ideaPayload(array $deps, array $overrides = []): array
{
    return array_merge([
        'title'                    => 'نظام إدارة المكتبة الذكي',
        'description'              => 'وصف تفصيلي لفكرة المشروع',
        'specialization_id'        => $deps['spec']->id,
        'required_students_count'  => 2,
        'skills'                   => 'PHP, Laravel',
        'keywords'                 => 'مكتبة, ذكاء اصطناعي',
    ], $overrides);
}

// ── Supervisor CRUD ──────────────────────────────────────────────────────────

test('supervisor can publish a new project idea', function () {
    $deps       = ideaDeps();
    $supervisor = makeLoggedInSupervisor();

    $this->actingAs($supervisor->user)
        ->post(route('supervisor.ideas.store'), ideaPayload($deps))
        ->assertRedirect();

    $idea = ProjectIdea::where('title', 'نظام إدارة المكتبة الذكي')->firstOrFail();
    expect($idea->faculty_member_id)->toBe($supervisor->id);
    expect($idea->status)->toBe(ProjectIdea::STATUS_AVAILABLE);
    expect($idea->required_students_count)->toBe(2);
});

test('supervisor sees only their own ideas on the index page', function () {
    $deps        = ideaDeps();
    $supervisor  = makeLoggedInSupervisor();
    $otherSuper  = makeLoggedInSupervisor();

    ProjectIdea::factory()->create(['faculty_member_id' => $supervisor->id, 'specialization_id' => $deps['spec']->id]);
    ProjectIdea::factory()->create(['faculty_member_id' => $otherSuper->id, 'specialization_id' => $deps['spec']->id]);

    $this->actingAs($supervisor->user)
        ->get(route('supervisor.ideas.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Supervisor/Ideas/Index')
            ->has('ideas', 1)
        );
});

test('supervisor can update their own idea including its status', function () {
    $deps       = ideaDeps();
    $supervisor = makeLoggedInSupervisor();
    $idea       = ProjectIdea::factory()->create([
        'faculty_member_id' => $supervisor->id,
        'specialization_id' => $deps['spec']->id,
    ]);

    $this->actingAs($supervisor->user)
        ->put(route('supervisor.ideas.update', $idea->id), ideaPayload($deps, [
            'title'  => 'عنوان محدَّث',
            'status' => 'closed',
        ]))
        ->assertRedirect();

    $idea->refresh();
    expect($idea->title)->toBe('عنوان محدَّث');
    expect($idea->status)->toBe('closed');
});

test('supervisor cannot update another supervisor idea', function () {
    $deps       = ideaDeps();
    $supervisor = makeLoggedInSupervisor();
    $owner      = makeLoggedInSupervisor();
    $idea       = ProjectIdea::factory()->create([
        'faculty_member_id' => $owner->id,
        'specialization_id' => $deps['spec']->id,
    ]);

    $this->actingAs($supervisor->user)
        ->put(route('supervisor.ideas.update', $idea->id), ideaPayload($deps, ['status' => 'closed']))
        ->assertForbidden();
});

test('supervisor can delete their own idea', function () {
    $deps       = ideaDeps();
    $supervisor = makeLoggedInSupervisor();
    $idea       = ProjectIdea::factory()->create([
        'faculty_member_id' => $supervisor->id,
        'specialization_id' => $deps['spec']->id,
    ]);

    $this->actingAs($supervisor->user)
        ->delete(route('supervisor.ideas.destroy', $idea->id))
        ->assertRedirect();

    expect(ProjectIdea::find($idea->id))->toBeNull();
});

test('supervisor cannot delete another supervisor idea', function () {
    $deps       = ideaDeps();
    $supervisor = makeLoggedInSupervisor();
    $owner      = makeLoggedInSupervisor();
    $idea       = ProjectIdea::factory()->create([
        'faculty_member_id' => $owner->id,
        'specialization_id' => $deps['spec']->id,
    ]);

    $this->actingAs($supervisor->user)
        ->delete(route('supervisor.ideas.destroy', $idea->id))
        ->assertForbidden();

    expect(ProjectIdea::find($idea->id))->not->toBeNull();
});

test('publishing an idea requires title, description and specialization', function () {
    $deps       = ideaDeps();
    $supervisor = makeLoggedInSupervisor();

    $this->actingAs($supervisor->user)
        ->post(route('supervisor.ideas.store'), ideaPayload($deps, [
            'title'             => '',
            'specialization_id' => '',
        ]))
        ->assertSessionHasErrors(['title', 'specialization_id']);
});

test('required_students_count cannot exceed the system max_students_per_project setting', function () {
    $deps       = ideaDeps();
    $supervisor = makeLoggedInSupervisor();

    \App\Models\SystemSetting::current()->update(['max_students_per_project' => 3]);

    $this->actingAs($supervisor->user)
        ->post(route('supervisor.ideas.store'), ideaPayload($deps, ['required_students_count' => 4]))
        ->assertSessionHasErrors('required_students_count');
});

test('dept_staff cannot publish a project idea', function () {
    $deps  = ideaDeps();
    $staff = userWithRole('dept_staff');

    $this->actingAs($staff)
        ->post(route('supervisor.ideas.store'), ideaPayload($deps))
        ->assertForbidden();
});

// ── Student browse ───────────────────────────────────────────────────────────

test('student sees only available ideas when browsing', function () {
    $deps       = ideaDeps();
    $supervisor = makeLoggedInSupervisor();
    $student    = loggedInStudent($deps);

    ProjectIdea::factory()->create([
        'faculty_member_id' => $supervisor->id,
        'specialization_id' => $deps['spec']->id,
        'status'            => ProjectIdea::STATUS_AVAILABLE,
        'title'             => 'فكرة متاحة',
    ]);
    ProjectIdea::factory()->create([
        'faculty_member_id' => $supervisor->id,
        'specialization_id' => $deps['spec']->id,
        'status'            => ProjectIdea::STATUS_CLOSED,
        'title'             => 'فكرة مغلقة',
    ]);

    $this->actingAs($student->user)
        ->get(route('student.ideas.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Student/Ideas/Index')
            ->where('ideas.total', 1)
            ->where('ideas.data.0.title', 'فكرة متاحة')
        );
});

test('student can filter browsed ideas by specialization', function () {
    $deps        = ideaDeps();
    $supervisor  = makeLoggedInSupervisor();
    $student     = loggedInStudent($deps);
    $otherSpec   = Specialization::factory()->create(['department_id' => $deps['dept']->id]);

    ProjectIdea::factory()->create([
        'faculty_member_id' => $supervisor->id,
        'specialization_id' => $deps['spec']->id,
        'status'            => ProjectIdea::STATUS_AVAILABLE,
    ]);
    ProjectIdea::factory()->create([
        'faculty_member_id' => $supervisor->id,
        'specialization_id' => $otherSpec->id,
        'status'            => ProjectIdea::STATUS_AVAILABLE,
    ]);

    $this->actingAs($student->user)
        ->get(route('student.ideas.index', ['specialization_id' => $deps['spec']->id]))
        ->assertInertia(fn ($page) => $page->where('ideas.total', 1));
});

test('student can view an available idea detail page', function () {
    $deps       = ideaDeps();
    $supervisor = makeLoggedInSupervisor();
    $student    = loggedInStudent($deps);
    $idea       = ProjectIdea::factory()->create([
        'faculty_member_id' => $supervisor->id,
        'specialization_id' => $deps['spec']->id,
        'status'            => ProjectIdea::STATUS_AVAILABLE,
    ]);

    $this->actingAs($student->user)
        ->get(route('student.ideas.show', $idea->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Student/Ideas/Show')->where('idea.id', $idea->id));
});

test('student cannot view a non-available idea detail page', function () {
    $deps       = ideaDeps();
    $supervisor = makeLoggedInSupervisor();
    $student    = loggedInStudent($deps);
    $idea       = ProjectIdea::factory()->create([
        'faculty_member_id' => $supervisor->id,
        'specialization_id' => $deps['spec']->id,
        'status'            => ProjectIdea::STATUS_RESERVED,
    ]);

    $this->actingAs($student->user)
        ->get(route('student.ideas.show', $idea->id))
        ->assertForbidden();
});

test('supervisor cannot browse the student ideas page', function () {
    $supervisor = makeLoggedInSupervisor();

    $this->actingAs($supervisor->user)
        ->get(route('student.ideas.index'))
        ->assertForbidden();
});

test('unauthenticated visitor is redirected to login for both idea areas', function () {
    $this->get(route('supervisor.ideas.index'))->assertRedirect(route('login'));
    $this->get(route('student.ideas.index'))->assertRedirect(route('login'));
});
