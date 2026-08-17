<?php

use App\Models\Department;
use App\Models\ProjectIdea;
use App\Models\ProjectIdeaRequest;
use App\Models\ProjectProposal;
use App\Models\Specialization;
use App\Notifications\ProjectIdeaRequestDecided;
use App\Notifications\ProjectIdeaRequestSubmitted;
use Database\Seeders\ProjectStatusSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);
    $this->seed(ProjectStatusSeeder::class);
    $this->seed(SystemSettingSeeder::class);
});

// ── Helpers ───────────────────────────────────────────────────────────────────

function requestDeps(): array
{
    $dept = Department::factory()->create();
    $spec = Specialization::factory()->create(['department_id' => $dept->id]);

    return compact('dept', 'spec');
}

// ── Student submits a request ───────────────────────────────────────────────

test('student can submit a join request to an available idea', function () {
    Notification::fake();

    $deps       = requestDeps();
    $supervisor = makeLoggedInSupervisor();
    $student    = loggedInStudent($deps);
    $idea       = ProjectIdea::factory()->create([
        'faculty_member_id' => $supervisor->id,
        'specialization_id' => $deps['spec']->id,
    ]);

    $this->actingAs($student->user)
        ->post(route('student.ideas.requests.store', $idea->id), ['message' => 'أرغب بالانضمام'])
        ->assertRedirect();

    $this->assertDatabaseHas('project_idea_requests', [
        'project_idea_id' => $idea->id,
        'student_id'      => $student->id,
        'status'          => 'pending',
    ]);

    Notification::assertSentTo($supervisor->user, ProjectIdeaRequestSubmitted::class);
});

test('student cannot submit a duplicate request while one is already pending', function () {
    $deps       = requestDeps();
    $supervisor = makeLoggedInSupervisor();
    $student    = loggedInStudent($deps);
    $idea       = ProjectIdea::factory()->create([
        'faculty_member_id' => $supervisor->id,
        'specialization_id' => $deps['spec']->id,
    ]);

    ProjectIdeaRequest::factory()->create(['project_idea_id' => $idea->id, 'student_id' => $student->id]);

    $this->actingAs($student->user)
        ->post(route('student.ideas.requests.store', $idea->id))
        ->assertForbidden();
});

test('student with an active proposal cannot submit a join request', function () {
    $deps       = requestDeps();
    $supervisor = makeLoggedInSupervisor();
    $student    = loggedInStudent($deps);
    $idea       = ProjectIdea::factory()->create([
        'faculty_member_id' => $supervisor->id,
        'specialization_id' => $deps['spec']->id,
    ]);

    $proposal = ProjectProposal::factory()->create([
        'department_id'     => $deps['dept']->id,
        'specialization_id' => $deps['spec']->id,
        'supervisor_id'     => $supervisor->id,
        'status'            => 'pending',
    ]);
    $proposal->students()->create([
        'full_name'           => $student->full_name,
        'registration_number' => $student->registration_number,
        'student_id'          => $student->id,
    ]);

    $this->actingAs($student->user)
        ->post(route('student.ideas.requests.store', $idea->id))
        ->assertForbidden();
});

test('student with an active reservation cannot request another idea', function () {
    $deps       = requestDeps();
    $supervisor = makeLoggedInSupervisor();
    $student    = loggedInStudent($deps);

    $acceptedIdea = ProjectIdea::factory()->create([
        'faculty_member_id' => $supervisor->id,
        'specialization_id' => $deps['spec']->id,
        'status'            => ProjectIdea::STATUS_RESERVED,
    ]);
    $acceptedRequest = ProjectIdeaRequest::factory()->create([
        'project_idea_id' => $acceptedIdea->id,
        'student_id'      => $student->id,
        'status'          => 'accepted',
    ]);
    \App\Models\ProposalReservation::factory()->create([
        'project_idea_id'         => $acceptedIdea->id,
        'student_id'              => $student->id,
        'project_idea_request_id' => $acceptedRequest->id,
        'status'                  => \App\Models\ProposalReservation::STATUS_RESERVED,
    ]);

    $otherIdea = ProjectIdea::factory()->create([
        'faculty_member_id' => $supervisor->id,
        'specialization_id' => $deps['spec']->id,
    ]);

    $this->actingAs($student->user)
        ->post(route('student.ideas.requests.store', $otherIdea->id))
        ->assertForbidden();
});

