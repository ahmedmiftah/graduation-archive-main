<?php

namespace App\Http\Requests;

use App\Models\Semester;
use App\Models\Specialization;
use App\Models\SystemSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled by role middleware + department scoping in the controller.
        return true;
    }

    /**
     * dept_manager can only ever create students inside their own department —
     * force the field before validation so no client-submitted value can bypass this.
     */
    protected function prepareForValidation(): void
    {
        /** @var \App\Models\User $authUser */
        $authUser = $this->user();

        if ($authUser->hasRole('dept_manager')) {
            $this->merge(['department_id' => $authUser->department_id]);
        }
    }

    public function rules(): array
    {
        $registrationLength = (int) SystemSetting::current()->registration_number_length;

        return [
            'full_name'           => ['required', 'string', 'max:255'],
            'national_id'         => ['required', 'regex:/^\d{12}$/', 'unique:students,national_id'],
            'registration_number' => ['required', 'string', 'size:' . $registrationLength, 'unique:students,registration_number'],
            'department_id'       => ['required', 'exists:departments,id'],
            'specialization_id'   => ['required', 'exists:specializations,id'],
            'semester'            => ['required', Rule::in(Semester::pluck('name'))],
            'academic_year'       => ['required', 'string', 'max:10'],
            'date_of_birth'       => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        $registrationLength = (int) SystemSetting::current()->registration_number_length;

        return [
            'national_id.regex'         => 'الرقم الوطني يجب أن يتكون من 12 رقماً',
            'registration_number.size'  => "رقم القيد يجب أن يتكون من {$registrationLength} خانة",
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('department_id') || $validator->errors()->has('specialization_id')) {
                return;
            }

            $specialization = Specialization::find($this->input('specialization_id'));
            if ($specialization && (int) $specialization->department_id !== (int) $this->input('department_id')) {
                $validator->errors()->add('specialization_id', 'التخصص المحدد لا ينتمي إلى القسم المحدد');
            }
        });
    }
}
