<?php

use App\Models\FacultyMember;
use App\Models\User;
use App\Notifications\SupervisorAccountCreated;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);
});

test('super_admin can create a login account for a faculty member', function () {
    Notification::fake();

    $facultyMember = FacultyMember::factory()->create([
        'email'        => 'dr.ahmad@college.edu',
        'phone_number' => '0501234567',
    ]);

    $this->actingAs(userWithRole('super_admin'))
        ->post(route('faculty-members.create-account', $facultyMember->id))
        ->assertRedirect();

    $facultyMember->refresh();
    expect($facultyMember->user_id)->not->toBeNull();

    $user = $facultyMember->user;
    expect($user->email)->toBe('dr.ahmad@college.edu')
        ->and($user->hasRole('supervisor'))->toBeTrue()
        ->and($user->force_password_change)->toBeTrue()
        ->and($user->is_active)->toBeTrue()
        ->and(Hash::check('0501234567', $user->password))->toBeTrue();

    Notification::assertSentTo($user, SupervisorAccountCreated::class);
});

test('dept_manager can also create a faculty member login account', function () {
    $facultyMember = FacultyMember::factory()->create();

    $this->actingAs(userWithRole('dept_manager'))
        ->post(route('faculty-members.create-account', $facultyMember->id))
        ->assertRedirect();

    expect($facultyMember->fresh()->user_id)->not->toBeNull();
});

test('dept_staff cannot create a faculty member login account', function () {
    $facultyMember = FacultyMember::factory()->create();

    $this->actingAs(userWithRole('dept_staff'))
        ->post(route('faculty-members.create-account', $facultyMember->id))
        ->assertForbidden();

    expect($facultyMember->fresh()->user_id)->toBeNull();
});

test('cannot create a second account for the same faculty member', function () {
    $facultyMember = FacultyMember::factory()->create();
    $existingUser  = User::factory()->create();
    $facultyMember->update(['user_id' => $existingUser->id]);

    $this->actingAs(userWithRole('super_admin'))
        ->post(route('faculty-members.create-account', $facultyMember->id))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($facultyMember->fresh()->user_id)->toBe($existingUser->id);
});

test('creating an account fails gracefully when the email is already used by another user', function () {
    User::factory()->create(['email' => 'taken@college.edu']);
    $facultyMember = FacultyMember::factory()->create(['email' => 'taken@college.edu']);

    $this->actingAs(userWithRole('super_admin'))
        ->post(route('faculty-members.create-account', $facultyMember->id))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($facultyMember->fresh()->user_id)->toBeNull();
});
