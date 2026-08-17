<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeProposalStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled by the policy in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'action'           => ['required', Rule::in(['reject', 'request_revision', 'approve'])],
            'rejection_reason' => ['required_if:action,reject', 'nullable', 'string'],
            'supervisor_note'  => ['nullable', 'string'],
            'department_note'  => ['nullable', 'string'],
        ];
    }
}
