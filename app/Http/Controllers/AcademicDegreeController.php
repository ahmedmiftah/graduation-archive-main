<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAcademicDegreeRequest;
use App\Models\AcademicDegree;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AcademicDegreeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('AcademicDegrees/Index', [
            'degrees' => AcademicDegree::orderBy('degree_name')->get(),
        ]);
    }

    public function store(StoreAcademicDegreeRequest $request): RedirectResponse
    {
        AcademicDegree::create($request->validated());

        return redirect()->route('academic-degrees.index')
            ->with('success', 'تم إضافة الدرجة العلمية بنجاح');
    }

    public function destroy(AcademicDegree $academicDegree): RedirectResponse
    {
        if ($academicDegree->facultyMembers()->exists()) {
            return back()->with('error', 'لا يمكن حذف الدرجة العلمية لارتباطها بأعضاء هيئة تدريس');
        }

        $academicDegree->delete();

        return redirect()->route('academic-degrees.index')
            ->with('success', 'تم حذف الدرجة العلمية بنجاح');
    }
}
