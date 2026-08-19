<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AcademicDegreeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\FacultyMemberController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectFacultyMemberController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\ProjectFeedbackController;
use App\Http\Controllers\Api\ProjectFeedbackApiController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SpecializationController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentImportController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\ProposalController as StudentProposalController;
use App\Http\Controllers\Student\ProjectIdeaController as StudentProjectIdeaController;
use App\Http\Controllers\Student\ProjectIdeaRequestController as StudentProjectIdeaRequestController;
use App\Http\Controllers\Student\ProposalReservationController as StudentProposalReservationController;
use App\Http\Controllers\Supervisor\DashboardController as SupervisorDashboardController;
use App\Http\Controllers\Supervisor\ProposalController as SupervisorProposalController;
use App\Http\Controllers\Supervisor\ProjectIdeaController as SupervisorProjectIdeaController;
use App\Http\Controllers\Supervisor\ProjectIdeaRequestController as SupervisorProjectIdeaRequestController;
use App\Http\Controllers\Supervisor\ProposalReservationController as SupervisorProposalReservationController;
use App\Http\Controllers\ProjectProposalWebController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public — no auth required
Route::get('/', [PublicController::class, 'index'])->name('home');
Route::get('/browse', [PublicController::class, 'browse'])->name('public.browse');
Route::get('/browse/{id}', [PublicController::class, 'show'])->name('public.show');
Route::get('/student', [PublicController::class, 'studentPortal'])->name('student.portal');

// Temporary dev-only design reference — remove before production
Route::get('/design-system', function () {
    return Inertia::render('DesignSystem');
})->name('design-system');

// All authenticated users
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [ReportController::class, 'dashboard'])->name('dashboard');
});

// Personal notification center — available to every authenticated role
Route::middleware(['auth'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::patch('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
    Route::patch('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
});

// Student self-service portal — role:student only, scoped to the student's own data
Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');

    Route::get('/proposal/create', [StudentProposalController::class, 'create'])->name('proposal.create');
    Route::post('/proposal', [StudentProposalController::class, 'store'])->name('proposal.store');
    Route::get('/proposal/{proposal}', [StudentProposalController::class, 'show'])->name('proposal.show');
    Route::get('/proposal/{proposal}/edit', [StudentProposalController::class, 'edit'])->name('proposal.edit');
    Route::put('/proposal/{proposal}', [StudentProposalController::class, 'update'])->name('proposal.update');

    Route::get('/ideas', [StudentProjectIdeaController::class, 'index'])->name('ideas.index');
    Route::get('/ideas/{idea}', [StudentProjectIdeaController::class, 'show'])->name('ideas.show');
    Route::post('/ideas/{idea}/requests', [StudentProjectIdeaRequestController::class, 'store'])->name('ideas.requests.store');
    Route::get('/idea-requests', [StudentProjectIdeaRequestController::class, 'index'])->name('idea-requests.index');

    Route::get('/reservation', [StudentProposalReservationController::class, 'show'])->name('reservation.show');
    Route::patch('/reservations/{reservation}/under-review', [StudentProposalReservationController::class, 'markUnderReview'])->name('reservations.under-review');
    Route::patch('/reservations/{reservation}/abandon', [StudentProposalReservationController::class, 'abandon'])->name('reservations.abandon');
});

// Supervisor self-service portal — role:supervisor only, scoped to the supervisor's own assignments
Route::middleware(['auth', 'role:supervisor'])->prefix('supervisor')->name('supervisor.')->group(function () {
    Route::get('/dashboard', [SupervisorDashboardController::class, 'index'])->name('dashboard');

    Route::get('/proposals', [SupervisorProposalController::class, 'index'])->name('proposals.index');
    Route::get('/proposals/{proposal}', [SupervisorProposalController::class, 'show'])->name('proposals.show');
    Route::patch('/proposals/{proposal}/note', [SupervisorProposalController::class, 'updateNote'])->name('proposals.update-note');

    Route::get('/ideas', [SupervisorProjectIdeaController::class, 'index'])->name('ideas.index');
    Route::post('/ideas', [SupervisorProjectIdeaController::class, 'store'])->name('ideas.store');
    Route::put('/ideas/{idea}', [SupervisorProjectIdeaController::class, 'update'])->name('ideas.update');
    Route::delete('/ideas/{idea}', [SupervisorProjectIdeaController::class, 'destroy'])->name('ideas.destroy');

    Route::get('/ideas/{idea}/requests', [SupervisorProjectIdeaRequestController::class, 'index'])->name('ideas.requests.index');
    Route::patch('/idea-requests/{ideaRequest}/accept', [SupervisorProjectIdeaRequestController::class, 'accept'])->name('idea-requests.accept');
    Route::patch('/idea-requests/{ideaRequest}/reject', [SupervisorProjectIdeaRequestController::class, 'reject'])->name('idea-requests.reject');

    Route::patch('/reservations/{reservation}/approve', [SupervisorProposalReservationController::class, 'approve'])->name('reservations.approve');
    Route::patch('/reservations/{reservation}/release', [SupervisorProposalReservationController::class, 'release'])->name('reservations.release');
    Route::patch('/reservations/{reservation}/finish', [SupervisorProposalReservationController::class, 'finish'])->name('reservations.finish');
});

