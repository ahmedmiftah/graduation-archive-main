<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\FacultyMember;
use App\Models\Specialization;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    public function definition(): array
    {
        $year = $this->faker->numberBetween(2020, 2026);

        return [
            'project_title'     => $this->faker->sentence(4),
            'description'       => $this->faker->paragraph(),
            'academic_year'     => $year . '/' . ($year + 1),
            'department_id'     => Department::factory(),
            'specialization_id' => Specialization::factory(),
            'supervisor_id'     => FacultyMember::factory(),
            'current_status_id' => 5, // in_progress — matches the default for newly created projects
            'is_deleted'        => false,
        ];
    }
}
