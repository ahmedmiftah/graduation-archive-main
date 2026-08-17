<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\FacultyMember;
use App\Models\ProjectProposal;
use App\Models\Specialization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectProposalFactory extends Factory
{
    protected $model = ProjectProposal::class;

    public function definition(): array
    {
        return [
            'title'           => $this->faker->sentence(4),
            'description'     => $this->faker->paragraph(),
            'department_id'   => Department::factory(),
            'specialization_id' => Specialization::factory(),
            'academic_year'   => (string) $this->faker->numberBetween(2020, 2026),
            'semester'        => $this->faker->randomElement(['ربيع', 'خريف']),
            'supervisor_id'   => FacultyMember::factory(),
            'status'          => ProjectProposal::STATUS_PENDING,
            'submission_date' => now()->toDateString(),
            'created_by'      => User::factory(),
        ];
    }
}
