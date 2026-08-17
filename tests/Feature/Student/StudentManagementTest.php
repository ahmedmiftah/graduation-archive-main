<?php

use App\Models\Department;
use App\Models\Specialization;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);
});

function makeStudentWithAccount(array $overrides = []): Student
{
    $student = Student::factory()->create($overrides);
    $user    = User::factory()->create([
        'name'                  => $student->full_name,
        'email'                 => $student->registration_number . '@students.local',
        'force_password_change' => true,
    ]);
    $user->assignRole('student');
    $student->update(['user_id' => $user->id]);

    return $student->fresh();
}

// ── Access control ───────────────────────────────────────────────────────────

test('super_admin can view the students list', function () {
    makeStudentWithAccount();

    $this->actingAs(userWithRole('super_admin'))
        ->get(route('students.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Students/Index'));
});

test('dept_staff cannot view the students list', function () {
    $this->actingAs(userWithRole('dept_staff'))
        ->get(route('students.index'))
        ->assertForbidden();
});

test('dept_manager only sees students from their own department', function () {
    $deptA = Department::factory()->create();
    $deptB = Department::factory()->create();
    $specA = Specialization::factory()->create(['department_id' => $deptA->id]);
    $specB = Specialization::factory()->create(['department_id' => $deptB->id]);

    makeStudentWithAccount(['department_id' => $deptA->id, 'specialization_id' => $specA->id, 'registration_number' => '111111111']);
    makeStudentWithAccount(['department_id' => $deptB->id, 'specialization_id' => $specB->id, 'registration_number' => '222222222']);

    $manager = User::factory()->create(['department_id' => $deptA->id]);
    $manager->assignRole('dept_manager');

    $this->actingAs($manager)
        ->get(route('students.index'))
        ->assertInertia(fn ($page) => $page->where('students.meta.total', 1));
});

test('dept_manager cannot view a student from another department', function () {
    $deptA   = Department::factory()->create();
    $deptB   = Department::factory()->create();
    $specB   = Specialization::factory()->create(['department_id' => $deptB->id]);
    $student = makeStudentWithAccount(['department_id' => $deptB->id, 'specialization_id' => $specB->id]);

    $manager = User::factory()->create(['department_id' => $deptA->id]);
    $manager->assignRole('dept_manager');

    $this->actingAs($manager)
        ->get(route('students.show', $student->id))
        ->assertForbidden();
});

// ── Toggle active ─────────────────────────────────────────────────────────────

test('super_admin can toggle a student account active state', function () {
    $student = makeStudentWithAccount();
    expect($student->user->is_active)->toBeTrue();

    $this->actingAs(userWithRole('super_admin'))
        ->patch(route('students.toggle-active', $student->id))
        ->assertRedirect();

    expect($student->user->fresh()->is_active)->toBeFalse();
});

// ── Reset password ─────────────────────────────────────────────────────────────

test('super_admin can reset a student password and it is shown once', function () {
    $student    = makeStudentWithAccount();
    $oldHash    = $student->user->password;

    $this->actingAs(userWithRole('super_admin'))
        ->post(route('students.reset-password', $student->id))
        ->assertRedirect()
        ->assertSessionHas('reset_password_value');

    $fresh = $student->user->fresh();
    expect($fresh->password)->not->toBe($oldHash)
        ->and($fresh->force_password_change)->toBeTrue();
});

test('resetting password for a student without an account fails gracefully', function () {
    $student = Student::factory()->create();

    $this->actingAs(userWithRole('super_admin'))
        ->post(route('students.reset-password', $student->id))
        ->assertRedirect()
        ->assertSessionHas('error');
});
