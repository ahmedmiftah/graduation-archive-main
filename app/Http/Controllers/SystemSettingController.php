<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSystemSettingRequest;
use App\Models\Semester;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Admin/SystemSettings', [
            'settings'  => SystemSetting::current(),
            'semesters' => Semester::orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateSystemSettingRequest $request): RedirectResponse
    {
        SystemSetting::current()->update($request->validated());

        return back()->with('success', 'تم تحديث إعدادات النظام بنجاح');
    }
}
