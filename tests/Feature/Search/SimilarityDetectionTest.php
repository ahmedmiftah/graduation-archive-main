<?php

use App\Models\Department;
use App\Models\FacultyMember;
use App\Models\Project;
use App\Models\ProjectProposal;
use App\Models\Specialization;
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
});

function similarityDeps(): array
{
    $dept       = Department::factory()->create();
    $spec       = Specialization::factory()->create(['department_id' => $dept->id]);
    $supervisor = FacultyMember::factory()->create();
    $supervisor->departments()->sync([$dept->id]);

    return compact('dept', 'spec', 'supervisor');
}

test('creating a project similar to an existing proposal flashes a linked warning', function () {
    $deps = similarityDeps();

    ProjectProposal::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'title'             => 'Smart Home Automation System',
        'status'            => 'pending',
    ]);

    $this->actingAs(userWithRole('dept_manager'))
        ->post(route('projects.store'), [
            'project_title'     => 'Smart Home Automation System',
            'description'       => 'Project description text',
            'academic_year'     => '2024/2025',
            'semester'          => 'خريف',
            'degree_level'      => 'bachelor',
            'department_id'     => $deps['dept']->id,
            'specialization_id' => $deps['spec']->id,
            'supervisor_id'     => $deps['supervisor']->id,
            'students'          => [['full_name' => 'Ahmed Ali', 'registration_number' => 'ST001']],
        ])
        ->assertSessionHas('similarity_warning', function ($warning) {
            return count($warning) === 1
                && $warning[0]['type'] === 'proposal'
                && $warning[0]['url'] !== null;
        });
});

test('staff creating a proposal similar to an existing project flashes a linked warning', function () {
    $deps = similarityDeps();

    Project::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
        'project_title'     => 'Smart Home Automation System',
    ]);

    $this->actingAs(staffInDept($deps['dept']->id))
        ->post(route('proposals.store'), [
            'title'              => 'Smart Home Automation System',
            'description'        => 'وصف تفصيلي لمقترح المشروع التجريبي',
            'specialization_id'  => $deps['spec']->id,
            'academic_year'      => '2026',
            'semester'           => 'خريف',
            'supervisor_id'      => $deps['supervisor']->id,
            'students'           => [['full_name' => 'أحمد محمد', 'registration_number' => '2026001']],
        ])
        ->assertSessionHas('similarity_warning', function ($warning) {
            return count($warning) === 1 && $warning[0]['type'] === 'project' && $warning[0]['url'] !== null;
        });
});

test('a student submitting a proposal similar to a project gets a clickable warning', function () {
    $deps    = similarityDeps();
    $student = loggedInStudent($deps);

    Project::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
        'project_title'     => 'Smart Home Automation System',
    ]);

    $this->actingAs($student->user)
        ->post(route('student.proposal.store'), [
            'title'             => 'Smart Home Automation System',
            'description'       => 'وصف تفصيلي للمشروع',
            'specialization_id' => $deps['spec']->id,
            'academic_year'     => '2026',
            'semester'          => 'خريف',
            'students'          => [],
        ])
        ->assertSessionHas('similarity_warning', function ($warning) {
            return count($warning) === 1 && $warning[0]['type'] === 'project' && $warning[0]['url'] !== null;
        });
});

test('a student submitting a proposal similar to another team proposal gets an unlinked warning', function () {
    $deps    = similarityDeps();
    $student = loggedInStudent($deps);

    ProjectProposal::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'title'             => 'Smart Home Automation System',
        'status'            => 'pending',
    ]);

    $this->actingAs($student->user)
        ->post(route('student.proposal.store'), [
            'title'             => 'Smart Home Automation System',
            'description'       => 'وصف تفصيلي للمشروع',
            'specialization_id' => $deps['spec']->id,
            'academic_year'     => '2026',
            'semester'          => 'خريف',
            'students'          => [],
        ])
        ->assertSessionHas('similarity_warning', function ($warning) {
            return count($warning) === 1 && $warning[0]['type'] === 'proposal' && $warning[0]['url'] === null;
        });
});

test('editing a project excludes itself from its own similarity candidates', function () {
    $deps    = similarityDeps();
    $manager = userWithRole('dept_manager');

    $project = Project::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $deps['supervisor']->id,
        'project_title'     => 'Smart Home Automation System',
        'current_status_id' => 5,
        'semester'          => 'خريف',
    ]);

    $this->actingAs($manager)
        ->put(route('projects.update', $project->id), [
            'project_title'     => 'Smart Home Automation System',
            'description'       => $project->description,
            'degree_level'      => 'bachelor',
            'academic_year'     => $project->academic_year,
            'semester'          => 'خريف',
            'department_id'     => $deps['dept']->id,
            'specialization_id' => $deps['spec']->id,
            'supervisor_id'     => $deps['supervisor']->id,
            'current_status_id' => 5,
            'students'          => [['full_name' => 'Ahmed Ali', 'registration_number' => 'ST001']],
        ])
        ->assertSessionMissing('similarity_warning');
});

test('no warning is flashed when nothing is similar', function () {
    $deps = similarityDeps();

    $this->actingAs(userWithRole('dept_manager'))
        ->post(route('projects.store'), [
            'project_title'     => 'Completely Unrelated Topic Title',
            'description'       => 'Nothing like anything else',
            'academic_year'     => '2024/2025',
            'semester'          => 'خريف',
            'degree_level'      => 'bachelor',
            'department_id'     => $deps['dept']->id,
            'specialization_id' => $deps['spec']->id,
            'supervisor_id'     => $deps['supervisor']->id,
            'students'          => [['full_name' => 'Ahmed Ali', 'registration_number' => 'ST001']],
        ])
        ->assertSessionMissing('similarity_warning');
});
