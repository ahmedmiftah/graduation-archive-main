<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visitor_name'  => ['nullable', 'string', 'max:255'],
            'visitor_email' => ['nullable', 'email', 'max:255'],
            'feedback_type' => ['required', 'string', 'in:suggestion,question,problem,like,general'],
            'title'         => ['required', 'string', 'max:255'],
            'message'       => ['required', 'string'],
            'rating'        => ['nullable', 'integer', 'between:1,5'],
        ];
    }
}
