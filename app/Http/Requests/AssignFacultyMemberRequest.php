<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignFacultyMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'faculty_member_id' => ['required', 'integer', 'exists:faculty_members,id'],
        ];
    }
}
