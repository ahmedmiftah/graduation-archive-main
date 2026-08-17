<?php

use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);
});

function makeLoggableStudent(string $registrationNumber = '202400123', string $dob = '2003-05-14'): Student
{
    $student = Student::factory()->create([
        'registration_number' => $registrationNumber,
        'date_of_birth'       => $dob,
    ]);

    $user = User::factory()->create([
        'name'                  => $student->full_name,
        'email'                 => $registrationNumber . '@students.local',
        'password'              => \Illuminate\Support\Facades\Hash::make(date('dmY', strtotime($dob))),
        'force_password_change' => true,
    ]);
    $user->assignRole('student');
    $student->update(['user_id' => $user->id]);

    return $student->fresh();
}

// ── Regression: staff/manager login by email is unaffected ─────────────────

test('staff can still authenticate with email as before', function () {
    $user = User::factory()->create();
    $user->assignRole('dept_staff');

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

// ── Student login by registration_number ────────────────────────────────────

test('student can authenticate using their registration_number as the login', function () {
    makeLoggableStudent('202400123', '2003-05-14');

    $this->post('/login', ['email' => '202400123', 'password' => '14052003'])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('a non-existent registration_number fails login safely', function () {
    $this->post('/login', ['email' => '999999999', 'password' => 'whatever'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('wrong password with a valid registration_number fails login', function () {
    makeLoggableStudent('202400123', '2003-05-14');

    $this->post('/login', ['email' => '202400123', 'password' => 'wrong'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

// ── Forced password change flow ─────────────────────────────────────────────

test('a student with a temporary password is redirected to change it before reaching the dashboard', function () {
    $student = makeLoggableStudent();

    $this->actingAs($student->user)
        ->get(route('dashboard'))
        ->assertRedirect(route('password.edit'));
});

test('the password-change page itself remains reachable while forced', function () {
    $student = makeLoggableStudent();

    $this->actingAs($student->user)
        ->get(route('password.edit'))
        ->assertOk();
});

test('changing the password clears the forced flag and unblocks normal navigation', function () {
    $student = makeLoggableStudent();

    $this->actingAs($student->user)
        ->put(route('password.update'), [
            'current_password'      => '14052003',
            'password'               => 'NewSecurePassword123',
            'password_confirmation'  => 'NewSecurePassword123',
        ])
        ->assertSessionDoesntHaveErrors();

    expect($student->user->fresh()->force_password_change)->toBeFalse();

    // Students are additionally redirected from the generic /dashboard to
    // their own /student/dashboard (Phase 2) — that redirect, not a direct
    // 200, is the expected "unblocked" behaviour here.
    $this->actingAs($student->user->fresh())
        ->get(route('dashboard'))
        ->assertRedirect(route('student.dashboard'));
});

test('a regular staff user without the forced flag is never redirected', function () {
    $user = User::factory()->create(); // force_password_change defaults to false
    $user->assignRole('dept_manager');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});
