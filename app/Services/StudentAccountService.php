<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use App\Notifications\StudentAccountCreated;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Single source of truth for turning a validated student row into a
 * Student + login-account pair. Shared by the bulk Excel import and the
 * single-student add form so both paths can never drift apart.
 */
class StudentAccountService
{
    public function create(array $data): Student
    {
        return DB::transaction(function () use ($data) {
            $dateOfBirth = $data['date_of_birth'] instanceof Carbon
                ? $data['date_of_birth']
                : Carbon::parse($data['date_of_birth']);

            $student = Student::create([
                'full_name'           => $data['full_name'],
                'national_id'         => $data['national_id'],
                'registration_number' => $data['registration_number'],
                'department_id'       => $data['department_id'],
                'specialization_id'   => $data['specialization_id'],
                'semester'            => $data['semester'],
                'academic_year'       => $data['academic_year'],
                'date_of_birth'       => $dateOfBirth,
            ]);

            $user = User::create([
                'name'                  => $data['full_name'],
                'email'                 => strtolower($data['registration_number']) . '@students.local',
                'password'              => $dateOfBirth->format('dmY'),
                'is_active'             => true,
                'force_password_change' => true,
                'email_verified_at'     => now(),
            ]);
            $user->assignRole('student');

            $student->update(['user_id' => $user->id]);

            Notification::send($user, new StudentAccountCreated($student));

            return $student;
        });
    }
}
