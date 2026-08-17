<?php

namespace Database\Factories;

use App\Models\ProjectIdea;
use App\Models\ProposalReservation;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProposalReservationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_idea_id' => ProjectIdea::factory(),
            'student_id'      => Student::factory(),
            'status'          => ProposalReservation::STATUS_RESERVED,
        ];
    }
}
