<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSemesterRequest;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;

class SemesterController extends Controller
{
    public function store(StoreSemesterRequest $request): RedirectResponse
    {
        Semester::create($request->validated());

        return redirect()->route('settings.system.edit')
            ->with('success', 'تم إضافة الفصل الدراسي بنجاح');
    }

    public function destroy(Semester $semester): RedirectResponse
    {
        if ($semester->projects()->exists() || $semester->proposals()->exists()) {
            return back()->with('error', 'لا يمكن حذف الفصل الدراسي لارتباطه بمشاريع أو مقترحات');
        }

        $semester->delete();

        return redirect()->route('settings.system.edit')
            ->with('success', 'تم حذف الفصل الدراسي بنجاح');
    }
}
