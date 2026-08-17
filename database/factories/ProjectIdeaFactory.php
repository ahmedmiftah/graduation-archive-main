<?php

namespace Database\Factories;

use App\Models\FacultyMember;
use App\Models\ProjectIdea;
use App\Models\Specialization;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectIdeaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'faculty_member_id'        => FacultyMember::factory(),
            'title'                    => $this->faker->sentence(4),
            'description'              => $this->faker->paragraph(),
            'specialization_id'        => Specialization::factory(),
            'required_students_count' => $this->faker->numberBetween(1, 3),
            'skills'                   => $this->faker->words(3, true),
            'keywords'                 => $this->faker->words(3, true),
            'notes'                    => null,
            'status'                   => ProjectIdea::STATUS_AVAILABLE,
        ];
    }
}
