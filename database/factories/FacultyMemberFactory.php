<?php

namespace Database\Factories;

use App\Models\AcademicDegree;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class FacultyMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'full_name'    => $this->faker->name(),
            'phone_number' => $this->faker->unique()->numerify('05########'),
            'email'        => $this->faker->unique()->safeEmail(),
            'degree_id'    => fn () => AcademicDegree::inRandomOrder()->value('id') ?? AcademicDegree::create([
                'degree_name' => 'بكالوريوس',
                'degree_code' => 'B.Sc',
            ])->id,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (\App\Models\FacultyMember $facultyMember) {
            if ($facultyMember->departments()->exists()) {
                return;
            }

            $facultyMember->departments()->attach(Department::factory()->create());
        });
    }
}
