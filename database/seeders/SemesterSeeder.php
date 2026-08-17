<?php

namespace Database\Seeders;

use App\Models\Semester;
use Illuminate\Database\Seeder;

class SemesterSeeder extends Seeder
{
    public function run(): void
    {
        $semesters = [
            ['name' => 'ربيع', 'sort_order' => 1, 'is_active' => true],
            ['name' => 'خريف', 'sort_order' => 2, 'is_active' => true],
        ];

        foreach ($semesters as $semester) {
            Semester::firstOrCreate(['name' => $semester['name']], $semester);
        }
    }
}
