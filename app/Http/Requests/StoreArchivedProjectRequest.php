<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreArchivedProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user->hasAnyRole(['dept_manager', 'super_admin'])) {
            return false;
        }

        if ($user->hasRole('dept_manager') && (int) $this->department_id !== $user->department_id) {
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
            'semester'                       => ['required', 'string', 'in:ربيع,خريف'],
            'department_id'                  => ['required', 'integer', 'exists:departments,id'],
            'specialization_id'              => ['required', 'integer', 'exists:specializations,id'],
            'supervisor_id'                  => ['required', 'integer', 'exists:users,id'],
            'pdf_file'                       => ['nullable', 'file', 'mimes:pdf', 'max:15360'],
            'final_score'                    => ['nullable', 'numeric', 'min:0', 'max:100'],
            'students'                       => ['required', 'array', 'min:1'],
            'students.*.full_name'           => ['required', 'string'],
            'students.*.registration_number' => ['required', 'string'],
            'examiners'                      => ['nullable', 'array', 'max:2'],
            'examiners.*.examiner_id'        => ['required', 'integer', 'exists:examiners,id', 'distinct'],
            'examiners.*.notes'              => ['nullable', 'string'],
        ];
    }
}
