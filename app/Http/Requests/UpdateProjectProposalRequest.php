<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Policy will handle authorization
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'specialization_id' => ['sometimes', 'required', 'exists:specializations,id'],
            'academic_year' => ['sometimes', 'required', 'string', 'max:9'],
            'semester' => ['sometimes', 'required', Rule::in(['ربيع', 'خريف'])],
            'submission_date' => ['nullable', 'date'],
            'students' => ['sometimes', 'array'],
            'students.*' => ['sometimes', 'string'],
            'supervisor_id' => ['nullable', 'exists:users,id'],
            'pdf_file' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'status' => ['sometimes', 'required', Rule::in(['new','under_review','approved','rejected','archived'])],
            'committee_decision' => ['nullable', Rule::in(['accepted','accepted_with_modifications','rejected'])],
            'committee_notes' => ['nullable', 'string'],
        ];
    }
}
?>
