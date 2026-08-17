<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Http\Requests\StoreArchivedProjectRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateArchivedProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Department;
use App\Models\FacultyMember;
use App\Models\Project;
use App\Models\Specialization;
use App\Notifications\ProjectReadyForDefense;
use App\Services\SearchService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProjectController extends Controller
{
    private const STATUS_ARCHIVED           = 1; // archived — published in archive
    private const STATUS_PENDING            = 2; // proposal_submitted — awaiting dept_manager approval
    private const STATUS_IN_PROGRESS        = 5; // in_progress — default status for newly created projects
    private const STATUS_READY_FOR_DEFENSE  = 6; // ready_for_defense

    public function __construct(private readonly SearchService $search) {}

    public function index(Request $request): Response
    {
        $filters = $request->only([
            'search', 'department_id', 'specialization_id',
            'academic_year', 'semester', 'supervisor_id', 'degree_level', 'sort',
        ]);

        // Archived ("منجز") projects belong exclusively to the archive page —
        // always excluded here, regardless of client input.
        $filters['status'] = 'exclude_archived';

        return Inertia::render('Projects/Index', [
            'projects'      => $this->search->searchProjects($filters),
            'filterOptions' => $this->search->getFilterOptions(),
            'filters'       => $filters,
        ]);
    }

    public function archivedIndex(Request $request): Response
    {
        $filters           = $request->only([
            'search', 'department_id', 'specialization_id',
            'academic_year', 'semester', 'supervisor_id', 'degree_level', 'sort',
        ]);
        $filters['status'] = 'active'; // forces archived-only results

        return Inertia::render('Projects/Archived/Index', [
            'projects'      => $this->search->searchProjects($filters),
            'filterOptions' => $this->search->getFilterOptions(),
            'filters'       => $filters,
        ]);
    }

    public function archivedExportExcel(Request $request): BinaryFileResponse
    {
        [$title, $headers, $rows] = $this->buildArchivedExportData($request);

        return Excel::download(new ReportExport($title, $headers, $rows), 'archived-projects.xlsx');
    }

    public function archivedExportPdf(Request $request): \Illuminate\Http\Response
    {
        [$title, $headers, $rows] = $this->buildArchivedExportData($request);

        $pdf = Pdf::loadView('exports.report', compact('title', 'headers', 'rows'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('archived-projects.pdf');
    }

    private function buildArchivedExportData(Request $request): array
    {
        $filters = $request->only([
            'search', 'department_id', 'specialization_id',
            'academic_year', 'semester', 'supervisor_id', 'degree_level',
        ]);
        $filters['status'] = 'active'; // archived-only, same as archivedIndex()

        $headers = ['العنوان', 'التخصص', 'المشرف', 'الفصل الدراسي', 'الدرجة', 'التقدير'];

        $rows = $this->search->searchProjectsForExport($filters)->map(fn (Project $p) => [
            $p->project_title,
            $p->specialization?->name ?? '—',
            $p->supervisor?->full_name ?? '—',
            $p->semester ? "{$p->semester} {$p->academic_year}" : $p->academic_year,
            $p->final_score ?? '—',
            $this->gradeLabel($p->final_score),
        ])->all();

        return ['أرشيف المشاريع', $headers, $rows];
    }

    private function gradeLabel(?string $score): string
    {
        if ($score === null || $score === '') return '—';
        $value = (float) $score;

        return match (true) {
            $value >= 90 => 'ممتاز',
            $value >= 80 => 'جيد جداً',
            $value >= 70 => 'جيد',
            $value >= 60 => 'مقبول',
            default      => 'ضعيف',
        };
    }

    public function archiveStore(StoreArchivedProjectRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $pdfPath = null;
        if ($request->hasFile('pdf_file')) {
            $pdfPath = $request->file('pdf_file')->store('projects', 'public');
        }

        $project = DB::transaction(function () use ($data, $pdfPath) {
            $project = Project::create([
                'project_title'     => $data['project_title'],
                'description'       => $data['description'],
                'degree_level'      => $data['degree_level'],
                'academic_year'     => $data['academic_year'],
                'semester'          => $data['semester'],
                'department_id'     => $data['department_id'],
                'specialization_id' => $data['specialization_id'],
                'supervisor_id'     => $data['supervisor_id'],
                'current_status_id' => self::STATUS_ARCHIVED,
                'draft_file_path'   => $pdfPath,
                'final_score'       => $data['final_score'] ?? null,
                'is_deleted'        => false,
            ]);

            foreach ($data['students'] as $student) {
                $project->students()->create([
                    'full_name'           => $student['full_name'],
                    'registration_number' => $student['registration_number'],
                    'status'              => 'active',
                ]);
            }

            if ($pdfPath) {
                $project->documents()->create([
                    'document_type' => 'final_report',
                    'file_path'     => $pdfPath,
                    'is_final'      => true,
                ]);
            }

            foreach ($data['examiners'] ?? [] as $examiner) {
                $project->facultyMembers()->attach($examiner['faculty_member_id'], ['assigned_by' => Auth::id()]);

                if (! empty($examiner['notes'])) {
                    $project->evaluations()->create([
                        'faculty_member_id' => $examiner['faculty_member_id'],
                        'notes'             => $examiner['notes'],
                    ]);
                }
            }

            return $project;
        });

        return redirect()->route('projects.show', $project)->with('success', 'تم إضافة المشروع المؤرشف بنجاح');
    }

    public function archiveUpdate(UpdateArchivedProjectRequest $request, int $id): RedirectResponse
    {
        $project = Project::where('is_deleted', false)->findOrFail($id);

        $this->authorizeEdit($project);

        $data = $request->validated();

        $pdfPath = $project->draft_file_path;
        if ($request->hasFile('pdf_file')) {
            if ($pdfPath) {
                Storage::disk('public')->delete($pdfPath);
            }
            $pdfPath = $request->file('pdf_file')->store('projects', 'public');
        }

        DB::transaction(function () use ($data, $pdfPath, $project) {
            $project->update([
                'project_title'     => $data['project_title'],
                'description'       => $data['description'],
                'degree_level'      => $data['degree_level'],
                'academic_year'     => $data['academic_year'],
                'semester'          => $data['semester'],
                'department_id'     => $data['department_id'],
                'specialization_id' => $data['specialization_id'],
                'supervisor_id'     => $data['supervisor_id'],
                'current_status_id' => self::STATUS_ARCHIVED,
                'draft_file_path'   => $pdfPath,
                'final_score'       => $data['final_score'] ?? null,
            ]);

            $project->students()->delete();
            foreach ($data['students'] as $student) {
                $project->students()->create([
                    'full_name'           => $student['full_name'],
                    'registration_number' => $student['registration_number'],
                    'status'              => 'active',
                ]);
            }

            if ($pdfPath && $pdfPath !== $project->getOriginal('draft_file_path')) {
                $project->documents()->create([
                    'document_type' => 'final_report',
                    'file_path'     => $pdfPath,
                    'is_final'      => true,
                ]);
            }

            $project->evaluations()->delete();
            $project->facultyMembers()->detach();
            foreach ($data['examiners'] ?? [] as $examiner) {
                $project->facultyMembers()->attach($examiner['faculty_member_id'], ['assigned_by' => Auth::id()]);

                if (! empty($examiner['notes'])) {
                    $project->evaluations()->create([
                        'faculty_member_id' => $examiner['faculty_member_id'],
                        'notes'             => $examiner['notes'],
                    ]);
                }
            }
        });

        return redirect()->route('projects.show', $project)->with('success', 'تم تحديث المشروع المؤرشف بنجاح');
    }

    public function create(): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (! $user->hasAnyRole(['dept_staff', 'dept_manager', 'super_admin'])) {
            abort(403);
        }

        $departmentId = $user->hasRole('super_admin') ? null : $user->department_id;

        $departmentsQuery = Department::orderBy('name');
        if ($departmentId) {
            $departmentsQuery->where('id', $departmentId);
        }

        $specializationsQuery = Specialization::orderBy('name');
        if ($departmentId) {
            $specializationsQuery->where('department_id', $departmentId);
        }

        $supervisors = FacultyMember::orderBy('full_name')->get(['id', 'full_name']);

        return Inertia::render('Projects/Create', [
            'departments'     => $departmentsQuery->get(['id', 'name']),
            'specializations' => $specializationsQuery->get(['id', 'name', 'department_id']),
            'supervisors'     => $supervisors,
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $similar = $this->search->detectSimilarity($data['project_title'], $data['description'] ?? '');

        // New projects always start "in progress" — status can only move to
        // "archived" later via the dedicated archive flow (with examiners,
        // score and file), not at creation time.
        $statusId = self::STATUS_IN_PROGRESS;

        $project = Project::create([
            'project_title'     => $data['project_title'],
            'description'       => $data['description'],
            'degree_level'      => $data['degree_level'],
            'academic_year'     => $data['academic_year'],
            'semester'          => $data['semester'],
            'department_id'     => $data['department_id'],
            'specialization_id' => $data['specialization_id'],
            'supervisor_id'     => $data['supervisor_id'],
            'current_status_id' => $statusId,
            'is_deleted'        => false,
        ]);

        foreach ($data['students'] as $student) {
            $project->students()->create([
                'full_name'           => $student['full_name'],
                'registration_number' => $student['registration_number'],
                'status'              => 'active',
            ]);
        }

        $redirect = redirect()->route('projects.show', $project)->with('success', 'تم إنشاء المشروع بنجاح');

        if ($similar->isNotEmpty()) {
            $redirect->with('similarity_warning', $this->withUrls($similar));
        }

        return $redirect;
    }

    /**
     * Attach a viewer-safe 'url' to each similarity candidate. Both target
     * routes are reachable by every role that can create/edit a project
     * (dept_staff/dept_manager/super_admin), so linking is always safe here.
     */
    private function withUrls(\Illuminate\Support\Collection $similar): array
    {
        return $similar->map(fn (array $item) => [
            ...$item,
            'url' => route($item['type'] === 'project' ? 'projects.show' : 'proposals.show', $item['id']),
        ])->all();
    }

    public function show(int $id): Response
    {
        $project = Project::with([
            'department',
            'specialization',
            'supervisor',
            'students',
            'documents',
            'facultyMembers.departments:id,name',
            'evaluations',
            'currentStatus',
        ])->where('is_deleted', false)->findOrFail($id);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user && !$user->hasRole('super_admin') && $user->department_id) {
            if ($project->department_id !== $user->department_id) {
                abort(403, 'Unauthorized action.');
            }
        }

        $project->increment('visit_count');

        $assignedIds        = $project->facultyMembers->pluck('id');

        $availableExaminersQuery = FacultyMember::whereNotIn('id', $assignedIds)
            ->with('departments:id,name')
            ->orderBy('full_name');

        if ($user && !$user->hasRole('super_admin') && $user->department_id) {
            $deptId = $user->department_id;
            $availableExaminersQuery->whereHas('departments', fn ($q) => $q->where('departments.id', $deptId));
        }

        $availableExaminers = $availableExaminersQuery->get();

        return Inertia::render('Projects/Show', [
            'project'            => $project,
            'availableExaminers' => $availableExaminers,
        ]);
    }

    public function edit(int $id): Response
    {
        $project = Project::where('is_deleted', false)->findOrFail($id);

        $this->authorizeEdit($project);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $departmentId = $user->hasRole('super_admin') ? null : $user->department_id;

        $departmentsQuery = Department::orderBy('name');
        if ($departmentId) {
            $departmentsQuery->where('id', $departmentId);
        }

        $specializationsQuery = Specialization::orderBy('name');
        if ($departmentId) {
            $specializationsQuery->where('department_id', $departmentId);
        }

        $supervisors = FacultyMember::orderBy('full_name')->get(['id', 'full_name']);

        $examinersQuery = FacultyMember::with('departments:id,name')->orderBy('full_name');
        if ($departmentId) {
            $examinersQuery->whereHas('departments', fn ($q) => $q->where('departments.id', $departmentId));
        }

        return Inertia::render('Projects/Edit', [
            'project'         => $project->load(['students', 'documents', 'facultyMembers', 'evaluations']),
            'departments'     => $departmentsQuery->get(['id', 'name']),
            'specializations' => $specializationsQuery->get(['id', 'name', 'department_id']),
            'supervisors'     => $supervisors,
            'examiners'       => $examinersQuery->get(),
        ]);
    }

    public function update(UpdateProjectRequest $request, int $id): RedirectResponse
    {
        $project = Project::where('is_deleted', false)->findOrFail($id);

        $this->authorizeEdit($project);

        $data         = $request->validated();
        $similar      = $this->search->detectSimilarity($data['project_title'], $data['description'] ?? '', excludeProjectId: $id);
        $previousStatusId = $project->current_status_id;

        $project->update([
            'project_title'     => $data['project_title'],
            'description'       => $data['description'],
            'degree_level'      => $data['degree_level'],
            'academic_year'     => $data['academic_year'],
            'semester'          => $data['semester'],
            'department_id'     => $data['department_id'],
            'specialization_id' => $data['specialization_id'],
            'supervisor_id'     => $data['supervisor_id'],
            'current_status_id' => $data['current_status_id'] ?? $project->current_status_id,
        ]);

        $project->students()->delete();
        foreach ($data['students'] as $student) {
            $project->students()->create([
                'full_name'           => $student['full_name'],
                'registration_number' => $student['registration_number'],
                'status'              => 'active',
            ]);
        }

        if ($previousStatusId !== self::STATUS_READY_FOR_DEFENSE && $project->current_status_id === self::STATUS_READY_FOR_DEFENSE) {
            $supervisorUser = $project->supervisor?->user;
            if ($supervisorUser) {
                Notification::send($supervisorUser, new ProjectReadyForDefense($project));
            }
        }

        $redirect = redirect()->route('projects.show', $project)->with('success', 'تم تحديث المشروع بنجاح');

        if ($similar->isNotEmpty()) {
            $redirect->with('similarity_warning', $this->withUrls($similar));
        }

        return $redirect;
    }

    public function destroy(int $id): RedirectResponse
    {
        $project = Project::where('is_deleted', false)->findOrFail($id);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (! $user->hasAnyRole(['dept_manager', 'super_admin'])) {
            abort(403);
        }

        $project->update(['is_deleted' => true]);

        return redirect()->route('projects.index')
            ->with('success', 'تم حذف المشروع بنجاح');
    }

    private function authorizeEdit(Project $project): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasRole('super_admin')) {
            return;
        }

        if ($user->hasRole('dept_manager') && $project->department_id === $user->department_id) {
            return;
        }

        // dept_staff may edit only pending projects within their own department
        if ($user->hasRole('dept_staff')
            && $project->current_status_id === self::STATUS_PENDING
            && $project->department_id === $user->department_id
        ) {
            return;
        }

        abort(403);
    }
}
