<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\ExaminerController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectExaminerController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\ProjectFeedbackController;
use App\Http\Controllers\Api\ProjectFeedbackApiController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SpecializationController;
use App\Http\Controllers\ProjectProposalWebController;
use App\Http\Controllers\Api\ProjectProposalController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public — no auth required
Route::get('/', [PublicController::class, 'index'])->name('home');
Route::get('/browse', [PublicController::class, 'browse'])->name('public.browse');
Route::get('/browse/{id}', [PublicController::class, 'show'])->name('public.show');

// Temporary dev-only design reference — remove before production
Route::get('/design-system', function () {
    return Inertia::render('DesignSystem');
})->name('design-system');

// All authenticated users
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [ReportController::class, 'dashboard'])->name('dashboard');
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

    Route::resource('examiners', ExaminerController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::get('feedback', [FeedbackController::class, 'index'])->name('feedback.index');
    Route::get('feedback/{feedback}', [FeedbackController::class, 'show'])->name('feedback.show');

    Route::post('projects/{id}/assign-examiner', [ProjectExaminerController::class, 'assign'])
        ->name('projects.assign-examiner');

    Route::delete('projects/{id}/examiners/{examinerId}', [ProjectExaminerController::class, 'remove'])
        ->name('projects.remove-examiner');

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

// Proposals API Routes
Route::middleware(['auth', 'role:super_admin,dept_manager,dept_staff,supervisor'])->prefix('api')->name('api.proposals.')->group(function () {
    Route::get('proposals', [ProjectProposalController::class, 'index'])->name('index');
    Route::post('proposals', [ProjectProposalController::class, 'store'])->name('store');
    Route::get('proposals/{proposal}', [ProjectProposalController::class, 'show'])->name('show');
    Route::put('proposals/{proposal}', [ProjectProposalController::class, 'update'])->name('update');
    Route::delete('proposals/{proposal}', [ProjectProposalController::class, 'destroy'])->name('destroy');
    Route::post('proposals/{proposal}/change-status', [ProjectProposalController::class, 'changeStatus'])->name('change-status');
});

// Proposals Web Routes (Inertia)
Route::middleware(['auth', 'role:super_admin,dept_manager,dept_staff,supervisor'])->prefix('proposals')->name('proposals.')->group(function () {
    Route::get('/', [ProjectProposalWebController::class, 'index'])->name('index');
    Route::get('/create', [ProjectProposalWebController::class, 'create'])->name('create');
    Route::get('/{proposal}', [ProjectProposalWebController::class, 'show'])->name('show');
    Route::get('/{proposal}/edit', [ProjectProposalWebController::class, 'edit'])->name('edit');
});

// super_admin only — create and delete departments
Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::get('/departments/create', [DepartmentController::class, 'create'])->name('departments.create');
    Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
});

// Bulk import — super_admin only
Route::middleware(['auth', 'role:super_admin'])->prefix('import')->name('import.')->group(function () {
    Route::get('/', [ImportController::class, 'index'])->name('index');
    Route::get('/template', [ImportController::class, 'downloadTemplate'])->name('template');
    Route::post('/preview', [ImportController::class, 'preview'])->name('preview');
    Route::post('/run', [ImportController::class, 'import'])->name('run');
    Route::post('/pdfs', [ImportController::class, 'uploadPdfs'])->name('pdfs');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
