<?php

use App\Models\Department;
use App\Models\ProjectIdea;
use App\Models\ProjectIdeaRequest;
use App\Models\ProposalReservation;
use App\Models\Specialization;
use Database\Seeders\ProjectStatusSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SystemSettingSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);
    $this->seed(ProjectStatusSeeder::class);
    $this->seed(SystemSettingSeeder::class);
});

// ── Helpers ───────────────────────────────────────────────────────────────────

function reservationDeps(): array
{
    $dept = Department::factory()->create();
    $spec = Specialization::factory()->create(['department_id' => $dept->id]);

    return compact('dept', 'spec');
}

/**
 * Full acceptance flow via the real controller action, so the reservation
 * is created exactly the way production code creates it.
 */
function acceptedReservation(array $deps, $supervisor, $student, array $ideaOverrides = []): ProposalReservation
{
    $idea = ProjectIdea::factory()->create(array_merge([
        'faculty_member_id' => $supervisor->id,
        'specialization_id' => $deps['spec']->id,
    ], $ideaOverrides));

    $ideaRequest = ProjectIdeaRequest::factory()->create([
        'project_idea_id' => $idea->id,
        'student_id'      => $student->id,
    ]);

    test()->actingAs($supervisor->user)
        ->patch(route('supervisor.idea-requests.accept', $ideaRequest->id));

    return $ideaRequest->reservation()->firstOrFail();
}

// ── Creation on acceptance ──────────────────────────────────────────────────

test('accepting an idea request creates a reservation', function () {
    $deps       = reservationDeps();
    $supervisor = makeLoggedInSupervisor();
    $student    = loggedInStudent($deps);

    $reservation = acceptedReservation($deps, $supervisor, $student);

    expect($reservation->status)->toBe(ProposalReservation::STATUS_RESERVED);
    expect($reservation->student_id)->toBe($student->id);
});

// ── Student actions ──────────────────────────────────────────────────────────

test('student can mark their reservation as under review', function () {
    $deps        = reservationDeps();
    $supervisor  = makeLoggedInSupervisor();
    $student     = loggedInStudent($deps);
    $reservation = acceptedReservation($deps, $supervisor, $student);

    $this->actingAs($student->user)
        ->patch(route('student.reservations.under-review', $reservation->id))
        ->assertRedirect();

    expect($reservation->fresh()->status)->toBe(ProposalReservation::STATUS_UNDER_REVIEW);
});

test('a student cannot mark another student reservation as under review', function () {
    $deps        = reservationDeps();
    $supervisor  = makeLoggedInSupervisor();
    $owner       = loggedInStudent($deps);
    $outsider    = loggedInStudent($deps, ['registration_number' => '2026321']);
    $reservation = acceptedReservation($deps, $supervisor, $owner);

    $this->actingAs($outsider->user)
        ->patch(route('student.reservations.under-review', $reservation->id))
        ->assertForbidden();
});

test('student can abandon a reserved or under-review reservation', function () {
    $deps        = reservationDeps();
    $supervisor  = makeLoggedInSupervisor();
    $student     = loggedInStudent($deps);
    $reservation = acceptedReservation($deps, $supervisor, $student);

    $this->actingAs($student->user)
        ->patch(route('student.reservations.abandon', $reservation->id))
        ->assertRedirect();

    expect($reservation->fresh()->status)->toBe(ProposalReservation::STATUS_ABANDONED);
});

test('student cannot abandon an approved reservation', function () {
    $deps        = reservationDeps();
    $supervisor  = makeLoggedInSupervisor();
    $student     = loggedInStudent($deps);
    $reservation = acceptedReservation($deps, $supervisor, $student);
    $reservation->update(['status' => ProposalReservation::STATUS_APPROVED]);

    $this->actingAs($student->user)
        ->patch(route('student.reservations.abandon', $reservation->id))
        ->assertForbidden();
});

test('abandoning a reservation immediately frees the student to request a different idea', function () {
    $deps        = reservationDeps();
    $supervisor  = makeLoggedInSupervisor();
    $student     = loggedInStudent($deps);
    $reservation = acceptedReservation($deps, $supervisor, $student);

    $this->actingAs($student->user)
        ->patch(route('student.reservations.abandon', $reservation->id));

    $otherIdea = ProjectIdea::factory()->create([
        'faculty_member_id' => $supervisor->id,
        'specialization_id' => $deps['spec']->id,
    ]);

    $this->actingAs($student->user)
        ->post(route('student.ideas.requests.store', $otherIdea->id))
        ->assertRedirect();

    $this->assertDatabaseHas('project_idea_requests', [
        'project_idea_id' => $otherIdea->id,
        'student_id'      => $student->id,
    ]);
});

// ── Supervisor actions ───────────────────────────────────────────────────────

