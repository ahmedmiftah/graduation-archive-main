<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AcademicDegreeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'degree_name' => $this->faker->unique()->words(2, true),
            'degree_code' => strtoupper($this->faker->unique()->lexify('???.??')),
        ];
    }
}
