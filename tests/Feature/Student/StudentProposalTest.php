<?php

use App\Models\Department;
use App\Models\FacultyMember;
use App\Models\Project;
use App\Models\ProjectProposal;
use App\Models\Specialization;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\LifecycleStageSeeder;
use Database\Seeders\ProjectStatusSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SemesterSeeder;
use Database\Seeders\SystemSettingSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);
    $this->seed(ProjectStatusSeeder::class);
    $this->seed(SystemSettingSeeder::class);
    $this->seed(SemesterSeeder::class);
    $this->seed(LifecycleStageSeeder::class);
});

// ── Helpers ───────────────────────────────────────────────────────────────────

function makeProposalStudentDeps(): array
{
    $dept       = Department::factory()->create();
    $spec       = Specialization::factory()->create(['department_id' => $dept->id]);
    $supervisor = FacultyMember::factory()->create();
    $supervisor->departments()->sync([$dept->id]);

    return compact('dept', 'spec', 'supervisor');
}

function loggedInStudent(array $deps, array $overrides = []): Student
{
    $student = Student::factory()->create(array_merge([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'semester'          => 'خريف',
        'academic_year'     => '2026',
    ], $overrides));

    $user = User::factory()->create([
        'name'  => $student->full_name,
        'email' => $student->registration_number . '@students.local',
    ]);
    $user->assignRole('student');
    $student->update(['user_id' => $user->id]);

    return $student->fresh();
}

// ── Create ────────────────────────────────────────────────────────────────────

test('student can submit their own proposal and is auto-included as a team member', function () {
    $deps    = makeProposalStudentDeps();
    $student = loggedInStudent($deps);

    $this->actingAs($student->user)
        ->post(route('student.proposal.store'), [
            'title'             => 'نظام إدارة المكتبة',
            'description'       => 'وصف تفصيلي للمشروع',
            'specialization_id' => $deps['spec']->id,
            'academic_year'     => '2026',
            'semester'          => 'خريف',
            'students'          => [],
        ])
        ->assertRedirect();

    $proposal = ProjectProposal::where('title', 'نظام إدارة المكتبة')->firstOrFail();
    expect($proposal->status)->toBe('pending')
        ->and($proposal->created_by)->toBe($student->user->id)
        ->and($proposal->students)->toHaveCount(1)
        ->and($proposal->students->first()->student_id)->toBe($student->id);
});

test('student cannot submit a second proposal while one is already active', function () {
    $deps    = makeProposalStudentDeps();
    $student = loggedInStudent($deps);

    ProjectProposal::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'status'            => 'pending',
    ])->students()->create([
        'full_name'           => $student->full_name,
        'registration_number' => $student->registration_number,
        'student_id'          => $student->id,
    ]);

    $this->actingAs($student->user)
        ->post(route('student.proposal.store'), [
            'title'             => 'مقترح ثانٍ',
            'description'       => 'وصف',
            'specialization_id' => $deps['spec']->id,
            'academic_year'     => '2026',
            'semester'          => 'خريف',
            'students'          => [],
        ])
        ->assertForbidden();
});

// ── Shared team visibility ───────────────────────────────────────────────────

test('a teammate listed by registration_number can also view the shared proposal', function () {
    $deps      = makeProposalStudentDeps();
    $initiator = loggedInStudent($deps, ['registration_number' => '111111111']);
    $teammate  = loggedInStudent($deps, ['registration_number' => '222222222']);

    $this->actingAs($initiator->user)
        ->post(route('student.proposal.store'), [
            'title'             => 'مشروع جماعي',
            'description'       => 'وصف',
            'specialization_id' => $deps['spec']->id,
            'academic_year'     => '2026',
            'semester'          => 'خريف',
            'students'          => [
                ['full_name' => $teammate->full_name, 'registration_number' => $teammate->registration_number],
            ],
        ])
        ->assertRedirect();

    $proposal = ProjectProposal::where('title', 'مشروع جماعي')->firstOrFail();
    expect($proposal->students)->toHaveCount(2);

    $this->actingAs($teammate->user)
        ->get(route('student.proposal.show', $proposal->id))
        ->assertOk();
});

test('a student not on the team cannot view another team proposal', function () {
    $deps     = makeProposalStudentDeps();
    $owner    = loggedInStudent($deps, ['registration_number' => '333333333']);
    $outsider = loggedInStudent($deps, ['registration_number' => '444444444']);

    $proposal = ProjectProposal::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'status'            => 'pending',
    ]);
    $proposal->students()->create([
        'full_name'           => $owner->full_name,
        'registration_number' => $owner->registration_number,
        'student_id'          => $owner->id,
    ]);

    $this->actingAs($outsider->user)
        ->get(route('student.proposal.show', $proposal->id))
        ->assertForbidden();
});

// ── Edit restrictions ────────────────────────────────────────────────────────

