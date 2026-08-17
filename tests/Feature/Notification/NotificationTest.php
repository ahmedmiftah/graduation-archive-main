<?php

use App\Models\Department;
use App\Models\FacultyMember;
use App\Models\Project;
use App\Models\ProjectProposal;
use App\Models\Specialization;
use App\Models\Student;
use App\Models\User;
use App\Notifications\ExaminerAssigned;
use App\Notifications\ProjectProposalStatusChanged;
use App\Notifications\ProjectReadyForDefense;
use App\Notifications\ProposalSubmitted;
use Database\Seeders\ProjectStatusSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SemesterSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);
    $this->seed(ProjectStatusSeeder::class);
    $this->seed(SystemSettingSeeder::class);
    $this->seed(SemesterSeeder::class);
});

// ── Helpers ───────────────────────────────────────────────────────────────────

function makeNotifDeps(): array
{
    $dept       = Department::factory()->create();
    $spec       = Specialization::factory()->create(['department_id' => $dept->id]);
    $supervisor = FacultyMember::factory()->create();
    $supervisor->departments()->sync([$dept->id]);

    return compact('dept', 'spec', 'supervisor');
}

function proposalPayload(array $deps, array $overrides = []): array
{
    return array_merge([
        'title'              => 'مقترح اختبار الإشعارات',
        'description'        => 'وصف تفصيلي لمقترح المشروع',
        'specialization_id'  => $deps['spec']->id,
        'academic_year'      => '2026',
        'semester'           => 'خريف',
        'submission_date'    => now()->toDateString(),
        'supervisor_id'      => $deps['supervisor']->id,
        'students'           => [
            ['full_name' => 'أحمد محمد', 'registration_number' => '2026001'],
        ],
    ], $overrides);
}

// ── Proposal submitted → department managers ────────────────────────────────

test('creating a proposal notifies only the department managers of that department', function () {
    Notification::fake();

    $deps        = makeNotifDeps();
    $staff       = staffInDept($deps['dept']->id);
    $manager     = managerInDept($deps['dept']->id);
    $otherDept   = Department::factory()->create();
    $otherMgr    = managerInDept($otherDept->id);

    $this->actingAs($staff)
        ->post(route('proposals.store'), proposalPayload($deps))
        ->assertRedirect();

    Notification::assertSentTo($manager, ProposalSubmitted::class, function ($notification) use ($manager) {
        return $notification->toDatabase($manager)['title'] === 'مقترح جديد بحاجة مراجعة';
    });
    Notification::assertNotSentTo($otherMgr, ProposalSubmitted::class);
});

test('resubmitting a proposal after revision sends a resubmission message', function () {
    Notification::fake();

    $deps    = makeNotifDeps();
    $staff   = staffInDept($deps['dept']->id);
    $manager = managerInDept($deps['dept']->id);
    $old     = ProjectProposal::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
        'status'            => 'needs_revision',
    ]);

    $this->actingAs($staff)
        ->post(route('proposals.store'), array_merge(proposalPayload($deps), [
            'replaces_proposal_id' => $old->id,
        ]))
        ->assertRedirect();

    Notification::assertSentTo($manager, ProposalSubmitted::class, function ($notification) use ($manager) {
        return $notification->toDatabase($manager)['title'] === 'إعادة تقديم مقترح بعد التعديل';
    });
});

// ── Status change → whole team + supervisor ─────────────────────────────────

test('approving a proposal notifies the student team, the creator and the supervisor', function () {
    Notification::fake();

    $deps    = makeNotifDeps();
    $staff   = staffInDept($deps['dept']->id);
    $manager = managerInDept($deps['dept']->id);

    $student = Student::factory()->create([
        'department_id'       => $deps['dept']->id,
        'specialization_id'   => $deps['spec']->id,
        'registration_number' => '2026001',
    ]);
    $studentUser = User::factory()->create();
    $studentUser->assignRole('student');
    $student->update(['user_id' => $studentUser->id]);

    $supervisorUser = User::factory()->create();
    $supervisorUser->assignRole('supervisor');
    $deps['supervisor']->update(['user_id' => $supervisorUser->id]);

    $proposal = ProjectProposal::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
        'status'            => 'pending',
        'created_by'        => $staff->id,
    ]);
    $proposal->students()->create([
        'full_name'           => $student->full_name,
        'registration_number' => '2026001',
        'student_id'          => $student->id,
    ]);

    $this->actingAs($manager)
        ->post(route('proposals.change-status', $proposal->id), ['action' => 'approve'])
        ->assertRedirect();

    Notification::assertSentTo($studentUser, ProjectProposalStatusChanged::class);
    Notification::assertSentTo($staff, ProjectProposalStatusChanged::class);
    Notification::assertSentTo($supervisorUser, ProjectProposalStatusChanged::class);
});

// ── Examiner assignment ──────────────────────────────────────────────────────

test('assigning an examiner notifies their linked account', function () {
    Notification::fake();

    $deps = makeNotifDeps();
    $project = Project::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
        'current_status_id' => 5,
    ]);

    $examinerUser = User::factory()->create();
    $examinerUser->assignRole('supervisor');
    $examiner = FacultyMember::factory()->create(['user_id' => $examinerUser->id]);

    $this->actingAs(managerInDept($deps['dept']->id))
        ->post(route('projects.assign-faculty-member', $project->id), ['faculty_member_id' => $examiner->id])
        ->assertRedirect();

    Notification::assertSentTo($examinerUser, ExaminerAssigned::class);
});

