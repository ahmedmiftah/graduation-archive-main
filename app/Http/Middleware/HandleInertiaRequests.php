<?php

namespace App\Http\Middleware;

use App\Models\Semester;
use App\Models\SystemSetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return array_merge(parent::share($request), [
            'name'  => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth'  => [
                'user' => $request->user() ? array_merge($request->user()->toArray(), [
                    'role' => $request->user()->getRoleNames()->first(),
                ]) : null,
            ],
            'notifications' => [
                'unreadCount' => $request->user() ? $request->user()->unreadNotifications()->count() : 0,
                'recent'      => $request->user()
                    ? $request->user()->notifications()->latest()->limit(6)->get()->map(fn ($n) => [
                        'id'         => $n->id,
                        'title'      => $n->data['title'] ?? null,
                        'message'    => $n->data['message'] ?? null,
                        'url'        => $n->data['url'] ?? null,
                        'read_at'    => $n->read_at,
                        'created_at' => $n->created_at,
                    ])
                    : [],
            ],
            'systemSettings' => $request->user() ? SystemSetting::current()->only([
                'max_students_per_project', 'examiners_per_project',
                'max_projects_per_supervisor_per_semester', 'academic_year_format',
            ]) : null,
            'semesters' => $request->user() ? Semester::where('is_active', true)->orderBy('sort_order')->orderBy('name')->pluck('name') : [],
            'flash' => [
                'success'            => $request->session()->get('success'),
                'error'              => $request->session()->get('error'),
                'similarity_warning' => $request->session()->get('similarity_warning'),
                'preview'                 => $request->session()->get('preview'),
                'import_summary'          => $request->session()->get('import_summary'),
                'pdf_summary'             => $request->session()->get('pdf_summary'),
                'student_import_preview'  => $request->session()->get('student_import_preview'),
                'student_import_summary'  => $request->session()->get('student_import_summary'),
                'reset_password_value'    => $request->session()->get('reset_password_value'),
            ],
        ]);
    }
}
