<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectFeedbackStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'dept_manager']) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:new,in_review,replied,resolved,archived'],
        ];
    }
}