// Reports — dept_manager + super_admin
Route::middleware(['auth', 'role:dept_manager,super_admin'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/department',      [ReportController::class, 'departmentReport'])    ->name('department');
    Route::get('/specializations', [ReportController::class, 'specializationReport'])->name('specializations');
    Route::get('/supervisors',     [ReportController::class, 'supervisorReport'])    ->name('supervisors');
    Route::get('/yearly',          [ReportController::class, 'yearlyReport'])        ->name('yearly');
    Route::get('/export/pdf',      [ReportController::class, 'exportPdf'])           ->name('export.pdf');
    Route::get('/export/excel',    [ReportController::class, 'exportExcel'])         ->name('export.excel');
});

// Statistics page – accessible to dept_manager, dept_staff, super_admin



// super_admin + dept_manager: dept managers can manage users only in their own department
Route::middleware(['auth', 'role:super_admin,dept_manager'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => Inertia::render('Admin/Dashboard'))->name('dashboard');
    Route::resource('users', AdminUserController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::patch('users/{user}/toggle-active', [AdminUserController::class, 'toggleActive'])
        ->name('users.toggle-active');
});

// super_admin + dept_manager — department view/edit (ownership enforced in controller for update/edit)
Route::middleware(['auth', 'role:super_admin,dept_manager'])->group(function () {
    Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
    Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
    Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
    Route::patch('/departments/{department}', [DepartmentController::class, 'update']);

    Route::resource('specializations', SpecializationController::class)
        ->only(['store', 'update', 'destroy']);

    Route::resource('faculty-members', FacultyMemberController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::post('faculty-members/{facultyMember}/create-account', [FacultyMemberController::class, 'createAccount'])
        ->name('faculty-members.create-account');

    Route::get('feedback', [FeedbackController::class, 'index'])->name('feedback.index');
    Route::get('feedback/{feedback}', [FeedbackController::class, 'show'])->name('feedback.show');

    Route::post('projects/{id}/assign-faculty-member', [ProjectFacultyMemberController::class, 'assign'])
        ->name('projects.assign-faculty-member');

    Route::delete('projects/{id}/faculty-members/{facultyMemberId}', [ProjectFacultyMemberController::class, 'remove'])
        ->name('projects.remove-faculty-member');

    Route::post('projects/{id}/evaluation', [EvaluationController::class, 'store'])
        ->name('projects.evaluation');

    Route::patch('projects/{id}/score', [EvaluationController::class, 'updateScore'])
        ->name('projects.score');
});

Route::post('projects/{id}/feedback', [ProjectFeedbackController::class, 'store'])
    ->name('projects.feedback.store');

// Projects — all authenticated users can browse; role checks handled in controller/form requests
Route::middleware(['auth'])->group(function () {
    // Archived projects — must be registered before the resource route so
    // "archived" isn't captured by the projects/{project} wildcard.
    Route::get('projects/archived', [ProjectController::class, 'archivedIndex'])->name('projects.archived');
    Route::get('projects/archived/export/excel', [ProjectController::class, 'archivedExportExcel'])->name('projects.archived.export.excel');
    Route::get('projects/archived/export/pdf', [ProjectController::class, 'archivedExportPdf'])->name('projects.archived.export.pdf');
    Route::middleware('role:dept_manager,super_admin')->group(function () {
        Route::post('projects/archived', [ProjectController::class, 'archiveStore'])->name('projects.archived.store');
        Route::put('projects/{id}/archive', [ProjectController::class, 'archiveUpdate'])->name('projects.archived.update');
    });

    Route::resource('projects', ProjectController::class);

    Route::get('search', [SearchController::class, 'index'])->name('search.index');
    Route::get('search/suggestions', [SearchController::class, 'suggestions'])->name('search.suggestions');
});

Route::middleware(['auth', 'role:super_admin,dept_manager'])->prefix('api')->name('api.feedback.')->group(function () {
    Route::get('feedback', [ProjectFeedbackApiController::class, 'index'])->name('index');
    Route::get('feedback/{id}', [ProjectFeedbackApiController::class, 'show'])->name('show');
    Route::patch('feedback/{id}/read', [ProjectFeedbackApiController::class, 'markAsRead'])->name('read');
    Route::patch('feedback/{id}/status', [ProjectFeedbackApiController::class, 'updateStatus'])->name('status');
    Route::post('feedback/{id}/reply', [ProjectFeedbackApiController::class, 'reply'])->name('reply');
    Route::delete('feedback/{id}', [ProjectFeedbackApiController::class, 'destroy'])->name('destroy');
});

// Proposals Web Routes (Inertia)
Route::middleware(['auth', 'role:super_admin,dept_manager,dept_staff'])->prefix('proposals')->name('proposals.')->group(function () {
    // "archived" must be registered before the {proposal} wildcard.
    Route::get('/archived', [ProjectProposalWebController::class, 'archived'])->name('archived');

    Route::get('/', [ProjectProposalWebController::class, 'index'])->name('index');
    Route::post('/', [ProjectProposalWebController::class, 'store'])->name('store');
    Route::get('/{proposal}', [ProjectProposalWebController::class, 'show'])->name('show');
    Route::put('/{proposal}', [ProjectProposalWebController::class, 'update'])->name('update');
    Route::delete('/{proposal}', [ProjectProposalWebController::class, 'destroy'])->name('destroy');
    Route::post('/{proposal}/change-status', [ProjectProposalWebController::class, 'changeStatus'])->name('change-status');
});

// super_admin only — create and delete departments
Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::get('/departments/create', [DepartmentController::class, 'create'])->name('departments.create');
    Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
});

