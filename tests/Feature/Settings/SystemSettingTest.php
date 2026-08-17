<?php

use App\Models\Department;
use App\Models\FacultyMember;
use App\Models\Project;
use App\Models\Semester;
use App\Models\Specialization;
use App\Models\SystemSetting;
use Database\Seeders\ProjectStatusSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SemesterSeeder;
use Database\Seeders\SystemSettingSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);
    $this->seed(SystemSettingSeeder::class);
    $this->seed(SemesterSeeder::class);
});

test('super_admin can view system settings', function () {
    $this->actingAs(userWithRole('super_admin'))
        ->get(route('settings.system.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/SystemSettings'));
});

test('dept_manager cannot view system settings', function () {
    $this->actingAs(userWithRole('dept_manager'))
        ->get(route('settings.system.edit'))
        ->assertForbidden();
});

test('super_admin can update system settings', function () {
    $this->actingAs(userWithRole('super_admin'))
        ->put(route('settings.system.update'), [
            'max_students_per_project'                  => 3,
            'examiners_per_project'                      => 2,
            'max_projects_per_supervisor_per_semester'   => 4,
            'registration_number_length'                  => 8,
            'academic_year_format'                        => '2_digit',
            'archive_enabled'                              => true,
            'archivable_proposal_statuses'                 => ['approved'],
        ])
        ->assertRedirect();

    $settings = SystemSetting::current();
    expect($settings->max_students_per_project)->toBe(3);
    expect($settings->max_projects_per_supervisor_per_semester)->toBe(4);
    expect($settings->registration_number_length)->toBe(8);
    expect($settings->academic_year_format)->toBe('2_digit');
    expect($settings->archivable_proposal_statuses)->toBe(['approved']);
});

test('super_admin can add a semester', function () {
    $this->actingAs(userWithRole('super_admin'))
        ->post(route('settings.semesters.store'), ['name' => 'صيفي'])
        ->assertRedirect();

    $this->assertDatabaseHas('semesters', ['name' => 'صيفي']);
});

test('dept_manager cannot add a semester', function () {
    $this->actingAs(userWithRole('dept_manager'))
        ->post(route('settings.semesters.store'), ['name' => 'صيفي'])
        ->assertForbidden();
});

test('super_admin can delete an unused semester', function () {
    $semester = Semester::create(['name' => 'صيفي', 'sort_order' => 3]);

    $this->actingAs(userWithRole('super_admin'))
        ->delete(route('settings.semesters.destroy', $semester))
        ->assertRedirect();

    $this->assertDatabaseMissing('semesters', ['id' => $semester->id]);
});

test('cannot delete a semester linked to projects', function () {
    $this->seed(ProjectStatusSeeder::class);

    $dept       = Department::factory()->create();
    $spec       = Specialization::factory()->create(['department_id' => $dept->id]);
    $supervisor = FacultyMember::factory()->create();

    Project::factory()->create([
        'department_id'     => $dept->id,
        'specialization_id' => $spec->id,
        'supervisor_id'     => $supervisor->id,
        'semester'          => 'ربيع',
    ]);

    $semester = Semester::where('name', 'ربيع')->first();

    $this->actingAs(userWithRole('super_admin'))
        ->delete(route('settings.semesters.destroy', $semester))
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->assertDatabaseHas('semesters', ['id' => $semester->id]);
});