test('supervisor can approve a reservation that is under review', function () {
    $deps        = reservationDeps();
    $supervisor  = makeLoggedInSupervisor();
    $student     = loggedInStudent($deps);
    $reservation = acceptedReservation($deps, $supervisor, $student);
    $reservation->update(['status' => ProposalReservation::STATUS_UNDER_REVIEW]);

    $this->actingAs($supervisor->user)
        ->patch(route('supervisor.reservations.approve', $reservation->id))
        ->assertRedirect();

    expect($reservation->fresh()->status)->toBe(ProposalReservation::STATUS_APPROVED);
});

test('supervisor cannot approve a reservation that is still just reserved', function () {
    $deps        = reservationDeps();
    $supervisor  = makeLoggedInSupervisor();
    $student     = loggedInStudent($deps);
    $reservation = acceptedReservation($deps, $supervisor, $student);

    $this->actingAs($supervisor->user)
        ->patch(route('supervisor.reservations.approve', $reservation->id))
        ->assertForbidden();
});

test('releasing an abandoned reservation reopens the idea', function () {
    $deps        = reservationDeps();
    $supervisor  = makeLoggedInSupervisor();
    $student     = loggedInStudent($deps);
    $reservation = acceptedReservation($deps, $supervisor, $student, ['required_students_count' => 1]);

    expect($reservation->idea->fresh()->status)->toBe(ProjectIdea::STATUS_CLOSED);

    $reservation->update(['status' => ProposalReservation::STATUS_ABANDONED]);

    $this->actingAs($supervisor->user)
        ->patch(route('supervisor.reservations.release', $reservation->id))
        ->assertRedirect();

    expect($reservation->fresh()->status)->toBe(ProposalReservation::STATUS_RELEASED);
    expect($reservation->idea->fresh()->status)->toBe(ProjectIdea::STATUS_AVAILABLE);
});

test('supervisor cannot release a reservation that is not abandoned', function () {
    $deps        = reservationDeps();
    $supervisor  = makeLoggedInSupervisor();
    $student     = loggedInStudent($deps);
    $reservation = acceptedReservation($deps, $supervisor, $student);

    $this->actingAs($supervisor->user)
        ->patch(route('supervisor.reservations.release', $reservation->id))
        ->assertForbidden();
});

test('supervisor can finish an approved reservation', function () {
    $deps        = reservationDeps();
    $supervisor  = makeLoggedInSupervisor();
    $student     = loggedInStudent($deps);
    $reservation = acceptedReservation($deps, $supervisor, $student);
    $reservation->update(['status' => ProposalReservation::STATUS_APPROVED]);

    $this->actingAs($supervisor->user)
        ->patch(route('supervisor.reservations.finish', $reservation->id))
        ->assertRedirect();

    expect($reservation->fresh()->status)->toBe(ProposalReservation::STATUS_FINISHED);
});

test('supervisor cannot finish a reservation that is not approved', function () {
    $deps        = reservationDeps();
    $supervisor  = makeLoggedInSupervisor();
    $student     = loggedInStudent($deps);
    $reservation = acceptedReservation($deps, $supervisor, $student);

    $this->actingAs($supervisor->user)
        ->patch(route('supervisor.reservations.finish', $reservation->id))
        ->assertForbidden();
});

test('a supervisor cannot act on a reservation for an idea they do not own', function () {
    $deps        = reservationDeps();
    $owner       = makeLoggedInSupervisor();
    $outsider    = makeLoggedInSupervisor();
    $student     = loggedInStudent($deps);
    $reservation = acceptedReservation($deps, $owner, $student);
    $reservation->update(['status' => ProposalReservation::STATUS_UNDER_REVIEW]);

    $this->actingAs($outsider->user)
        ->patch(route('supervisor.reservations.approve', $reservation->id))
        ->assertForbidden();
});

// ── Student reservation page ─────────────────────────────────────────────────

test('student sees their current reservation on the reservation page', function () {
    $deps        = reservationDeps();
    $supervisor  = makeLoggedInSupervisor();
    $student     = loggedInStudent($deps);
    $reservation = acceptedReservation($deps, $supervisor, $student);

    $this->actingAs($student->user)
        ->get(route('student.reservation.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Student/Ideas/Reservation')
            ->where('reservation.id', $reservation->id)
        );
});

test('student with no reservation sees the empty state', function () {
    $student = loggedInStudent(reservationDeps());

    $this->actingAs($student->user)
        ->get(route('student.reservation.show'))
        ->assertInertia(fn ($page) => $page->component('Student/Ideas/Reservation')->where('reservation', null));
});

test('unauthenticated visitor is blocked from reservation routes', function () {
    $this->get(route('student.reservation.show'))->assertRedirect(route('login'));
});
