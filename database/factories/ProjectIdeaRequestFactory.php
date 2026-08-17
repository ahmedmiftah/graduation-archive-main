<?php

namespace Database\Factories;

use App\Models\ProjectIdea;
use App\Models\ProjectIdeaRequest;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectIdeaRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_idea_id' => ProjectIdea::factory(),
            'student_id'      => Student::factory(),
            'message'         => $this->faker->sentence(),
            'status'          => ProjectIdeaRequest::STATUS_PENDING,
        ];
    }
}
