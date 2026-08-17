<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'faculty_member_id' => ['required', 'integer', 'exists:faculty_members,id'],
            'notes'             => ['required', 'string'],
        ];
    }
}
