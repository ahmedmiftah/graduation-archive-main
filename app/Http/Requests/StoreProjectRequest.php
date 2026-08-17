<?php

namespace App\Http\Requests;

use App\Models\Semester;
use App\Models\SystemSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user->hasAnyRole(['dept_staff', 'dept_manager', 'super_admin'])) {
            return false;
        }

        // dept_staff may only submit projects for their own department
        if ($user->hasRole('dept_staff') && (int) $this->department_id !== $user->department_id) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'project_title'                  => ['required', 'string', 'max:255'],
            'description'                    => ['required', 'string'],
            'degree_level'                   => ['required', 'string', 'in:diploma,bachelor,master'],
            'academic_year'                  => ['required', 'string', 'max:20'],
            'semester'                       => ['required', 'string', Rule::in(Semester::pluck('name'))],
            'department_id'                  => ['required', 'integer', 'exists:departments,id'],
            'specialization_id'              => ['required', 'integer', 'exists:specializations,id'],
            'supervisor_id'                  => ['required', 'integer', 'exists:faculty_members,id'],
            'students'                       => ['required', 'array', 'min:1', 'max:' . SystemSetting::current()->max_students_per_project],
            'students.*.full_name'           => ['required', 'string'],
            'students.*.registration_number' => ['required', 'string'],
        ];
    }
}
