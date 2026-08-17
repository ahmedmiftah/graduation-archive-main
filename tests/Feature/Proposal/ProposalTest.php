<?php

use App\Models\Department;
use App\Models\FacultyMember;
use App\Models\Project;
use App\Models\ProjectProposal;
use App\Models\Specialization;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\ProjectStatusSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SemesterSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);
    $this->seed(ProjectStatusSeeder::class);
    $this->seed(SystemSettingSeeder::class);
    $this->seed(SemesterSeeder::class);
    Storage::fake('public');
});

// ── Helpers ───────────────────────────────────────────────────────────────────

function makeProposalDeps(): array
{
    $dept       = Department::factory()->create();
    $spec       = Specialization::factory()->create(['department_id' => $dept->id]);
    $supervisor = FacultyMember::factory()->create();
    $supervisor->departments()->sync([$dept->id]);

    return compact('dept', 'spec', 'supervisor');
}

function validProposalPayload(array $deps, array $overrides = []): array
{
    return array_merge([
        'title'              => 'مقترح تجريبي لاختبار النظام',
        'description'        => 'وصف تفصيلي لمقترح المشروع التجريبي',
        'specialization_id'  => $deps['spec']->id,
        'academic_year'      => '2026',
        'semester'           => 'خريف',
        'submission_date'    => now()->toDateString(),
        'supervisor_id'      => $deps['supervisor']->id,
        'students'           => [
            ['full_name' => 'أحمد محمد', 'registration_number' => '2026001', 'phone_number' => '0500000000'],
        ],
    ], $overrides);
}

// ── Create ────────────────────────────────────────────────────────────────────

test('dept_staff can create a proposal with inline students and two files', function () {
    $deps  = makeProposalDeps();
    $staff = staffInDept($deps['dept']->id);

    $formFile     = UploadedFile::fake()->create('form.pdf', 100, 'application/pdf');
    $proposalFile = UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf');

    $this->actingAs($staff)
        ->post(route('proposals.store'), array_merge(validProposalPayload($deps), [
            'form_file'     => $formFile,
            'proposal_file' => $proposalFile,
        ]))
        ->assertRedirect();

    $proposal = ProjectProposal::where('title', 'مقترح تجريبي لاختبار النظام')->firstOrFail();

    expect($proposal->status)->toBe('pending');
    expect($proposal->department_id)->toBe($deps['dept']->id);
    expect($proposal->students)->toHaveCount(1);
    expect($proposal->form_file_path)->not->toBeNull();
    expect($proposal->proposal_file_path)->not->toBeNull();
});

test('exceeding the max students setting is rejected', function () {
    $deps  = makeProposalDeps();
    $staff = staffInDept($deps['dept']->id);

    $students = [];
    for ($i = 0; $i < 10; $i++) {
        $students[] = ['full_name' => "طالب {$i}", 'registration_number' => "2026{$i}"];
    }

    $this->actingAs($staff)
        ->post(route('proposals.store'), validProposalPayload($deps, ['students' => $students]))
        ->assertSessionHasErrors('students');
});

test('supervisor list on the create page is restricted to the user department', function () {
    $deps       = makeProposalDeps();
    $otherDept  = Department::factory()->create();
    $outsideSup = FacultyMember::factory()->create();
    $outsideSup->departments()->sync([$otherDept->id]);

    $staff = staffInDept($deps['dept']->id);

    $this->actingAs($staff)
        ->get(route('proposals.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Proposals/Index')
            ->where('filterOptions.supervisors.0.id', $deps['supervisor']->id)
            ->has('filterOptions.supervisors', 1)
        );
});

// ── Supervisor per-semester capacity ────────────────────────────────────────

test('assigning a supervisor beyond the semester capacity is rejected', function () {
    SystemSetting::current()->update(['max_projects_per_supervisor_per_semester' => 2]);

    $deps  = makeProposalDeps();
    $staff = staffInDept($deps['dept']->id);

    ProjectProposal::factory()->count(2)->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
        'academic_year'     => '2026',
        'semester'          => 'خريف',
        'status'            => 'pending',
    ]);

    $this->actingAs($staff)
        ->post(route('proposals.store'), validProposalPayload($deps))
        ->assertSessionHasErrors('supervisor_id');
});

