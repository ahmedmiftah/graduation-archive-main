<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSystemSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'max_students_per_project'                  => ['required', 'integer', 'min:1', 'max:20'],
            'examiners_per_project'                      => ['required', 'integer', 'min:1', 'max:10'],
            'max_projects_per_supervisor_per_semester'   => ['required', 'integer', 'min:1', 'max:50'],
            'registration_number_length'                 => ['required', 'integer', 'min:4', 'max:20'],
            'academic_year_format'         => ['required', Rule::in(['2_digit', '4_digit'])],
            'archive_enabled'              => ['required', 'boolean'],
            'archivable_proposal_statuses' => ['required', 'array', 'min:1'],
            'archivable_proposal_statuses.*' => [Rule::in(['approved', 'rejected'])],
        ];
    }
}
