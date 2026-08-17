<?php

namespace App\Http\Controllers;

use App\Exports\StudentImportTemplate;
use App\Imports\StudentsImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentImportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Students/Import');
    }

    public function downloadTemplate(): BinaryFileResponse
    {
        return Excel::download(new StudentImportTemplate, 'students_import_template.xlsx');
    }

    public function preview(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        $import = new StudentsImport(dryRun: true);
        Excel::import($import, $request->file('file'));

        return back()->with('student_import_preview', $import->getSummary());
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        $import = new StudentsImport(dryRun: false, importedBy: $request->user()->id);
        Excel::import($import, $request->file('file'));

        $summary = $import->getSummary();

        $message = "تم إنشاء {$summary['success_count']} حساب طالب بنجاح";
        if ($summary['failed_count'] > 0) {
            $message .= "، {$summary['failed_count']} صف فشل";
        }

        return back()
            ->with('success', $message)
            ->with('student_import_summary', $summary);
    }
}