test('rejected and superseded proposals do not count toward supervisor capacity', function () {
    SystemSetting::current()->update(['max_projects_per_supervisor_per_semester' => 2]);

    $deps  = makeProposalDeps();
    $staff = staffInDept($deps['dept']->id);

    ProjectProposal::factory()->create([
        'department_id' => $deps['dept']->id, 'specialization_id' => $deps['spec']->id,
        'supervisor_id' => $deps['supervisor']->id, 'academic_year' => '2026', 'semester' => 'خريف',
        'status' => 'rejected',
    ]);
    ProjectProposal::factory()->create([
        'department_id' => $deps['dept']->id, 'specialization_id' => $deps['spec']->id,
        'supervisor_id' => $deps['supervisor']->id, 'academic_year' => '2026', 'semester' => 'خريف',
        'status' => 'superseded',
    ]);

    $this->actingAs($staff)
        ->post(route('proposals.store'), validProposalPayload($deps))
        ->assertRedirect();
});

test('supervisor capacity only counts proposals in the same semester', function () {
    SystemSetting::current()->update(['max_projects_per_supervisor_per_semester' => 1]);

    $deps  = makeProposalDeps();
    $staff = staffInDept($deps['dept']->id);

    ProjectProposal::factory()->create([
        'department_id' => $deps['dept']->id, 'specialization_id' => $deps['spec']->id,
        'supervisor_id' => $deps['supervisor']->id, 'academic_year' => '2025', 'semester' => 'ربيع',
        'status' => 'pending',
    ]);

    $this->actingAs($staff)
        ->post(route('proposals.store'), validProposalPayload($deps, ['academic_year' => '2026', 'semester' => 'خريف']))
        ->assertRedirect();
});

test('updating a proposal excludes itself from its own supervisor capacity count', function () {
    SystemSetting::current()->update(['max_projects_per_supervisor_per_semester' => 1]);

    $deps    = makeProposalDeps();
    $manager = managerInDept($deps['dept']->id);
    $proposal = ProjectProposal::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
        'academic_year'     => '2026',
        'semester'          => 'خريف',
        'status'            => 'pending',
    ]);

    $this->actingAs($manager)
        ->put(route('proposals.update', $proposal->id), validProposalPayload($deps, ['title' => 'مقترح محدّث']))
        ->assertRedirect();

    expect($proposal->fresh()->title)->toBe('مقترح محدّث');
});

// ── Status changes ──────────────────────────────────────────────────────────

test('rejecting a proposal requires a reason', function () {
    $deps    = makeProposalDeps();
    $manager = managerInDept($deps['dept']->id);
    $proposal = ProjectProposal::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
        'status'            => 'pending',
    ]);

    $this->actingAs($manager)
        ->post(route('proposals.change-status', $proposal->id), ['action' => 'reject'])
        ->assertSessionHasErrors('rejection_reason');

    $this->actingAs($manager)
        ->post(route('proposals.change-status', $proposal->id), [
            'action'           => 'reject',
            'rejection_reason' => 'المقترح غير مكتمل',
        ])
        ->assertRedirect();

    expect($proposal->fresh()->status)->toBe('rejected');
    expect($proposal->fresh()->rejection_reason)->toBe('المقترح غير مكتمل');
});

test('requesting revision keeps the proposal active', function () {
    $deps    = makeProposalDeps();
    $manager = managerInDept($deps['dept']->id);
    $proposal = ProjectProposal::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
        'status'            => 'pending',
    ]);

    $this->actingAs($manager)
        ->post(route('proposals.change-status', $proposal->id), ['action' => 'request_revision'])
        ->assertRedirect();

    expect($proposal->fresh()->status)->toBe('needs_revision');

    $this->actingAs($manager)
        ->get(route('proposals.index'))
        ->assertInertia(fn ($page) => $page->where('proposals.total', 1));
});

