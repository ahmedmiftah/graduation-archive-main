<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        SystemSetting::firstOrCreate([], [
            'max_students_per_project'                => 5,
            'examiners_per_project'                    => 2,
            'max_projects_per_supervisor_per_semester' => 5,
            'registration_number_length'               => 9,
            'academic_year_format'                     => '4_digit',
            'archive_enabled'                           => true,
            'archivable_proposal_statuses'               => ['approved', 'rejected'],
        ]);
    }
}