// super_admin only — academic degrees
Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::get('/academic-degrees', [AcademicDegreeController::class, 'index'])->name('academic-degrees.index');
    Route::post('/academic-degrees', [AcademicDegreeController::class, 'store'])->name('academic-degrees.store');
    Route::delete('/academic-degrees/{academicDegree}', [AcademicDegreeController::class, 'destroy'])->name('academic-degrees.destroy');
});

// super_admin only — system settings + semesters
Route::middleware(['auth', 'role:super_admin'])->prefix('settings')->name('settings.')->group(function () {
    Route::get('/system', [SystemSettingController::class, 'edit'])->name('system.edit');
    Route::put('/system', [SystemSettingController::class, 'update'])->name('system.update');
    Route::post('/semesters', [SemesterController::class, 'store'])->name('semesters.store');
    Route::delete('/semesters/{semester}', [SemesterController::class, 'destroy'])->name('semesters.destroy');
});

// Bulk import — super_admin only
Route::middleware(['auth', 'role:super_admin'])->prefix('import')->name('import.')->group(function () {
    Route::get('/', [ImportController::class, 'index'])->name('index');
    Route::get('/template', [ImportController::class, 'downloadTemplate'])->name('template');
    Route::post('/preview', [ImportController::class, 'preview'])->name('preview');
    Route::post('/run', [ImportController::class, 'import'])->name('run');
    Route::post('/pdfs', [ImportController::class, 'uploadPdfs'])->name('pdfs');
});

// Student roster import + management — dept_manager (own department) + super_admin
Route::middleware(['auth', 'role:dept_manager,super_admin'])->group(function () {
    Route::prefix('students/import')->name('students.import.')->group(function () {
        Route::get('/', [StudentImportController::class, 'index'])->name('index');
        Route::get('/template', [StudentImportController::class, 'downloadTemplate'])->name('template');
        Route::post('/preview', [StudentImportController::class, 'preview'])->name('preview');
        Route::post('/run', [StudentImportController::class, 'import'])->name('run');
    });

    Route::prefix('students')->name('students.')->group(function () {
        Route::get('/', [StudentController::class, 'index'])->name('index');
        Route::post('/', [StudentController::class, 'store'])->name('store');
        Route::get('/{student}', [StudentController::class, 'show'])->name('show');
        Route::patch('/{student}/toggle-active', [StudentController::class, 'toggleActive'])->name('toggle-active');
        Route::post('/{student}/reset-password', [StudentController::class, 'resetPassword'])->name('reset-password');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
