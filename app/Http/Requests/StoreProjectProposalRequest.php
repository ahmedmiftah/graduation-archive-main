<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled by policy, allow here
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'specialization_id' => ['required', 'exists:specializations,id'],
            'academic_year' => ['required', 'string', 'max:9'], // e.g., 2024
            'semester' => ['required', Rule::in(['ربيع', 'خريف'])],
            'submission_date' => ['nullable', 'date'],
            'students' => ['sometimes', 'array'],
            'students.*' => ['sometimes', 'string'],
            'supervisor_id' => ['nullable', 'exists:users,id'],
            'pdf_file' => ['nullable', 'file', 'mimes:pdf', 'max:5120'], // max 5MB
            'status' => ['required', Rule::in(['new','under_review','approved','rejected','archived'])],
            'committee_decision' => ['nullable', Rule::in(['accepted','accepted_with_modifications','rejected'])],
            'committee_notes' => ['nullable', 'string'],
        ];
    }
}
?>