test('student can edit their proposal while pending', function () {
    $deps    = makeProposalStudentDeps();
    $student = loggedInStudent($deps);
    $proposal = ProjectProposal::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'status'            => 'pending',
    ]);
    $proposal->students()->create([
        'full_name' => $student->full_name, 'registration_number' => $student->registration_number, 'student_id' => $student->id,
    ]);

    $this->actingAs($student->user)
        ->get(route('student.proposal.edit', $proposal->id))
        ->assertOk();
});

test('student cannot edit their proposal once approved', function () {
    $deps    = makeProposalStudentDeps();
    $student = loggedInStudent($deps);
    $proposal = ProjectProposal::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'status'            => 'approved',
    ]);
    $proposal->students()->create([
        'full_name' => $student->full_name, 'registration_number' => $student->registration_number, 'student_id' => $student->id,
    ]);

    $this->actingAs($student->user)
        ->get(route('student.proposal.edit', $proposal->id))
        ->assertForbidden();
});

test('student cannot change proposal status', function () {
    $deps    = makeProposalStudentDeps();
    $student = loggedInStudent($deps);
    $proposal = ProjectProposal::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'status'            => 'pending',
    ]);
    $proposal->students()->create([
        'full_name' => $student->full_name, 'registration_number' => $student->registration_number, 'student_id' => $student->id,
    ]);

    $this->actingAs($student->user)
        ->post(route('proposals.change-status', $proposal->id), ['action' => 'approve'])
        ->assertForbidden();
});

// ── Dashboard ────────────────────────────────────────────────────────────────

test('student dashboard shows an empty state when no proposal exists yet', function () {
    $deps    = makeProposalStudentDeps();
    $student = loggedInStudent($deps);

    $this->actingAs($student->user)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Student/Dashboard')->where('proposal', null));
});

test('a generic /dashboard visit redirects a student to /student/dashboard', function () {
    $deps    = makeProposalStudentDeps();
    $student = loggedInStudent($deps);

    $this->actingAs($student->user)
        ->get(route('dashboard'))
        ->assertRedirect(route('student.dashboard'));
});

// ── Timeline ─────────────────────────────────────────────────────────────────

test('timeline marks proposal_submission as current for a fresh pending proposal', function () {
    $deps    = makeProposalStudentDeps();
    $student = loggedInStudent($deps);
    $proposal = ProjectProposal::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'status'            => 'pending',
        'supervisor_id'     => null,
    ]);
    $proposal->students()->create([
        'full_name' => $student->full_name, 'registration_number' => $student->registration_number, 'student_id' => $student->id,
    ]);

    $this->actingAs($student->user)
        ->get(route('student.proposal.show', $proposal->id))
        ->assertInertia(fn ($page) => $page
            ->component('Student/Proposal/Show')
            ->where('timeline.1.key', 'proposal_submission')
            ->where('timeline.1.status', 'current')
            ->where('timeline.0.status', 'upcoming') // supervisor_selection: no supervisor set
        );
});

test('timeline marks project_start as reached once the proposal is approved', function () {
    $deps    = makeProposalStudentDeps();
    $student = loggedInStudent($deps);
    $proposal = ProjectProposal::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'status'            => 'approved',
        'supervisor_id'     => $deps['supervisor']->id,
    ]);
    $proposal->students()->create([
        'full_name' => $student->full_name, 'registration_number' => $student->registration_number, 'student_id' => $student->id,
    ]);
    Project::factory()->create([
        'proposal_id'       => $proposal->id,
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
        'current_status_id' => 5, // in_progress
    ]);

    $this->actingAs($student->user)
        ->get(route('student.proposal.show', $proposal->id))
        ->assertInertia(fn ($page) => $page
            ->component('Student/Proposal/Show')
            ->where('timeline.5.key', 'project_start')
            ->where('timeline.5.status', 'current')
        );
});

// ── Notes recorded by department during review ──────────────────────────────

test('dept_manager can record supervisor and department notes when changing status', function () {
    $deps    = makeProposalStudentDeps();
    $manager = User::factory()->create(['department_id' => $deps['dept']->id]);
    $manager->assignRole('dept_manager');

    $proposal = ProjectProposal::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'status'            => 'pending',
    ]);

    $this->actingAs($manager)
        ->post(route('proposals.change-status', $proposal->id), [
            'action'           => 'request_revision',
            'supervisor_note'  => 'يرجى توضيح منهجية العمل',
            'department_note'  => 'العنوان يحتاج تعديلاً بسيطاً',
        ])
        ->assertRedirect();

    $fresh = $proposal->fresh();
    expect($fresh->status)->toBe('needs_revision')
        ->and($fresh->supervisor_note)->toBe('يرجى توضيح منهجية العمل')
        ->and($fresh->department_note)->toBe('العنوان يحتاج تعديلاً بسيطاً');
});
