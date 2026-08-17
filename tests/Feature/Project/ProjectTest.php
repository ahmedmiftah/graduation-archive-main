<?php

use App\Models\Department;
use App\Models\FacultyMember;
use App\Models\Project;
use App\Models\Specialization;
use App\Models\User;
use Database\Seeders\ProjectStatusSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);
    $this->seed(ProjectStatusSeeder::class);
    $this->seed(\Database\Seeders\SemesterSeeder::class);
    Storage::fake('public');
});

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Creates the three foreign-key dependencies every project needs.
 * Pass an existing $dept to reuse a department across helpers.
 */
function makeProjectDeps(?Department $dept = null): array
{
    $dept       = $dept ?? Department::factory()->create();
    $spec       = Specialization::factory()->create(['department_id' => $dept->id]);
    $supervisor = FacultyMember::factory()->create();
    $supervisor->departments()->sync([$dept->id]);

    return compact('dept', 'spec', 'supervisor');
}

/**
 * Returns a valid project form payload, mergeable with overrides.
 */
function projectData(array $deps, array $overrides = []): array
{
    return array_merge([
        'project_title'     => 'Test Project Title',
        'description'       => 'Project description text',
        'academic_year'     => '2024/2025',
        'semester'          => 'خريف',
        'degree_level'      => 'bachelor',
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
        'students'          => [
            ['full_name' => 'Ahmed Ali', 'registration_number' => 'ST001'],
        ],
    ], $overrides);
}

// ── Visibility ────────────────────────────────────────────────────────────────

test('super_admin can view all projects', function () {
    $deps = makeProjectDeps();

    Project::factory()->count(3)->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
    ]);

    $this->actingAs(userWithRole('super_admin'))
        ->get(route('projects.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Projects/Index')
            ->has('projects.data', 3)
        );
});

test('archived projects do not appear on the general projects index', function () {
    $deps = makeProjectDeps();

    Project::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
        'current_status_id' => 1, // archived
        'project_title'     => 'Archived Project',
    ]);
    Project::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
        'current_status_id' => 5, // in_progress
        'project_title'     => 'In Progress Project',
    ]);

    $this->actingAs(userWithRole('super_admin'))
        ->get(route('projects.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Projects/Index')
            ->has('projects.data', 1)
            ->where('projects.data.0.project_title', 'In Progress Project')
        );
});

// ── Create / Store ────────────────────────────────────────────────────────────

test('dept_manager can create project', function () {
    $deps = makeProjectDeps();

    $this->actingAs(userWithRole('dept_manager'))
        ->post(route('projects.store'), projectData($deps))
        ->assertRedirect();

    $this->assertDatabaseHas('projects', [
        'project_title'     => 'Test Project Title',
        'current_status_id' => 5, // in_progress — every new project starts here
    ]);
});

test('dept_staff creates project with in-progress status', function () {
    $dept = Department::factory()->create();
    $deps = makeProjectDeps($dept);

    $staff = User::factory()->create(['department_id' => $dept->id]);
    $staff->assignRole('dept_staff');

    $this->actingAs($staff)
        ->post(route('projects.store'), projectData($deps))
        ->assertRedirect();

    $this->assertDatabaseHas('projects', [
        'project_title'     => 'Test Project Title',
        'current_status_id' => 5, // in_progress — every new project starts here
    ]);
});

test('dept_staff cannot create project in other department', function () {
    $ownDept   = Department::factory()->create();
    $otherDept = Department::factory()->create();
    $deps      = makeProjectDeps($otherDept);

    $staff = User::factory()->create(['department_id' => $ownDept->id]);
    $staff->assignRole('dept_staff');

    $this->actingAs($staff)
        ->post(route('projects.store'), projectData($deps))
        ->assertForbidden();
});

// ── PDF Validation (archived project creation) ─────────────────────────────────

test('archived project rejects non-PDF uploaded file', function () {
    $deps    = makeProjectDeps();
    $manager = User::factory()->create(['department_id' => $deps['dept']->id]);
    $manager->assignRole('dept_manager');

    $this->actingAs($manager)
        ->post(route('projects.archived.store'), projectData($deps, [
            'pdf_file' => UploadedFile::fake()->create('document.txt', 100, 'text/plain'),
        ]))
        ->assertSessionHasErrors('pdf_file');
});

test('archived project PDF cannot exceed 15MB', function () {
    $deps    = makeProjectDeps();
    $manager = User::factory()->create(['department_id' => $deps['dept']->id]);
    $manager->assignRole('dept_manager');

    $this->actingAs($manager)
        ->post(route('projects.archived.store'), projectData($deps, [
            'pdf_file' => UploadedFile::fake()->create('document.pdf', 16384, 'application/pdf'), // 16 MB
        ]))
        ->assertSessionHasErrors('pdf_file');
});

// ── Archive create/update ────────────────────────────────────────────────────

