<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\EnsuresStudentTeamMembership;
use App\Models\ProjectProposal;
use App\Models\Semester;
use App\Models\SystemSetting;
use App\Rules\SupervisorSemesterCapacity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectProposalRequest extends FormRequest
{
    use EnsuresStudentTeamMembership;

    public function authorize(): bool
    {
        // Authorization is handled by the policy in the controller.
        return true;
    }

    public function rules(): array
    {
        $settings = SystemSetting::current();
        $yearPattern = $settings->academic_year_format === '2_digit' ? '/^\d{2}$/' : '/^\d{4}$/';

        $routeProposal = $this->route('proposal');
        $proposalId    = $routeProposal instanceof ProjectProposal ? $routeProposal->id : (int) $routeProposal;

        return [
            'title'                           => ['required', 'string', 'max:255'],
            'description'                     => ['required', 'string'],
            'specialization_id'               => ['required', 'exists:specializations,id'],
            'academic_year'                   => ['required', 'string', 'regex:' . $yearPattern],
            'semester'                        => ['required', Rule::in(Semester::pluck('name'))],
            'submission_date'                 => ['nullable', 'date'],
            'supervisor_id'                   => [
                'nullable',
                'exists:faculty_members,id',
                new SupervisorSemesterCapacity($this->input('academic_year'), $this->input('semester'), $proposalId),
            ],
            'students'                        => ['required', 'array', 'min:1', 'max:' . $settings->max_students_per_project],
            'students.*.full_name'            => ['required', 'string'],
            'students.*.registration_number'  => ['required', 'string'],
            'students.*.phone_number'         => ['nullable', 'string'],
            'form_file'                       => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'proposal_file'                   => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ];
    }
}
