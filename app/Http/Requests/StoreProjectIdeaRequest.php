<?php

namespace App\Http\Requests;

use App\Models\SystemSetting;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectIdeaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\ProjectIdea::class);
    }

    public function rules(): array
    {
        return [
            'title'                    => ['required', 'string', 'max:255'],
            'description'              => ['required', 'string'],
            'specialization_id'        => ['required', 'exists:specializations,id'],
            'required_students_count'  => ['required', 'integer', 'min:1', 'max:' . SystemSetting::current()->max_students_per_project],
            'skills'                   => ['nullable', 'string'],
            'keywords'                 => ['nullable', 'string'],
            'notes'                    => ['nullable', 'string'],
        ];
    }
}
