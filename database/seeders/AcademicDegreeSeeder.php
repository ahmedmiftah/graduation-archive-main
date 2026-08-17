<?php

namespace Database\Seeders;

use App\Models\AcademicDegree;
use Illuminate\Database\Seeder;

class AcademicDegreeSeeder extends Seeder
{
    public function run(): void
    {
        $degrees = [
            ['degree_name' => 'دبلوم',    'degree_code' => 'Dip.'],
            ['degree_name' => 'بكالوريوس', 'degree_code' => 'B.Sc'],
            ['degree_name' => 'ماجستير',   'degree_code' => 'M.Sc'],
            ['degree_name' => 'دكتوراه',   'degree_code' => 'Ph.D'],
        ];

        foreach ($degrees as $degree) {
            AcademicDegree::firstOrCreate(['degree_code' => $degree['degree_code']], $degree);
        }
    }
}