test('dept_staff cannot change proposal status', function () {
    $deps  = makeProposalDeps();
    $staff = staffInDept($deps['dept']->id);
    $proposal = ProjectProposal::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
        'status'            => 'pending',
    ]);

    $this->actingAs($staff)
        ->post(route('proposals.change-status', $proposal->id), ['action' => 'approve'])
        ->assertForbidden();
});

// ── Approval → Project conversion ───────────────────────────────────────────

test('approving a proposal creates a linked project with copied students and file', function () {
    $deps    = makeProposalDeps();
    $manager = managerInDept($deps['dept']->id);
    $proposal = ProjectProposal::factory()->create([
        'title'              => 'مقترح جاهز للاعتماد',
        'department_id'      => $deps['dept']->id,
        'specialization_id'  => $deps['spec']->id,
        'supervisor_id'      => $deps['supervisor']->id,
        'status'             => 'pending',
        'proposal_file_path' => 'proposals/sample.pdf',
    ]);
    $proposal->students()->create(['full_name' => 'سارة أحمد', 'registration_number' => '2026099']);

    $this->actingAs($manager)
        ->post(route('proposals.change-status', $proposal->id), ['action' => 'approve'])
        ->assertRedirect();

    $proposal->refresh();
    expect($proposal->status)->toBe('approved');

    $project = Project::where('proposal_id', $proposal->id)->first();
    expect($project)->not->toBeNull();
    expect($project->project_title)->toBe('مقترح جاهز للاعتماد');
    expect($project->supervisor_id)->toBe($deps['supervisor']->id);
    expect($project->students)->toHaveCount(1);
    expect($project->students->first()->registration_number)->toBe('2026099');
    expect($project->draft_file_path)->toBe('proposals/sample.pdf');
});

// ── Replacement traceability ────────────────────────────────────────────────

test('submitting a replacement supersedes the old proposal and links both ways', function () {
    $deps  = makeProposalDeps();
    $staff = staffInDept($deps['dept']->id);
    $old = ProjectProposal::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
        'status'            => 'needs_revision',
    ]);

    $this->actingAs($staff)
        ->post(route('proposals.store'), array_merge(validProposalPayload($deps, ['title' => 'مقترح بديل محدث']), [
            'replaces_proposal_id' => $old->id,
        ]))
        ->assertRedirect();

    expect($old->fresh()->status)->toBe('superseded');

    $new = ProjectProposal::where('title', 'مقترح بديل محدث')->firstOrFail();
    expect($new->replaces_proposal_id)->toBe($old->id);
    expect($old->fresh()->replacedBy->id)->toBe($new->id);
});

// ── Active vs finished lists ─────────────────────────────────────────────────

test('active and finished proposal lists are separated', function () {
    $deps    = makeProposalDeps();
    $manager = managerInDept($deps['dept']->id);

    ProjectProposal::factory()->create([
        'department_id' => $deps['dept']->id, 'specialization_id' => $deps['spec']->id,
        'supervisor_id' => $deps['supervisor']->id, 'status' => 'pending',
    ]);
    ProjectProposal::factory()->create([
        'department_id' => $deps['dept']->id, 'specialization_id' => $deps['spec']->id,
        'supervisor_id' => $deps['supervisor']->id, 'status' => 'approved',
    ]);
    ProjectProposal::factory()->create([
        'department_id' => $deps['dept']->id, 'specialization_id' => $deps['spec']->id,
        'supervisor_id' => $deps['supervisor']->id, 'status' => 'rejected',
    ]);

    $this->actingAs($manager)
        ->get(route('proposals.index'))
        ->assertInertia(fn ($page) => $page->component('Proposals/Index')->where('proposals.total', 1));

    $this->actingAs($manager)
        ->get(route('proposals.archived'))
        ->assertInertia(fn ($page) => $page->component('Proposals/Archived/Index')->where('proposals.total', 2));
});
