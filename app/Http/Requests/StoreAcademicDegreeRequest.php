<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAcademicDegreeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'degree_name' => ['required', 'string', 'max:150', 'unique:academic_degrees,degree_name'],
            'degree_code' => ['required', 'string', 'max:20', 'unique:academic_degrees,degree_code'],
        ];
    }
}
