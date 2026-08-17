<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'max_students_per_project',
        'examiners_per_project',
        'max_projects_per_supervisor_per_semester',
        'registration_number_length',
        'academic_year_format',
        'archive_enabled',
        'archivable_proposal_statuses',
    ];

    protected function casts(): array
    {
        return [
            'archive_enabled'               => 'boolean',
            'archivable_proposal_statuses'  => 'array',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'archivable_proposal_statuses' => ['approved', 'rejected'],
        ]);
    }
}