test('dept_manager can create archived project with examiners and score', function () {
    $deps     = makeProjectDeps();
    $manager  = User::factory()->create(['department_id' => $deps['dept']->id]);
    $manager->assignRole('dept_manager');
    $examiner = FacultyMember::factory()->create();
    $examiner->departments()->sync([$deps['dept']->id]);

    $this->actingAs($manager)
        ->post(route('projects.archived.store'), projectData($deps, [
            'final_score' => 88.5,
            'examiners'   => [
                ['faculty_member_id' => $examiner->id, 'notes' => 'ممتاز'],
            ],
        ]))
        ->assertRedirect();

    $this->assertDatabaseHas('projects', [
        'project_title'     => 'Test Project Title',
        'current_status_id' => 1,
        'final_score'       => 88.5,
    ]);

    $project = Project::where('project_title', 'Test Project Title')->firstOrFail();
    expect($project->facultyMembers)->toHaveCount(1);
    expect($project->evaluations)->toHaveCount(1);
});

test('dept_manager can update archived project examiners and score', function () {
    $deps      = makeProjectDeps();
    $manager   = User::factory()->create(['department_id' => $deps['dept']->id]);
    $manager->assignRole('dept_manager');
    $examiner1 = FacultyMember::factory()->create();
    $examiner1->departments()->sync([$deps['dept']->id]);
    $examiner2 = FacultyMember::factory()->create();
    $examiner2->departments()->sync([$deps['dept']->id]);

    $project = Project::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
        'current_status_id' => 1,
        'final_score'       => 70,
    ]);
    $project->facultyMembers()->attach($examiner1->id, ['assigned_by' => $manager->id]);

    $this->actingAs($manager)
        ->put(route('projects.archived.update', $project->id), projectData($deps, [
            'final_score' => 95,
            'examiners'   => [
                ['faculty_member_id' => $examiner2->id, 'notes' => 'جيد جداً'],
            ],
        ]))
        ->assertRedirect();

    $project->refresh();
    expect((float) $project->final_score)->toBe(95.0);
    expect($project->facultyMembers->pluck('id')->all())->toBe([$examiner2->id]);
});

// ── Delete / Soft Delete ──────────────────────────────────────────────────────

test('dept_manager can soft delete project', function () {
    $deps = makeProjectDeps();

    $project = Project::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
    ]);

    $this->actingAs(userWithRole('dept_manager'))
        ->delete(route('projects.destroy', $project->id))
        ->assertRedirect(route('projects.index'));

    $this->assertDatabaseHas('projects', [
        'id'         => $project->id,
        'is_deleted' => true,
    ]);
});

test('dept_staff cannot delete project', function () {
    $dept = Department::factory()->create();
    $deps = makeProjectDeps($dept);

    $project = Project::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
    ]);

    $staff = User::factory()->create(['department_id' => $dept->id]);
    $staff->assignRole('dept_staff');

    $this->actingAs($staff)
        ->delete(route('projects.destroy', $project->id))
        ->assertForbidden();
});

// ── Visit Count ───────────────────────────────────────────────────────────────

test('project visit count increments on each show', function () {
    $deps = makeProjectDeps();

    $project = Project::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
        'visit_count'       => 0,
    ]);

    $user = userWithRole('super_admin');
    $this->actingAs($user)->get(route('projects.show', $project->id));
    $this->actingAs($user)->get(route('projects.show', $project->id));

    $this->assertDatabaseHas('projects', [
        'id'          => $project->id,
        'visit_count' => 2,
    ]);
});

// ── Search & Filters ──────────────────────────────────────────────────────────

test('search returns only title-matching projects', function () {
    $deps = makeProjectDeps();

    Project::factory()->create([
        'project_title'     => 'Unique Alpha Title',
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
    ]);
    Project::factory()->create([
        'project_title'     => 'Something Completely Different',
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
    ]);

    $this->actingAs(userWithRole('super_admin'))
        ->get(route('projects.index', ['search' => 'Unique Alpha']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('projects.data', 1)
        );
});

test('filter by department returns only that department projects', function () {
    $depsA = makeProjectDeps();
    $depsB = makeProjectDeps(); // separate department

    Project::factory()->count(2)->create([
        'department_id'     => $depsA['dept']->id,
        'specialization_id' => $depsA['spec']->id,
        'supervisor_id'     => $depsA['supervisor']->id,
    ]);
    Project::factory()->create([
        'department_id'     => $depsB['dept']->id,
        'specialization_id' => $depsB['spec']->id,
        'supervisor_id'     => $depsB['supervisor']->id,
    ]);

    $this->actingAs(userWithRole('super_admin'))
        ->get(route('projects.index', ['department_id' => $depsA['dept']->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('projects.data', 2)
        );
});

test('filter by academic year returns correct projects', function () {
    $deps = makeProjectDeps();

    Project::factory()->count(2)->create([
        'academic_year'     => '2023/2024',
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
    ]);
    Project::factory()->create([
        'academic_year'     => '2022/2023',
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
    ]);

    $this->actingAs(userWithRole('super_admin'))
        ->get(route('projects.index', ['academic_year' => '2023/2024']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('projects.data', 2)
        );
});

// ── Similarity Warning ────────────────────────────────────────────────────────

test('duplicate title projects both appear in search results', function () {
    $deps  = makeProjectDeps();
    $title = 'Identical Project Title';

    Project::factory()->count(2)->create([
        'project_title'     => $title,
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
    ]);

    $this->actingAs(userWithRole('super_admin'))
        ->get(route('projects.index', ['search' => $title]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('projects.data', 2)
        );
});
