<?php

use App\Models\Department;
use App\Models\Semester;
use App\Models\Specialization;
use App\Models\Student;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SystemSettingSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);
    $this->seed(SystemSettingSeeder::class); // registration_number_length = 9
});

function makeStudentAddDeps(): array
{
    $department     = Department::factory()->create(['code' => 'CS']);
    $specialization = Specialization::factory()->create(['department_id' => $department->id]);
    $semester       = Semester::create(['name' => 'الفصل الأول', 'is_active' => true, 'sort_order' => 1]);

    return [$department, $specialization, $semester];
}

function validStudentPayload(Department $department, Specialization $specialization, Semester $semester, array $overrides = []): array
{
    return array_merge([
        'full_name'           => 'أحمد علي',
        'national_id'         => '123456789012',
        'registration_number' => str_pad('1', (int) SystemSetting::current()->registration_number_length, '0', STR_PAD_LEFT),
        'department_id'       => $department->id,
        'specialization_id'   => $specialization->id,
        'semester'            => $semester->name,
        'academic_year'       => '2025',
        'date_of_birth'       => '2001-05-10',
    ], $overrides);
}

test('dept_manager can add a single student to their own department', function () {
    [$department, $specialization, $semester] = makeStudentAddDeps();
    $manager = User::factory()->create(['department_id' => $department->id]);
    $manager->assignRole('dept_manager');

    $this->actingAs($manager)
        ->post(route('students.store'), validStudentPayload($department, $specialization, $semester))
        ->assertRedirect()
        ->assertSessionHas('success');

    $student = Student::where('national_id', '123456789012')->first();
    expect($student)->not->toBeNull()
        ->and($student->department_id)->toBe($department->id)
        ->and($student->user)->not->toBeNull()
        ->and($student->user->hasRole('student'))->toBeTrue()
        ->and($student->user->email)->toBe(strtolower($student->registration_number) . '@students.local');
});

test('dept_manager cannot add a student to another department (field is forced server-side)', function () {
    [$deptA, $specA, $semester] = makeStudentAddDeps();
    $deptB = Department::factory()->create(['code' => 'EE']);
    $specB = Specialization::factory()->create(['department_id' => $deptB->id]);

    $manager = User::factory()->create(['department_id' => $deptA->id]);
    $manager->assignRole('dept_manager');

    // Attempts to submit deptB/specB — server must force department_id to the manager's own department,
    // which makes specB (belonging to deptB) mismatch and fail validation.
    $this->actingAs($manager)
        ->post(route('students.store'), validStudentPayload($deptB, $specB, $semester))
        ->assertSessionHasErrors('specialization_id');

    expect(Student::count())->toBe(0);
});

test('dept_staff cannot add a student', function () {
    [$department, $specialization, $semester] = makeStudentAddDeps();

    $this->actingAs(userWithRole('dept_staff'))
        ->post(route('students.store'), validStudentPayload($department, $specialization, $semester))
        ->assertForbidden();
});

test('national_id must be exactly 12 digits', function () {
    [$department, $specialization, $semester] = makeStudentAddDeps();

    $this->actingAs(userWithRole('super_admin'))
        ->post(route('students.store'), validStudentPayload($department, $specialization, $semester, ['national_id' => '123']))
        ->assertSessionHasErrors('national_id');
});

test('registration_number must match the configured length', function () {
    [$department, $specialization, $semester] = makeStudentAddDeps();

    $this->actingAs(userWithRole('super_admin'))
        ->post(route('students.store'), validStudentPayload($department, $specialization, $semester, ['registration_number' => '1']))
        ->assertSessionHasErrors('registration_number');
});

test('national_id must be unique', function () {
    [$department, $specialization, $semester] = makeStudentAddDeps();
    Student::factory()->create([
        'national_id'       => '123456789012',
        'department_id'     => $department->id,
        'specialization_id' => $specialization->id,
    ]);

    $this->actingAs(userWithRole('super_admin'))
        ->post(route('students.store'), validStudentPayload($department, $specialization, $semester))
        ->assertSessionHasErrors('national_id');
});

test('specialization must belong to the submitted department', function () {
    [$deptA, , $semester] = makeStudentAddDeps();
    $deptB = Department::factory()->create(['code' => 'EE']);
    $specB = Specialization::factory()->create(['department_id' => $deptB->id]);

    $this->actingAs(userWithRole('super_admin'))
        ->post(route('students.store'), validStudentPayload($deptA, $specB, $semester))
        ->assertSessionHasErrors('specialization_id');
});

test('semester must be a known semester name', function () {
    [$department, $specialization, $semester] = makeStudentAddDeps();

    $this->actingAs(userWithRole('super_admin'))
        ->post(route('students.store'), validStudentPayload($department, $specialization, $semester, ['semester' => 'غير موجود']))
        ->assertSessionHasErrors('semester');
});

test('students index passes departments for the add-student form', function () {
    [$department] = makeStudentAddDeps();

    $this->actingAs(userWithRole('super_admin'))
        ->get(route('students.index'))
        ->assertInertia(fn ($page) => $page
            ->component('Students/Index')
            ->has('departments')
            ->has('specializations.0.department_id')
        );
});
