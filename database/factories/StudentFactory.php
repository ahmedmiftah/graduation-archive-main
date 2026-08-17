<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Specialization;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'full_name'           => $this->faker->name(),
            'national_id'         => $this->faker->unique()->numerify('############'),
            'registration_number' => $this->faker->unique()->numerify('#########'),
            'department_id'       => fn () => Department::factory()->create()->id,
            'specialization_id'   => fn (array $attrs) => Specialization::factory()->create([
                'department_id' => $attrs['department_id'],
            ])->id,
            'semester'            => 'خريف',
            'academic_year'       => '2024',
            'date_of_birth'       => $this->faker->dateTimeBetween('-25 years', '-18 years')->format('Y-m-d'),
        ];
    }
}
