<?php

use App\Models\AcademicDegree;
use App\Models\FacultyMember;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);
});

// ── Visibility ────────────────────────────────────────────────────────────────

test('super_admin can view academic degrees', function () {
    AcademicDegree::factory()->count(3)->create();

    $this->actingAs(userWithRole('super_admin'))
        ->get(route('academic-degrees.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('AcademicDegrees/Index')
            ->has('degrees', 3)
        );
});

test('dept_manager cannot access academic degrees', function () {
    $this->actingAs(userWithRole('dept_manager'))
        ->get(route('academic-degrees.index'))
        ->assertForbidden();
});

test('dept_staff cannot access academic degrees', function () {
    $this->actingAs(userWithRole('dept_staff'))
        ->get(route('academic-degrees.index'))
        ->assertForbidden();
});

// ── CRUD ──────────────────────────────────────────────────────────────────────

test('super_admin can create academic degree', function () {
    $this->actingAs(userWithRole('super_admin'))
        ->post(route('academic-degrees.store'), [
            'degree_name' => 'بكالوريوس',
            'degree_code' => 'B.Sc',
        ])
        ->assertRedirect(route('academic-degrees.index'));

    $this->assertDatabaseHas('academic_degrees', [
        'degree_name' => 'بكالوريوس',
        'degree_code' => 'B.Sc',
    ]);
});

test('dept_manager cannot create academic degree', function () {
    $this->actingAs(userWithRole('dept_manager'))
        ->post(route('academic-degrees.store'), [
            'degree_name' => 'بكالوريوس',
            'degree_code' => 'B.Sc',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('academic_degrees', ['degree_code' => 'B.Sc']);
});

test('super_admin can delete unused academic degree', function () {
    $degree = AcademicDegree::factory()->create();

    $this->actingAs(userWithRole('super_admin'))
        ->delete(route('academic-degrees.destroy', $degree))
        ->assertRedirect(route('academic-degrees.index'));

    $this->assertDatabaseMissing('academic_degrees', ['id' => $degree->id]);
});

test('cannot delete academic degree linked to faculty members', function () {
    $degree = AcademicDegree::factory()->create();
    FacultyMember::factory()->create(['degree_id' => $degree->id]);

    $this->actingAs(userWithRole('super_admin'))
        ->delete(route('academic-degrees.destroy', $degree))
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->assertDatabaseHas('academic_degrees', ['id' => $degree->id]);
});

// ── Validation ────────────────────────────────────────────────────────────────

test('academic degree name must be unique', function () {
    AcademicDegree::factory()->create(['degree_name' => 'ماجستير', 'degree_code' => 'M.Sc']);

    $this->actingAs(userWithRole('super_admin'))
        ->post(route('academic-degrees.store'), [
            'degree_name' => 'ماجستير',
            'degree_code' => 'M.A',
        ])
        ->assertSessionHasErrors('degree_name');
});

test('academic degree code must be unique', function () {
    AcademicDegree::factory()->create(['degree_name' => 'ماجستير', 'degree_code' => 'M.Sc']);

    $this->actingAs(userWithRole('super_admin'))
        ->post(route('academic-degrees.store'), [
            'degree_name' => 'ماجستير إدارة أعمال',
            'degree_code' => 'M.Sc',
        ])
        ->assertSessionHasErrors('degree_code');
});