test('assigning an examiner without a linked account does not error', function () {
    Notification::fake();

    $deps = makeNotifDeps();
    $project = Project::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
        'current_status_id' => 5,
    ]);
    $examiner = FacultyMember::factory()->create(['user_id' => null]);

    $this->actingAs(managerInDept($deps['dept']->id))
        ->post(route('projects.assign-faculty-member', $project->id), ['faculty_member_id' => $examiner->id])
        ->assertRedirect()
        ->assertSessionHas('success');
});

// ── Ready for defense ─────────────────────────────────────────────────────────

function updateProjectPayload(array $deps, Project $project, array $overrides = []): array
{
    return array_merge([
        'project_title'     => $project->project_title,
        'description'       => $project->description,
        'degree_level'      => $project->degree_level,
        'academic_year'     => $project->academic_year,
        'semester'          => $project->semester,
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
        'current_status_id' => $project->current_status_id,
        'students'          => [
            ['full_name' => 'Ahmed Ali', 'registration_number' => 'ST001'],
        ],
    ], $overrides);
}

test('project becoming ready for defense notifies the supervisor', function () {
    Notification::fake();

    $deps = makeNotifDeps();
    $supervisorUser = User::factory()->create();
    $supervisorUser->assignRole('supervisor');
    $deps['supervisor']->update(['user_id' => $supervisorUser->id]);

    $project = Project::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
        'current_status_id' => 5, // in_progress
        'semester'          => 'خريف',
        'degree_level'      => 'bachelor',
    ]);

    $this->actingAs(managerInDept($deps['dept']->id))
        ->put(route('projects.update', $project->id), updateProjectPayload($deps, $project, ['current_status_id' => 6]))
        ->assertRedirect();

    Notification::assertSentTo($supervisorUser, ProjectReadyForDefense::class);
});

test('updating a project without changing status to ready-for-defense does not notify', function () {
    Notification::fake();

    $deps = makeNotifDeps();
    $supervisorUser = User::factory()->create();
    $supervisorUser->assignRole('supervisor');
    $deps['supervisor']->update(['user_id' => $supervisorUser->id]);

    $project = Project::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
        'current_status_id' => 5,
        'semester'          => 'خريف',
        'degree_level'      => 'bachelor',
    ]);

    $this->actingAs(managerInDept($deps['dept']->id))
        ->put(route('projects.update', $project->id), updateProjectPayload($deps, $project, ['current_status_id' => 5]))
        ->assertRedirect();

    Notification::assertNotSentTo($supervisorUser, ProjectReadyForDefense::class);
});

// ── Notification center ──────────────────────────────────────────────────────

test('a user can view only their own notifications', function () {
    $me    = userWithRole('dept_staff');
    $other = userWithRole('dept_staff');
    $deps  = makeNotifDeps();
    $project = Project::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
    ]);

    $me->notify(new ProjectReadyForDefense($project));
    $other->notify(new ExaminerAssigned($project));

    $this->actingAs($me)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Notifications/Index')
            ->where('notifications.total', 1)
        );
});

test('marking a notification as read updates read_at', function () {
    $me = userWithRole('dept_staff');
    $project = Project::factory()->create([
        'department_id'     => Department::factory()->create()->id,
        'specialization_id' => Specialization::factory()->create()->id,
        'supervisor_id'     => FacultyMember::factory()->create()->id,
    ]);
    $me->notify(new ExaminerAssigned($project));
    $notificationId = $me->notifications()->first()->id;

    $this->actingAs($me)
        ->patch(route('notifications.read', $notificationId))
        ->assertRedirect();

    expect($me->notifications()->first()->read_at)->not->toBeNull();
});

test('a user cannot mark another user notification as read', function () {
    $me    = userWithRole('dept_staff');
    $other = userWithRole('dept_staff');
    $project = Project::factory()->create([
        'department_id'     => Department::factory()->create()->id,
        'specialization_id' => Specialization::factory()->create()->id,
        'supervisor_id'     => FacultyMember::factory()->create()->id,
    ]);
    $other->notify(new ExaminerAssigned($project));
    $notificationId = $other->notifications()->first()->id;

    $this->actingAs($me)
        ->patch(route('notifications.read', $notificationId))
        ->assertNotFound();
});

test('mark-all-as-read clears every unread notification for the user', function () {
    $me = userWithRole('dept_staff');
    $project = Project::factory()->create([
        'department_id'     => Department::factory()->create()->id,
        'specialization_id' => Specialization::factory()->create()->id,
        'supervisor_id'     => FacultyMember::factory()->create()->id,
    ]);
    $me->notify(new ExaminerAssigned($project));
    $me->notify(new ProjectReadyForDefense($project));

    $this->actingAs($me)
        ->patch(route('notifications.read-all'))
        ->assertRedirect();

    expect($me->unreadNotifications()->count())->toBe(0);
});

test('guest is redirected to login when visiting the notification center', function () {
    $this->get(route('notifications.index'))->assertRedirect(route('login'));
});