test('student cannot request a non-available idea', function () {
    $deps       = requestDeps();
    $supervisor = makeLoggedInSupervisor();
    $student    = loggedInStudent($deps);
    $idea       = ProjectIdea::factory()->create([
        'faculty_member_id' => $supervisor->id,
        'specialization_id' => $deps['spec']->id,
        'status'            => ProjectIdea::STATUS_CLOSED,
    ]);

    $this->actingAs($student->user)
        ->post(route('student.ideas.requests.store', $idea->id))
        ->assertForbidden();
});

// ── Supervisor review ────────────────────────────────────────────────────────

test('supervisor can view requests for their own idea', function () {
    $deps       = requestDeps();
    $supervisor = makeLoggedInSupervisor();
    $student    = loggedInStudent($deps);
    $idea       = ProjectIdea::factory()->create([
        'faculty_member_id' => $supervisor->id,
        'specialization_id' => $deps['spec']->id,
    ]);
    ProjectIdeaRequest::factory()->create(['project_idea_id' => $idea->id, 'student_id' => $student->id]);

    $this->actingAs($supervisor->user)
        ->get(route('supervisor.ideas.requests.index', $idea->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Supervisor/Ideas/Requests')->has('requests', 1));
});

test('supervisor cannot view requests for another supervisor idea', function () {
    $deps      = requestDeps();
    $owner     = makeLoggedInSupervisor();
    $outsider  = makeLoggedInSupervisor();
    $idea      = ProjectIdea::factory()->create([
        'faculty_member_id' => $owner->id,
        'specialization_id' => $deps['spec']->id,
    ]);

    $this->actingAs($outsider->user)
        ->get(route('supervisor.ideas.requests.index', $idea->id))
        ->assertForbidden();
});

test('accepting the only required request closes the idea and notifies the student', function () {
    Notification::fake();

    $deps       = requestDeps();
    $supervisor = makeLoggedInSupervisor();
    $student    = loggedInStudent($deps);
    $idea       = ProjectIdea::factory()->create([
        'faculty_member_id'       => $supervisor->id,
        'specialization_id'       => $deps['spec']->id,
        'required_students_count' => 1,
    ]);
    $ideaRequest = ProjectIdeaRequest::factory()->create(['project_idea_id' => $idea->id, 'student_id' => $student->id]);

    $this->actingAs($supervisor->user)
        ->patch(route('supervisor.idea-requests.accept', $ideaRequest->id))
        ->assertRedirect();

    expect($ideaRequest->fresh()->status)->toBe('accepted');
    expect($idea->fresh()->status)->toBe(ProjectIdea::STATUS_CLOSED);
    Notification::assertSentTo($student->user, ProjectIdeaRequestDecided::class);
});

test('accepting one of several required requests only reserves the idea', function () {
    $deps       = requestDeps();
    $supervisor = makeLoggedInSupervisor();
    $student    = loggedInStudent($deps, ['registration_number' => '2026777']);
    $idea       = ProjectIdea::factory()->create([
        'faculty_member_id'       => $supervisor->id,
        'specialization_id'       => $deps['spec']->id,
        'required_students_count' => 2,
    ]);
    $ideaRequest = ProjectIdeaRequest::factory()->create(['project_idea_id' => $idea->id, 'student_id' => $student->id]);

    $this->actingAs($supervisor->user)
        ->patch(route('supervisor.idea-requests.accept', $ideaRequest->id))
        ->assertRedirect();

    expect($idea->fresh()->status)->toBe(ProjectIdea::STATUS_RESERVED);
});

test('supervisor can reject a pending request without affecting idea status', function () {
    Notification::fake();

    $deps       = requestDeps();
    $supervisor = makeLoggedInSupervisor();
    $student    = loggedInStudent($deps);
    $idea       = ProjectIdea::factory()->create([
        'faculty_member_id' => $supervisor->id,
        'specialization_id' => $deps['spec']->id,
    ]);
    $ideaRequest = ProjectIdeaRequest::factory()->create(['project_idea_id' => $idea->id, 'student_id' => $student->id]);

    $this->actingAs($supervisor->user)
        ->patch(route('supervisor.idea-requests.reject', $ideaRequest->id))
        ->assertRedirect();

    expect($ideaRequest->fresh()->status)->toBe('rejected');
    expect($idea->fresh()->status)->toBe(ProjectIdea::STATUS_AVAILABLE);
    Notification::assertSentTo($student->user, ProjectIdeaRequestDecided::class);
});

test('a decided request cannot be decided again', function () {
    $deps       = requestDeps();
    $supervisor = makeLoggedInSupervisor();
    $student    = loggedInStudent($deps);
    $idea       = ProjectIdea::factory()->create([
        'faculty_member_id' => $supervisor->id,
        'specialization_id' => $deps['spec']->id,
    ]);
    $ideaRequest = ProjectIdeaRequest::factory()->create([
        'project_idea_id' => $idea->id,
        'student_id'      => $student->id,
        'status'          => 'accepted',
        'decided_at'      => now(),
    ]);

    $this->actingAs($supervisor->user)
        ->patch(route('supervisor.idea-requests.reject', $ideaRequest->id))
        ->assertForbidden();
});

test('supervisor cannot decide a request for an idea they do not own', function () {
    $deps       = requestDeps();
    $owner      = makeLoggedInSupervisor();
    $outsider   = makeLoggedInSupervisor();
    $student    = loggedInStudent($deps);
    $idea       = ProjectIdea::factory()->create([
        'faculty_member_id' => $owner->id,
        'specialization_id' => $deps['spec']->id,
    ]);
    $ideaRequest = ProjectIdeaRequest::factory()->create(['project_idea_id' => $idea->id, 'student_id' => $student->id]);

    $this->actingAs($outsider->user)
        ->patch(route('supervisor.idea-requests.accept', $ideaRequest->id))
        ->assertForbidden();
});

// ── Student's own request list & idea visibility ────────────────────────────

test('student sees only their own submitted requests', function () {
    $deps       = requestDeps();
    $supervisor = makeLoggedInSupervisor();
    $student    = loggedInStudent($deps);
    $other      = loggedInStudent($deps, ['registration_number' => '2026555']);
    $idea       = ProjectIdea::factory()->create([
        'faculty_member_id' => $supervisor->id,
        'specialization_id' => $deps['spec']->id,
    ]);

    ProjectIdeaRequest::factory()->create(['project_idea_id' => $idea->id, 'student_id' => $student->id]);
    ProjectIdeaRequest::factory()->create(['project_idea_id' => $idea->id, 'student_id' => $other->id]);

    $this->actingAs($student->user)
        ->get(route('student.idea-requests.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Student/Ideas/MyRequests')->has('requests', 1));
});

test('a student who already applied can still view the idea after it stops being available', function () {
    $deps       = requestDeps();
    $supervisor = makeLoggedInSupervisor();
    $student    = loggedInStudent($deps);
    $idea       = ProjectIdea::factory()->create([
        'faculty_member_id' => $supervisor->id,
        'specialization_id' => $deps['spec']->id,
        'status'            => ProjectIdea::STATUS_RESERVED,
    ]);
    ProjectIdeaRequest::factory()->create(['project_idea_id' => $idea->id, 'student_id' => $student->id, 'status' => 'accepted']);

    $this->actingAs($student->user)
        ->get(route('student.ideas.show', $idea->id))
        ->assertOk();
});

test('unauthenticated visitor is blocked from idea-request routes', function () {
    $deps = requestDeps();
    $idea = ProjectIdea::factory()->create(['specialization_id' => $deps['spec']->id]);

    $this->post(route('student.ideas.requests.store', $idea->id))->assertRedirect(route('login'));
    $this->get(route('student.idea-requests.index'))->assertRedirect(route('login'));
});
