<?php

namespace App\Http\Requests\Concerns;

/**
 * When a student submits a proposal through the student portal, they must
 * always be part of its team. The "students" array validation requires at
 * least one entry, so this must run in prepareForValidation() — injecting
 * their entry inside the controller (after validation) is too late.
 */
trait EnsuresStudentTeamMembership
{
    protected function prepareForValidation(): void
    {
        $user = $this->user();

        if (! $user || ! $user->hasRole('student') || ! $user->student) {
            return;
        }

        $students = $this->input('students', []);
        $alreadyIncluded = collect($students)->contains(
            fn ($s) => ($s['registration_number'] ?? null) === $user->student->registration_number
        );

        if (! $alreadyIncluded) {
            array_unshift($students, [
                'full_name'           => $user->student->full_name,
                'registration_number' => $user->student->registration_number,
                'phone_number'        => null,
            ]);
        }

        $this->merge(['students' => $students]);
    }
}
