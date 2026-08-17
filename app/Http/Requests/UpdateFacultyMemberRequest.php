<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFacultyMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name'          => ['required', 'string', 'max:150'],
            'phone_number'       => ['required', 'string', 'max:30'],
            'email'              => ['required', 'email', 'max:150'],
            'degree_id'          => ['required', 'integer', 'exists:academic_degrees,id'],
            'department_ids'     => ['required', 'array', 'min:1'],
            'department_ids.*'   => ['integer', 'exists:departments,id'],
        ];
    }
}
