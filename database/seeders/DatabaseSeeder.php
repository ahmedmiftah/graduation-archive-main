<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            ProjectStatusSeeder::class,
            AcademicDegreeSeeder::class,
            SystemSettingSeeder::class,
            SemesterSeeder::class,
            LifecycleStageSeeder::class,
            AdminSeeder::class,
            DummyDataSeeder::class,
        ]);
    }
}
